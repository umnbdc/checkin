<?php

date_default_timezone_set('America/Chicago');

// Import some configurations
require_once "resources/config.php";
require_once "resources/db.php";

require_once 'auth.php';
require_once 'resources/mailchimp.php';
require_once 'resources/referrals.php';
require_once 'resources/checkin.php';


// Safeguard against forgetting to change the current term start and end
if ( strtotime($CURRENT_START_DATE) > strtotime('today') || strtotime($CURRENT_END_DATE) < strtotime('today') ) {
  die("Current term (range) is out of date.");
}


/* BEGIN DUES, FEES, CHECK INS */

// return positive integer, number of cents
function calculateDues($membership, $feeStatus, $term) {
  global $FEE_TABLE;

//    // Summer membership/feeStatus should only be available in during summer terms
//    if ( strpos($term, "Summer") === 0 ) {
//        $FEE_TABLE['Summer'] = [];
//        $FEE_TABLE['Summer']['Summer'] = 0;
//    }

  if (array_key_exists($feeStatus, $FEE_TABLE) && array_key_exists($membership, $FEE_TABLE[$feeStatus])) {
    return $FEE_TABLE[$feeStatus][$membership];
  } else {
    die("membership-feeStatus-term combination is invalid");
  }
}

function createMembershipDueKind($membership, $feeStatus, $term) {
  return "Membership (" . $membership . ", " . $feeStatus . ", " . $term . ")";
}

// returns balance
function calculateOutstandingDues($safe_member_id) {
  global $link;
  
  $selectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $safe_member_id . "' AND `kind` LIKE 'Membership%'";
  $transactions = assocArraySelectQuery($selectQuery, $link, "Failed to select debit_credit from calculateOutstandingDues");
  
  $balance = 0;
  foreach( $transactions as $t ) {
    $balance += $t['amount'];
  }
  return $balance;
}

function hasHadMembership($safeId) {
  global $link;
  
  $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safeId . "'";
  $membershipArray = assocArraySelectQuery($selectQuery, $link, "Failed to select membership in isFirstMembership");
  return $membershipArray != [];
}

function insertPayment($member_id, $amount, $method, $kind) {
  // assumes that the parameters are safe
  global $link;
    
  $insertQuery = sprintf("INSERT INTO `debit_credit`(`member_id`, `amount`, `method`, `kind`, `date_time`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP)",
      "'" . $member_id . "'",
      "'" . $amount . "'",
      "'" . $method . "'",
      "'" . $kind . "'");
  safeQuery($insertQuery, $link, "Failed to insert new payment");
}

function getMembershipAndFeeStatus($safe_member_id, $term) {
  global $link;
  
  $toReturn = array(
    'membership' => '',
    'fee_status' => ''
  );

  $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safe_member_id . "' AND `term`='" . $term . "'";
  $membershipArray = assocArraySelectQuery($selectQuery, $link, "Failed to select membership in getMembershipAndFeeStatus");
  if ( $membershipArray != [] ) {
    $toReturn['membership'] = $membershipArray[0]['kind'];
  }
  
  $selectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $safe_member_id . "' AND `term`='" . $term . "'";
  $feeStatusArray = assocArraySelectQuery($selectQuery, $link, "Failed to select fee status in getMembershipAndFeeStatus");
  if ( $feeStatusArray != [] ) {
    $toReturn['fee_status'] = $feeStatusArray[0]['kind'];
  }
  
  return $toReturn;
}

