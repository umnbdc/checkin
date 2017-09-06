<?php

require_once "config.php";
require_once "db.php";
require_once "compteam.php";


function newMember($member, $dbLink) {
    $escapedEmail = mysql_escape_string($member['email']);
    $toReturn = array("succeeded" => false, "reason" => "", "member" => null);

    // prevent duplicate emails
    $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $escapedEmail);
    $members = assocArraySelectQuery($selectQuery, $dbLink, "Failed to check for a member with the same email in newMember");
    if (count($members) != 0) {
        assert(count($members) == 1);
        $toReturn['reason'] = "A member with the given email already exists.";
        $toReturn['member'] = $members[0];
    } else {
        $insertQuery = sprintf("INSERT INTO `member`(`first_name`, `last_name`, `nick_name`, `email`, publicity, `join_date`, `referred_by`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP,%s)",
            "'" . mysql_escape_string($member['firstName']) . "'",
	    "'" . mysql_escape_string($member['lastName']) . "'",
            $member['nickname'] ? "'" . mysql_escape_string($member['nickname']) . "'" : 'NULL',
	    "'" . $escapedEmail . "'",
	    "'" . mysql_escape_string($member['publicity']) . "'",
            $member['referredBy'] ? mysql_escape_string($member['referredBy']) : 'NULL');
        safeQuery($insertQuery, $dbLink, "Failed to insert new member in newMember");

        $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $escapedEmail);
        $members = assocArraySelectQuery($selectQuery, $dbLink, "Failed to fetch the new member in newMember");
        assert(count($members) == 1);
        $toReturn['succeeded'] = true;
        $toReturn['member'] = $members[0];
    }
    return $toReturn;
}


function updateMember($firstName, $nickName, $lastName, $email, $proficiency, $id, $authRole, $link) {
    $toReturn = [];
    if ( $authRole == "Volunteer" ) {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Volunteers cannot change update member information.";
    } else {
        $updateQuery = "UPDATE `member` SET `first_name`='" . $firstName . "',`nick_name`='" . $nickName . "',`last_name`='" . $lastName . "',`email`='" . $email . "',`proficiency`='" . $proficiency . "' WHERE `id`='" . $id . "'";
        $toReturn['updateQuery'] = $updateQuery;
        safeQuery($updateQuery, $link, "Failed to update member info");
        $toReturn['succeeded'] = true;
    }
    return $toReturn;
}


function getMembers($query, $dbLink) {
    $likeConditions = "";
    $searchTermParts = explode(" ", $query);
    $conditionCount = 0;
    foreach ($searchTermParts as $s) {
        if ( !preg_match('/^\s*$/', $s) ) {
            $s = "%" . mysql_escape_string($s) . "%";
            $likeConditions = $likeConditions . " AND (`first_name` LIKE '" . $s . "' OR `last_name` LIKE '" . $s . "' OR `nick_name` LIKE '" . $s . "' OR `email` LIKE '" . $s . "')";
            $conditionCount++;
        }
    }

    $members = [];
    if ( $conditionCount > 0 ) {
        $selectQuery = "SELECT * FROM `member` WHERE 1" . $likeConditions . " ORDER BY `last_name`";
        $members = assocArraySelectQuery($selectQuery, $dbLink, "Failed to search members");
    }
    return $members;
}


