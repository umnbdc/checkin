<?php

require_once "config.php";
require_once "db.php";


$REFERRAL_REWARD_KIND = "Referral (2017-on program)";


function generateOrdinalString($number) {
    // http://stackoverflow.com/questions/3109978/php-display-number-with-ordinal-suffix
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if (($number %100) >= 11 && ($number%100) <= 13)
        $abbreviation = $number. 'th';
    else
        $abbreviation = $number. $ends[$number % 10];
    return $abbreviation;
}


// assumes that the member identified by $referrerMemberId
// has just made a referral for which no reward has been generated
function generateRewardPostReferral($referrerMemberId) {
    global $link;
    global $CURRENT_TERM;
    global $REFERRAL_REWARD_KIND;

    // Put a reward entry in the reward table
    $rewardInsertQuery = "INSERT INTO `reward`(`member_id`, `kind`, `term`, `claimed`) VALUES ('" . $referrerMemberId . "','" . $REFERRAL_REWARD_KIND . "','" . $CURRENT_TERM . "',0)";
    safeQuery($rewardInsertQuery, $link, "Failed to insert reward in generateRewardPostReferral (query: '" . $rewardInsertQuery . "')");
}


/**
 * Reward whoever referred this member, since this is their first membership and it's giving us money
 * @param $safeId int the id of the new referred member
 */
function generateReferral($safeId) {
    global $link;
    global $CURRENT_TERM;

    $selectQuery = "SELECT * FROM `member` WHERE `id`='" . $safeId . "'";
    $memberArray = assocArraySelectQuery($selectQuery, $link, "Failed to select member in generateReferral");
    assert(count($memberArray) == 1);
    $referredMember = $memberArray[0];

    if ( $referredMember['referred_by'] ) {
        $referredMemberId = $referredMember['id'];
        $referrerMemberId = $referredMember['referred_by'];

        // double check that the referred hasn't already been referred
        $referralSelectQuery = "SELECT * FROM `referral` WHERE `referred_id`='" . $referredMemberId . "'";
        $referralArray = assocArraySelectQuery($referralSelectQuery, $link, "Failed to select referral in generateReferral");
        if ( count($referralArray) == 0 ) {
            $referralInsertQuery = "INSERT INTO `referral`(`referrer_id`, `referred_id`, `term`) VALUES ('" . $referrerMemberId . "','" . $referredMemberId . "','" . $CURRENT_TERM . "')";
            safeQuery($referralInsertQuery, $link, "Failed to insert new referral");

            generateRewardPostReferral($referrerMemberId);
        }
    }
}

function claimReferralRewards($referrerId, $semesterDues) {
    global $link;
    global $CURRENT_TERM;
    global $REFERRAL_REWARD_KIND;

    // Find all referral rewards available (not yet claimed) to this user
    // The reason I do it this way is because we want to apply the reward to a future semester
    // - If we didn't check the term (semester) of the rewards there are scenarios where a member is referred and buys
    //   a membership earlier in the semester than the person that referred them, and then the referrer would be rewarded
    //   this semester
    // - If we only checked for the previous semester, maybe someone referred a friend and then went abroad next semester.
    //   We still want to incentive-ize them to refer people (this is just a choice I made and is what this code does)
    $unclaimedReferralQuery = "SELECT * FROM `reward` WHERE `member_id` = " . $referrerId .
        " AND NOT `term` LIKE '" . $CURRENT_TERM . // Don't allow reward to apply to the same semester as when the referred person joined
        "' AND `kind` LIKE '%" . $REFERRAL_REWARD_KIND . "%'".
        " AND `claimed` = 0";
    $referralRewardsArray = assocArraySelectQuery($unclaimedReferralQuery, $link, "Failed to select member in generateReferral");

    $numUnclaimedRewards = count($referralRewardsArray);
    $numClaimableRewards = min(4, $numUnclaimedRewards); // We allow a maximum of 4 rewards from referrals per semester
    if ( $numUnclaimedRewards > 0 ) {
        // Mark all unclaimed rewards as claimed (even if they exceeded the max of 4)
        // To do this, modify each affected row, selecting by the row's id in the table
        foreach ( $referralRewardsArray as $rewardsEntry ) {
            $rewardUpdateQuery = "UPDATE `reward` SET `claim_date_time`=CURRENT_TIMESTAMP AND `claimed`=" . 1 .
                " WHERE `id`=" . $rewardsEntry['id'];
            safeQuery($rewardUpdateQuery, $link, "Failed to add credit for referral reward");
        }

        // Apply credits in the database
        $amount = min($semesterDues, 1000 * $numClaimableRewards); // Cap rewards at a free membership
        $creditKind = "Membership (Referral Reward)";
        $creditMethod = "Reward (Referral x " . min( 4, $numUnclaimedRewards ) . ")";
        $creditQuery = "INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`, `method`) VALUES ('" .
            $referrerId . "'," . $amount . ",'" . $creditKind . "','" . $creditMethod . "')";
        safeQuery($creditQuery, $link, "Failed to insert referral credits");
    }
    return $numClaimableRewards;
}