function updateCompetitionLateFees($safe_member_id, $term) {
  global $link;
  global $COMP_DUE_DATE_TABLE;
  global $COMP_PRACTICES_TABLE;
  global $LATE_FEE_AMOUNT;
  
  $membershipAndFeeStatus = getMembershipAndFeeStatus($safe_member_id, $term);
  $membership = $membershipAndFeeStatus['membership'];
  $fee_status = $membershipAndFeeStatus['fee_status'];
  
  if ( $membership != 'Competition' ) {
    return;
  }
  
  $balance = calculateOutstandingDues($safe_member_id);
  
  if ( $balance < 0 ) {
    // get amount due by this date
    $dueTable = $COMP_DUE_DATE_TABLE[$term][$fee_status];
    // get earliest past due date where balance is less than minimum
    $earliestPastDueDate = '';
    foreach( array_keys($dueTable) as $date ) {
      $minOutstandingAtDate = $dueTable[$date];
      if ( strtotime($date) < strtotime('today') && $balance < $minOutstandingAtDate ) {
        $earliestPastDueDate = $date;
        break;
      }
    }
    if ( $earliestPastDueDate != '' ) {
      // count the practices from after (excluding) $earliestPastDueDate until now (including today)
      $practicesLate = 0;
      foreach( $COMP_PRACTICES_TABLE[$term] as $practice ) {
        if ( strtotime($practice) > strtotime('today') ) {
          break;
        } else if ( strtotime($practice) > strtotime($earliestPastDueDate) ) {
          $practicesLate += 1;
        }
      }
      // update late fee debit
      $kindPartial = "Membership (Late fee, since " . $earliestPastDueDate;
      $debitDeleteQuery = "DELETE FROM `debit_credit` WHERE `member_id`='" . $safe_member_id . "' AND `kind` LIKE '" . $kindPartial . "%'";
      safeQuery($debitDeleteQuery, $link, "Failed to delete old late fee debit in updateCompetitionLateFees");
      
      $kind = $kindPartial . ", " . $practicesLate . " practices late)";
      $amount = -1 * $LATE_FEE_AMOUNT * $practicesLate;
      $debitInsertQuery = "INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`) VALUES ('" . $safe_member_id . "','" . $amount . "','" . $kind . "')";
      safeQuery($debitInsertQuery, $link, "Failed to insert new late fee debit in updateCompetitionLateFees");
    }
  }
}

/* END DUES, FEES, CHECK INS */
/* BEGIN POST REQUEST HANDLING */

$data = $_POST;

include('auth.php');
include('mailchimp.php');