function getMemberInfo($id, $dbLink) {
    global $CURRENT_TERM;
    $data = [];

    updateCompetitionLateFees($id, $CURRENT_TERM, $dbLink); // function will check if applicable

    $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
    $data['member'] = assocArraySelectQuery($memberSelectQuery, $dbLink, "Failed to getMemberInfo member")[0]; // assume only one member with id
    if ( $data['member']['referred_by'] ) {
        $referredBySelectQuery = "SELECT * FROM `member` WHERE `id`='" . $data['member']['referred_by'] . "'";
        $referrerMemberObject = assocArraySelectQuery($referredBySelectQuery, $dbLink, "Failed to get referrer member info in getMemberInfo")[0];
        $data['member']['referred_by_name'] = $referrerMemberObject['first_name'] . " " . $referrerMemberObject['last_name'];
    }

    $membershipSelectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $id . "'";
    $data['memberships'] = assocArraySelectQuery($membershipSelectQuery, $dbLink, "Failed to getMemberInfo membership");

    $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
    $data['checkIns'] = assocArraySelectQuery($checkinSelectQuery, $dbLink, "Failed to getMemberInfo checkin");

    $debitCreditSelectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
    $data['debitCredits'] = assocArraySelectQuery($debitCreditSelectQuery, $dbLink, "Failed to getMemberInfo debit/credit");

    $feeStatusSelectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $id . "'";
    $data['feeStatus'] = assocArraySelectQuery($feeStatusSelectQuery, $dbLink, "Failed to getMemberInfo fee status");

    $waiverStatusSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $id . "'";
    $data['waiverStatus'] = assocArraySelectQuery($waiverStatusSelectQuery, $dbLink, "Failed to getMemberInfo waiver status");

    $referralSelectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $id . "'";
    $references = assocArraySelectQuery($referralSelectQuery, $dbLink, "Failed to getMemberInfo referral");
    for ( $i = 0; $i < count($references); $i++ ) {
        $referredSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $references[$i]['referred_id'] . "'";
        $memberObject = assocArraySelectQuery($referredSelectQuery, $dbLink, "Failed to get referred member info in getMemberInfo")[0];
        $references[$i]['referred_name'] = $memberObject['first_name'] . " " . $memberObject['last_name'];
    }
    $data['references'] = $references;

    $rewardSelectQuery = "SELECT * FROM `reward` WHERE `member_id`='" . $id . "'";
    $data['rewards'] = assocArraySelectQuery($rewardSelectQuery, $dbLink, "Failed to getMemberInfo rewards");

    return $data;
}


function getComplexMembers($thisTerm, $dbLink) {
    global $CURRENT_TERM;
    global $CURRENT_START_DATE;
    global $CURRENT_END_DATE;

    $termConstraint = $thisTerm ? " AND `term`='" . $CURRENT_TERM . "'" : "";
    $dateConstraint = $thisTerm ? " AND DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'" : "";

    $query = "SELECT * FROM `member`";
    $members = assocArraySelectQuery($query, $dbLink, "Failed to select from member in getComplexMembers");
    $memberDict = [];
    foreach($members as $member) {
        $mid = $member['id'];

        $query = "SELECT * FROM `membership` WHERE `member_id`='" . $mid . "'" . $termConstraint;
        $member['membership'] = assocArraySelectQuery($query, $dbLink, "Failed to select membership in getComplexMembers");

        $query = "SELECT * FROM `fee_status` WHERE `member_id`='" . $mid . "'" . $termConstraint;
        $member['fee_status'] = assocArraySelectQuery($query, $dbLink, "Failed to select fee_status in getComplexMembers");

        $query = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $mid . "'" . $termConstraint;
        $member['waiver_status'] = assocArraySelectQuery($query, $dbLink, "Failed to select waiver_status in getComplexMembers");

        $query = "SELECT * FROM `referral` WHERE `referrer_id`='" . $mid . "'" . $termConstraint;
        $member['referral'] = assocArraySelectQuery($query, $dbLink, "Failed to select referral in getComplexMembers");

        $query = "SELECT * FROM `reward` WHERE `member_id`='" . $mid . "'" . $termConstraint;
        $member['reward'] = assocArraySelectQuery($query, $dbLink, "Failed to select reward in getComplexMembers");

        $query = "SELECT * FROM `checkin` WHERE `member_id`='" . $mid . "'" . $dateConstraint;
        $member['checkin'] = assocArraySelectQuery($query, $dbLink, "Failed to select checkin in getComplexMembers");

        $query = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $mid . "'" . $dateConstraint;
        $member['debit_credit'] = assocArraySelectQuery($query, $dbLink, "Failed to select debit_credit in getComplexMembers");

        $memberDict[$mid] = $member;
    }
    return $memberDict;
}


function hasHadMembership($safeId, $dbLink) {
    $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safeId . "'";
    $membershipArray = assocArraySelectQuery($selectQuery, $dbLink, "Failed to select membership in isFirstMembership");
    return $membershipArray != [];
}


function getMembership($safe_member_id, $term, $dbLink) {
    $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safe_member_id . "' AND `term`='" . $term . "'";
    $membershipArray = assocArraySelectQuery($selectQuery, $dbLink, "Failed to select membership in getMembershipAndFeeStatus");
    return ( $membershipArray != [] )
        ? $membershipArray[0]['kind']
        : '';
}
