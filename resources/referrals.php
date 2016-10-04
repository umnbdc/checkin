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
        }
    }
}


function getLastSemesterReferrals($safeId) {
    global $PREVIOUS_TERM;
    global $link;

    $selectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $safeId . "' AND `term` LIKE '" . $PREVIOUS_TERM . "'";
    $referredArray = assocArraySelectQuery($selectQuery, $link, "Failed to get info from referrals table");
    return $referredArray;
}


/**
 * @param $referrerId
 * @param $dbLink
 * @return bool true if the member has already been credited this semester for referrals
 */
function checkIfPaidForReferralThisTerm($referrerId, $dbLink) {
    global $CURRENT_TERM;
    $creditSelectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $referrerId . "' AND `kind` LIKE 'Membership (Referrer,%" . $CURRENT_TERM . "%'";
    $creditResults = assocArraySelectQuery($creditSelectQuery, $dbLink, "Failed to get info from referrals table");
    return count($creditResults) > 0;
}


function addRewardForAnyReferrals($referrerId, $duesAmount, $dbLink) {
    global $IS_REWARD_SYSTEM_ON;
    global $REWARD_AMOUNT_PER_NEW_MEMBER;
    global $MAX_REFERRAL_REWARD;
    global $CURRENT_TERM;
    if ( !$IS_REWARD_SYSTEM_ON ) {
        return;
    }

    // Determine the amount to credit
    $lastSemesterReferrals = getLastSemesterReferrals($referrerId);
    $rewardAmount = $REWARD_AMOUNT_PER_NEW_MEMBER * count($lastSemesterReferrals);
    $rewardAmount = min($duesAmount, $rewardAmount, $MAX_REFERRAL_REWARD);
    $creditKind = "Membership (Referrer, " . $CURRENT_TERM . ")";
    $creditMethod = "Reward (Referrer, " . $CURRENT_TERM . ")";

    $creditInsertQuery = sprintf("INSERT INTO `debit_credit` (`member_id`, `amount`, `kind`, `method`) VALUES ('%s',%s,'%s','%s')",
        $referrerId,
        $rewardAmount,
        $creditKind,
        $creditMethod);
    safeQuery($creditInsertQuery, $dbLink, "Failed to insert reward credit (Bring a friend, $25) in generateRewardPostReferral");
}