switch ( $_POST['type'] ) {
  case "environment":
    $data = [];
    $data['CURRENT_TERM'] = $CURRENT_TERM;
    break;

  case "newMember":
    $member = $_POST['member'];
    $escapedEmail = mysql_escape_string($member['email']);

    $data = array("succeeded" => false, "reason" => "", member => null);

    // prevent duplicate emails
    $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $escapedEmail);
    $members = assocArraySelectQuery($selectQuery, $link, "Failed to check for a member with the same email in newMember");
    if ( count($members) != 0 ) {
      assert(count($members) == 1);
      $data['reason'] = "A member with the given email already exists.";
      $data['member'] = $members[0];
    } else {
      $insertQuery = sprintf("INSERT INTO `member`(`first_name`, `last_name`, `nick_name`, `email`, `join_date`, `referred_by`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP,%s)",
          "'" . mysql_escape_string($member['firstName']) . "'",
          "'" . mysql_escape_string($member['lastName']) . "'",
          $member['nickname'] ? "'" . mysql_escape_string($member['nickname']) . "'" : 'NULL',
          "'" . $escapedEmail . "'",
          $member['referredBy'] ? mysql_escape_string($member['referredBy']) : 'NULL');
      safeQuery($insertQuery, $link, "Failed to insert new member in newMember");

      $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $escapedEmail);
      $members = assocArraySelectQuery($selectQuery, $link, "Failed to fetch the new member in newMember");
      assert(count($members) == 1);
      $data['succeeded'] = true;
      $data['member'] = $members[0];
    }
    break;

  case "updateMember":
    $id = mysql_escape_string($_POST['id']);
    $firstName = mysql_escape_string($_POST['firstName']);
    $lastName = mysql_escape_string($_POST['lastName']);
    $nickName = mysql_escape_string($_POST['nickName']);
    $email = mysql_escape_string($_POST['email']);
    $proficiency = mysql_escape_string($_POST['proficiency']);

    if (isVolunteer()) {
      $data['succeeded'] = false;
      $data['reason'] = "Volunteers cannot change update member information.";
    } else {
      $updateQuery = "UPDATE `member` SET `first_name`='" . $firstName . "',`nick_name`='" . $nickName . "',`last_name`='" . $lastName . "',`email`='" . $email . "',`proficiency`='" . $proficiency . "' WHERE `id`='" . $id . "'";
      $data['updateQuery'] = $updateQuery;
      safeQuery($updateQuery, $link, "Failed to update member info");
      $data['succeeded'] = true;
    }
    break;

  case "designateIntermediate":
    if ( isVolunteer() ) {
      $data['succeeded'] = false;
      $data['reason'] = "Volunteers cannot designate intermediate status";
    } else {
      $id = mysql_escape_string($_POST['member_id']);
      $updateQuery = "UPDATE `member` SET `proficiency`='Intermediate' WHERE `id`='" . $id . "'";
      $data['updateQuery'] = $updateQuery;
      safeQuery($updateQuery, $link, "Failed to designate intermediate member");
      $data['succeeded'] = true;
    }
    break;

  case "getMembers":
    $likeConditions = "";
    $searchTermParts = explode(" ", $_POST['query']);
    $conditionCount = 0;
    foreach ($searchTermParts as $s) {
      if ( !preg_match('/^\s*$/', $s) ) {
        $s = "%" . mysql_escape_string($s) . "%";
        $likeConditions = $likeConditions . " AND (`first_name` LIKE '" . $s . "' OR `last_name` LIKE '" . $s . "' OR `nick_name` LIKE '" . $s . "' OR `email` LIKE '" . $s . "')";
        $conditionCount++;
      }
    }

    if ( $conditionCount > 0 ) {
      $selectQuery = "SELECT * FROM `member` WHERE 1" . $likeConditions . " ORDER BY `last_name`";
      $data = assocArraySelectQuery($selectQuery, $link, "Failed to search members");
    } else {
      $data = [];
    }
    break;

  case "getMemberInfo":
    $id = mysql_escape_string($_POST['id']);

    $data = [];

    updateCompetitionLateFees($id, $CURRENT_TERM); // function will check if applicable

    $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
    $data['member'] = assocArraySelectQuery($memberSelectQuery, $link, "Failed to getMemberInfo member")[0]; // assume only one member with id
    if ( $data['member']['referred_by'] ) {
      $referredBySelectQuery = "SELECT * FROM `member` WHERE `id`='" . $data['member']['referred_by'] . "'";
      $referrerMemberObject = assocArraySelectQuery($referredBySelectQuery, $link, "Failed to get referrer member info in getMemberInfo")[0];
      $data['member']['referred_by_name'] = $referrerMemberObject['first_name'] . " " . $referrerMemberObject['last_name'];
    }

    $membershipSelectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $id . "'";
    $data['memberships'] = assocArraySelectQuery($membershipSelectQuery, $link, "Failed to getMemberInfo membership");

    $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
    $data['checkIns'] = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to getMemberInfo checkin");

    $debitCreditSelectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
    $data['debitCredits'] = assocArraySelectQuery($debitCreditSelectQuery, $link, "Failed to getMemberInfo debit/credit");

    $feeStatusSelectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $id . "'";
    $data['feeStatus'] = assocArraySelectQuery($feeStatusSelectQuery, $link, "Failed to getMemberInfo fee status");

    $waiverStatusSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $id . "'";
    $data['waiverStatus'] = assocArraySelectQuery($waiverStatusSelectQuery, $link, "Failed to getMemberInfo waiver status");

    $referralSelectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $id . "'";
    $references = assocArraySelectQuery($referralSelectQuery, $link, "Failed to getMemberInfo referral");
    for ( $i = 0; $i < count($references); $i++ ) {
      $referredSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $references[$i]['referred_id'] . "'";
      $memberObject = assocArraySelectQuery($referredSelectQuery, $link, "Failed to get referred member info in getMemberInfo")[0];
      $references[$i]['referred_name'] = $memberObject['first_name'] . " " . $memberObject['last_name'];
    }
    $data['references'] = $references;

    $rewardSelectQuery = "SELECT * FROM `reward` WHERE `member_id`='" . $id . "'";
    $data['rewards'] = assocArraySelectQuery($rewardSelectQuery, $link, "Failed to getMemberInfo rewards");
    break;

  case "checkInMember":
    $id = mysql_escape_string($_POST['id']);
    $override = array_key_exists('override', $_POST) && $_POST['override'] == "true";
    $data = checkinMember($id, $override, $data, $link);
    break;

  case "checkedIn?":
    $id = mysql_escape_string($_POST['id']);
    $data['checkedIn'] = checkedInToday($id, $link);
    break;

  case "updateMembershipAndFeeStatus":
    $id = mysql_escape_string($_POST['id']);
    $feeStatus = mysql_escape_string($_POST['feeStatus']);
    $membership = mysql_escape_string($_POST['membership']);
    $term = mysql_escape_string($_POST['term']);

    $data = [];

    if (isVolunteer() && $membership == 'Competition') {
      $data['succeeded'] = false;
      $data['reason'] = 'Volunteers cannot assign competition team membership';
    } else {
      $oldFeeStatus = '';
      $oldMembership = '';

      // we don't want to generate the referral before in case something fails
      // but we need to check for previous membership before memberships are inserted into the DB
      // also only generate referral for paid memberships
      // Note:
      // Also if the new combination is invalid, it will error out before DB changes are made
      $generateReferralAtEnd = !hasHadMembership($id) && calculateDues($membership, $feeStatus, $term) > 0;

      // update/insert fee status
      $feeStatusSelectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $id . "' AND `term`='" . $term . "'";
      $feeStatusResult = assocArraySelectQuery($feeStatusSelectQuery, $link, "Failed to select fee status");
      if ( $feeStatusResult ) {
        $fee_status_id = $feeStatusResult[0]['id'];
        $oldFeeStatus = $feeStatusResult[0]['kind'];
        $feeStatusUpdateQuery = "UPDATE `fee_status` SET `kind`='" . $feeStatus . "' WHERE `id`='" . $fee_status_id . "'";
        safeQuery($feeStatusUpdateQuery, $link, "Failed to update new fee status");
      } else {
        $feeStatusInsertQuery = sprintf("INSERT INTO `fee_status`(`member_id`, `term`, `kind`) VALUES (%s,%s,%s)",
            "'" . $id . "'",
            "'" . $term . "'",
            "'" . $feeStatus . "'");
        safeQuery($feeStatusInsertQuery, $link, "Failed to insert new fee status");
      }

      // update/insert membership
      $membershipSelectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $id . "' AND `term`='" . $term . "'";
      $membershipResult = assocArraySelectQuery($membershipSelectQuery, $link, "Failed to select membership");
      if ( $membershipResult ) {
        $membership_id = $membershipResult[0]['id'];
        $oldMembership = $membershipResult[0]['kind'];
        $membershipUpdateQuery = "UPDATE `membership` SET `kind`='" . $membership . "' WHERE `id`='" . $membership_id . "'";
        safeQuery($membershipUpdateQuery, $link, "Failed to update new membership");
      } else {
        $membershipInsertQuery = sprintf("INSERT INTO `membership`(`member_id`, `term`, `kind`) VALUES (%s,%s,%s)",
            "'" . $id . "'",
            "'" . $term . "'",
            "'" . $membership . "'");
        safeQuery($membershipInsertQuery, $link, "Failed to insert new membership");
      }

      // update dues?
      if ( $oldFeeStatus != $feeStatus || $oldMembership != $membership ) {
        // was there an old feeStatus-membership debit
        if ( $oldFeeStatus && $oldMembership ) {
          // Note, could be done less precisely using LIKE keyword to wildcard membership and feeStatus
          $oldDueKind = createMembershipDueKind($oldMembership, $oldFeeStatus, $term);
          $duesDeleteQuery = "DELETE FROM `debit_credit` WHERE `member_id`='" . $id . "' AND `kind`='" . $oldDueKind . "'";
          safeQuery($duesDeleteQuery, $link, "Failed to delete old membership debit");
          $data['duesDeleteQuery'] = $duesDeleteQuery;
        }
        $newDueKind = createMembershipDueKind($membership, $feeStatus, $term);
        $amount = -1 * calculateDues($membership, $feeStatus, $term);
        $duesInsertQuery = sprintf("INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`, `date_time`) VALUES (%s,%s,%s,CURRENT_TIMESTAMP)",
            "'" . $id . "'",
            "'" . $amount . "'",
            "'" . $newDueKind . "'");
        safeQuery($duesInsertQuery, $link, "Failed to insert new membership debit");
      }

      $data['oldFeeStatus'] = $oldFeeStatus;
      $data['oldMembership'] = $oldMembership;
      $data['newFeeStatus'] = $feeStatus;
      $data['newMembership'] = $membership;

      if ( generateReferralAtEnd ) {
        generateReferral($id);
      }
      $data['succeeded'] = true;
    }
    break;

  case "payment":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $method = mysql_escape_string($_POST['method']);
    $amount = mysql_escape_string($_POST['amount']); // should be in cents

    $authorized = $_POST['auth_role'] == "President" || $_POST['auth_role'] == "Treasurer" || $_POST['auth_role'] == "Admin";
    if ( isVolunteer() ) {
      $data['succeeded'] = false;
      $data['reason'] = "Volunteers cannot process payments.";
    } else if ( $method == "Cash" || $method == "Check" || ($authorized && $method == "Forgiveness") ) {
      if ($method == "Forgiveness") {
        $method = $method . " (" . $_POST['auth_role'] . ")";
      }
      insertPayment($member_id, $amount, $method, $kind);
      $data['succeeded'] = true;
    } else {
      $data['succeeded'] = false;
      $data['reason'] = "Payment method not accepted";
    }
    break;

  case "debit":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $amount = mysql_escape_string($_POST['amount']); // should be in cents

    insertPayment($member_id, $amount, $method, $kind);
    $data['succeeded'] = true;
    break;

  case "purchase":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $method = mysql_escape_string($_POST['method']);

    if ( isVolunteer() ) {
      $data['succeeded'] = false;
      $data['reason'] = "Volunteers cannot process purchases.";
    } else if ( array_key_exists($kind, $PURCHASE_TABLE) ) {
      $amount = $PURCHASE_TABLE[$kind];
      insertPayment($member_id, $amount, $method, $kind); // credit
      insertPayment($member_id, -1*$amount, "", $kind); // debit
      $data['succeeded'] = true;
    } else {
      $data['succeeded'] = false;
      $data['reason'] = 'Cost of item "' . $kind . '" could not be found.';
    }
    break;

  case "addNewCompMemberDiscount":
    $member_id = mysql_escape_string($_POST['member_id']);
    $method = "NewCompMember (" . $_POST['auth_role'] . ")";
    $kind = "Membership (NewCompMember)";
    $amount = $NEW_COMP_MEMBER_DISCOUNT;

    if (
        $_POST['auth_role'] == "President" ||
        $_POST['auth_role'] == "Fundraising" ||
        $_POST['auth_role'] == "Treasurer" ||
        $_POST['auth_role'] == "Admin"
    ) {
      insertPayment($member_id, $amount, $method, $kind);
      $data['succeeded'] = true;
    } else {
      $data['succeeded'] = false;
      $data['reason'] = "Only the president, fundraising officer, treasurer, or admin can add the new team member discount.";
    }
    break;

  case "updateWaiver":
    $member_id = mysql_escape_string($_POST['member_id']);
    $completed = mysql_escape_string($_POST['completed']);
    $term = mysql_escape_string($_POST['term']);
    assert($completed == 0 || $completed == 1);

    if ( $_POST['auth_role'] == "SafetyAndFacilities" || $_POST['auth_role'] == "Admin" ) {
      $deleteQuery = "DELETE FROM `waiver_status` WHERE `member_id`='" . $member_id . "' AND `term`='" . $term . "'";
      safeQuery($deleteQuery, $link, "Failed to delete waiver status in updateWaiver");
      $insertQuery = "INSERT INTO `waiver_status`(`member_id`, `term`, `completed`) VALUES ('" . $member_id . "','" . $term . "','" . $completed . "')";
      safeQuery($insertQuery, $link, "Failed to insert new waiver status in updateWaiver");
      $data['succeeded'] = true;
    } else {
      $data['succeeded'] = false;
      $data['reason'] = "Only the safety and facilities officer can modify waiver information.";
    }
    break;

  case "getWaiverlessMembers":

    if ( $_POST['auth_role'] == "SafetyAndFacilities" || $_POST['auth_role'] == "Admin" ) {
      if ( $_POST['when'] == 'today' ) {
        $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) = DATE(NOW())";
      } else {
        $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) BETWEEN DATE('" . $CURRENT_START_DATE . "') AND DATE('" . $CURRENT_END_DATE . "')";
      }
      $checkins = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins in getWaiverlessMembers");

      $memberIds = [];
      foreach ( $checkins as $c ) {
        $waiverSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $c['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
        $waiverStatusArray = assocArraySelectQuery($waiverSelectQuery, $link, "Failed to select waiver_status in getPresentWaiverlessMembers");
        if ( $waiverStatusArray == [] || $waiverStatusArray[0]['completed'] != 1 ) {
          $memberIds[] = $c['member_id'];
        }
      }

      $memberObjects = [];
      foreach( $memberIds as $id ) {
        $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
        $memberArray = assocArraySelectQuery($memberSelectQuery, $link, "Failed to select member in getPresentWaiverlessMembers");
        assert(count($memberArray) == 1);
        $memberObjects[] = $memberArray[0];
      }

      $data['members'] = $memberObjects;
      $data['succeeded'] = true;
    } else {
      $data['succeeded'] = false;
      $data['reason'] = "Only the safety and facilities officer can modify waiver information.";
    }
    break;

  case "claimReward":
    $reward = $_POST['reward'];
    $rewardId = mysql_escape_string($reward['id']);
    if ( $reward['claimed'] == '0' ) {
      $updateQuery = "UPDATE `reward` SET `claim_date_time`=CURRENT_TIMESTAMP,`claimed`=1 WHERE `id`='" . $rewardId . "'";
      safeQuery($updateQuery, $link, "Failed to update reward in claimReward");
    }
    break;

  case "getCompetitionTeamList":
    $membershipSelectQuery = "SELECT * FROM `membership` WHERE `kind`='Competition' AND `term`='" . $CURRENT_TERM . "'";
    $membershipArray = assocArraySelectQuery($membershipSelectQuery, $link, "Failed to select memberships in getCompetitionTeamList");

    $memberObjects = [];
    foreach($membershipArray as $membership) {
      $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $membership['member_id'] . "'";
      $memberArray = assocArraySelectQuery($memberSelectQuery, $link, "Failed to select member in getPresentWaiverlessMembers");
      assert(count($memberArray) == 1);
      $memberObjects[] = $memberArray[0];
    }

    for ( $i = 0; $i < count($memberObjects); $i++ ) {
      $memberObjects[$i]['balance'] = calculateOutstandingDues($memberObjects[$i]['id']);
    }

    $data = $memberObjects;
    break;

  case "getTransactions":
    $methodConditions = "";
    if ( array_key_exists('methods', $_POST) ) {
      $methods = $_POST['methods'];
      foreach($methods as $m) {
        $methodConditions = $methodConditions . " OR `method`='" . mysql_escape_string($m) . "'";
      }
      if ( count($methods) > 0 ) {
        $methodConditions = " AND (0" . $methodConditions . ")";
      }
    }

    $startDate = array_key_exists('startDate', $_POST) ?  $_POST['startDate'] : $CURRENT_START_DATE;
    $endDate = array_key_exists('endDate', $_POST) ?  $_POST['endDate'] : $CURRENT_END_DATE;

    $query = "SELECT * FROM `debit_credit` WHERE `date_time` BETWEEN '" . $startDate . "' AND '" . $endDate . "'" . $methodConditions . " ORDER BY `date_time`";
    $transactions = assocArraySelectQuery($query, $link, "Failed to select from debit_credit in getTransactions");

    for ( $i = 0; $i < count($transactions); $i++ ) {
      $query = "SELECT * FROM `member` WHERE `id`='" . $transactions[$i]['member_id'] . "'";
      $memberArray = assocArraySelectQuery($query, $link, "Failed to select member in getTransactions");
      assert(count($memberArray)==1);
      $member = $memberArray[0];
      $transactions[$i]['member_name'] = $member['first_name'] . " " . $member['last_name'];
    }

    $data = array("transactions" => $transactions);
    break;

  case "getSummaryData":
    $summary_kind = $_POST['summary_kind'];

    $day = $_POST['day'] ? "'" . $_POST['day'] . "'" : "NOW()";
    // defaults to "term"
    if ( $summary_kind == "day" ) {
      $dateCondition = " DATE(`date_time`) = DATE(" . $day . ")";
    } else if ( $summary_kind == "week" ) {
      $dateCondition = " WEEKOFYEAR(`date_time`)=WEEKOFYEAR(" . $day . ")";
    } else {
      $dateCondition = " DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
    }

    // collect checkins
    $query = "SELECT * FROM `checkin` WHERE" . $dateCondition . " ORDER BY `date_time`";
    $checkins = assocArraySelectQuery($query, $link, "Failed to select checkins in getSummaryData");
    // associate membership with checkin
    for ($i = 0; $i < count($checkins); $i++) {
      $query = "SELECT * FROM `membership` WHERE `member_id`='" . $checkins[$i]['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
      $membershipArray = assocArraySelectQuery($query, $link, "Failed to select membership for checkin in getSummaryData");
      assert(count($membershipArray) < 2);
      $checkins[$i]['membership'] = count($membershipArray) > 0 ? $membershipArray[0]['kind'] : 'None';
      $checkins[$i]['membership_id'] = count($membershipArray) > 0 ? $membershipArray[0]['id'] : 0;
    }
    $data['checkins'] = $checkins;

    // collect new memberships, does not include updated memberships
    $query = "SELECT * FROM `membership` WHERE" . $dateCondition;
    $data['newMemberships'] = assocArraySelectQuery($query, $link, "Failed to select memberships in getSummaryData");

    // collect income
    $query = "SELECT * FROM `debit_credit` WHERE (`method`='Cash' OR `method`='Check') AND `amount`>0 AND" . $dateCondition;
    $data['credits'] = assocArraySelectQuery($query, $link, "Failed to select credits in getSummaryData");
    break;

  case "getComplexMembers":
    $query = "SELECT * FROM `member`";
    $members = assocArraySelectQuery($query, $link, "Failed to select from member in getComplexMembers");

    $thisTerm = $_POST['thisTerm'] ? true : false;
    $termConstraint = $thisTerm ? " AND `term`='" . $CURRENT_TERM . "'" : "";
    $dateConstraint = $thisTerm ? " AND DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'" : "";

    $memberDict = [];
    foreach($members as $member) {
      $mid = $member['id'];

      $query = "SELECT * FROM `membership` WHERE `member_id`='" . $mid . "'" . $termConstraint;
      $member['membership'] = assocArraySelectQuery($query, $link, "Failed to select membership in getComplexMembers");

      $query = "SELECT * FROM `fee_status` WHERE `member_id`='" . $mid . "'" . $termConstraint;
      $member['fee_status'] = assocArraySelectQuery($query, $link, "Failed to select fee_status in getComplexMembers");

      $query = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $mid . "'" . $termConstraint;
      $member['waiver_status'] = assocArraySelectQuery($query, $link, "Failed to select waiver_status in getComplexMembers");

      $query = "SELECT * FROM `referral` WHERE `referrer_id`='" . $mid . "'" . $termConstraint;
      $member['referral'] = assocArraySelectQuery($query, $link, "Failed to select referral in getComplexMembers");

      $query = "SELECT * FROM `reward` WHERE `member_id`='" . $mid . "'" . $termConstraint;
      $member['reward'] = assocArraySelectQuery($query, $link, "Failed to select reward in getComplexMembers");

      $query = "SELECT * FROM `checkin` WHERE `member_id`='" . $mid . "'" . $dateConstraint;
      $member['checkin'] = assocArraySelectQuery($query, $link, "Failed to select checkin in getComplexMembers");

      $query = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $mid . "'" . $dateConstraint;
      $member['debit_credit'] = assocArraySelectQuery($query, $link, "Failed to select debit_credit in getComplexMembers");

      $memberDict[$mid] = $member;
    }

    $data['members'] = array_values($memberDict);
    break;
}


$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>
