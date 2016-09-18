<?php

require_once "config.php";
require_once "db.php";


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

    // "Bring a Friend" Program
    $referralSelectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $referrerMemberId . "' AND `term`='" . $CURRENT_TERM . "'";
    $referrals = assocArraySelectQuery($referralSelectQuery, $link, "Failed to select referrals in generateRewardPostReferral");
    $refCount = count($referrals);
    $description = generateOrdinalString($refCount) . " referral";
    if ( $refCount < 3 ) {
        // generate $25 reward
        $rewardKind = "Bring a Friend (" . $description . ", $25 membership credit)";
        $rewardInsertQuery = "INSERT INTO `reward`(`member_id`, `kind`, `term`, `claim_date_time`, `claimed`) VALUES ('" . $referrerMemberId . "','" . $rewardKind . "','" . $CURRENT_TERM . "',CURRENT_TIMESTAMP,1)";
        safeQuery($rewardInsertQuery, $link, "Failed to insert reward (Bring a friend, $25) in generateRewardPostReferral");
        $creditKind = "Membership (Bring a Friend, " . $description . ", " . $CURRENT_TERM . ")";
        $creditMethod = "Reward (Bring a Friend, " . $description . ", " . $CURRENT_TERM . ")";
        $creditInsertQuery = "INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`, `method`) VALUES ('" . $referrerMemberId . "',2500,'" . $creditKind . "','" . $creditMethod . "')";
        safeQuery($creditInsertQuery, $link, "Failed to insert reward credit (Bring a friend, $25) in generateRewardPostReferral");
    } else {
        // generate free shoe reward
        $rewardKind = "Bring a Friend (" . $description . ", Free pair of shoes)";
        $rewardInsertQuery = "INSERT INTO `reward`(`member_id`, `kind`, `term`, `claimed`) VALUES ('" . $referrerMemberId . "','" . $rewardKind . "','" . $CURRENT_TERM . "',0)";
        safeQuery($rewardInsertQuery, $link, "Failed to insert reward (Bring a friend, shoes) in generateRewardPostReferral");
    }
}

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
