<?php

date_default_timezone_set('America/Chicago');

// Import some configurations
require_once "resources/config.php";
require_once "resources/db.php";

require_once 'auth.php';
require_once 'resources/mailchimp.php';
require_once 'resources/referrals.php';
require_once 'resources/checkin.php';
require_once 'resources/memberInfo.php';
require_once 'resources/fees.php';


// Safeguard against forgetting to change the current term start and end
if ( strtotime($CURRENT_START_DATE) > strtotime('today') || strtotime($CURRENT_END_DATE) < strtotime('today') ) {
  die("Current term (range) is out of date.");
}


/* BEGIN DUES, FEES, CHECK INS */


function updateCompetitionLateFees($safe_member_id, $term) {
  global $link;
  global $COMP_DUE_DATE_TABLE;
  global $COMP_PRACTICES_TABLE;
  global $LATE_FEE_AMOUNT;

  $membership = getMembership($safe_member_id, $term, $link);
  $fee_status = getFeeStatus($safe_member_id, $term, $link);
  if ( $membership != 'Competition' ) {
    return;
  }
  
  $balance = calculateOutstandingDues($safe_member_id, $link);
  
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

function setSucceededAndReason($data, $result) {
  $data['succeeded'] = $result['succeeded'];
  if (array_key_exists('reason', $result)) {
    $data['reason'] = $result['reason'];
  }
  return $data;
}


$data = $_POST;

switch ( $_POST['type'] ) {
  case "environment":
    $data = ['CURRENT_TERM' => $CURRENT_TERM];
    break;

  case "newMember":
    $member = $_POST['member'];
    $data = newMember($member, $link);
    break;

  case "updateMember":
    $id = mysql_escape_string($_POST['id']);
    $firstName = mysql_escape_string($_POST['firstName']);
    $lastName = mysql_escape_string($_POST['lastName']);
    $nickName = mysql_escape_string($_POST['nickName']);
    $email = mysql_escape_string($_POST['email']);
    $proficiency = mysql_escape_string($_POST['proficiency']);
    $authRole = $_POST['auth_role'];

    $result = updateMember($firstName, $nickName, $lastName, $email, $proficiency, $id, $link);
    $data = setSucceededAndReason($data, $result);
    if (array_key_exists('updateQuery', $result)) {
      $data['updateQuery'] = $result['updateQuery'];
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
    $query = $_POST['query'];
    $data = getMembers($query, $link);
    break;

  case "getMemberInfo":
    $id = mysql_escape_string($_POST['id']);
    $data = getMemberInfo($id, $link);
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
    $authRole = $_POST['auth_role'];

    $data = updateMembershipAndFeeStatus($authRole, $membership, $id, $feeStatus, $term, $link);
    break;

  case "payment":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $method = mysql_escape_string($_POST['method']);
    $amount = mysql_escape_string($_POST['amount']); // should be in cents
    $authRole = $_POST['auth_role'];

    $result = doPayment($member_id, $kind, $method, $amount, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "debit":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $amount = mysql_escape_string($_POST['amount']); // should be in cents

    insertPayment($member_id, $amount, $method, $kind, $link);
    $data['succeeded'] = true;
    break;

  case "purchase":
    $member_id = mysql_escape_string($_POST['member_id']);
    $kind = mysql_escape_string($_POST['kind']);
    $method = mysql_escape_string($_POST['method']);
    $authRole = $_POST['auth_role'];
    $result = doPurchase($member_id, $kind, $method, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "addNewCompMemberDiscount":
    function addNewCompMemberDiscount($memberId, $authRole, $dbLink) {
      global $NEW_COMP_MEMBER_DISCOUNT;

      $method = "NewCompMember (" . $authRole . ")";
      $kind = "Membership (NewCompMember)";
      $amount = $NEW_COMP_MEMBER_DISCOUNT;
      $toReturn = [];

      if (
          $authRole == "President" ||
          $authRole == "Fundraising" ||
          $authRole == "Treasurer" ||
          $authRole == "Admin"
      ) {
        insertPayment($memberId, $amount, $method, $kind, $dbLink);
        $toReturn['succeeded'] = true;
      } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Only the president, fundraising officer, treasurer, or admin can add the new team member discount.";
      }
      return $toReturn;
    }
    $member_id = mysql_escape_string($_POST['member_id']);
    $authRole = $_POST['auth_role'];
    $result = addNewCompMemberDiscount($member_id, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "updateWaiver":
    function updateWaiver($member_id, $completed, $term, $authRole, $dbLink) {
      $toReturn = [];
      assert($completed == 0 || $completed == 1);
      if ( $authRole == "SafetyAndFacilities" || $authRole == "Admin" ) {
        $deleteQuery = "DELETE FROM `waiver_status` WHERE `member_id`='" . $member_id . "' AND `term`='" . $term . "'";
        safeQuery($deleteQuery, $dbLink, "Failed to delete waiver status in updateWaiver");
        $insertQuery = "INSERT INTO `waiver_status`(`member_id`, `term`, `completed`) VALUES ('" . $member_id . "','" . $term . "','" . $completed . "')";
        safeQuery($insertQuery, $dbLink, "Failed to insert new waiver status in updateWaiver");
        $toReturn['succeeded'] = true;
      } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Only the safety and facilities officer can modify waiver information.";
      }
      return $toReturn;
    }
    $member_id = mysql_escape_string($_POST['member_id']);
    $completed = mysql_escape_string($_POST['completed']);
    $term = mysql_escape_string($_POST['term']);
    $authRole = $_POST['auth_role'];

    $result = updateWaiver($member_id, $completed, $term, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "getWaiverlessMembers":
    function getWaiverlessMembers($authRole, $when, $link) {
      global $CURRENT_TERM;
      global $CURRENT_START_DATE;
      global $CURRENT_END_DATE;

      $toReturn = [];
      if ($authRole == "SafetyAndFacilities" || $authRole == "Admin") {
        if ($when == 'today') {
          $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) = DATE(NOW())";
        } else {
          $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) BETWEEN DATE('" . $CURRENT_START_DATE . "') AND DATE('" . $CURRENT_END_DATE . "')";
        }
        $checkins = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins in getWaiverlessMembers");

        $memberIds = [];
        foreach ($checkins as $c) {
          $waiverSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $c['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
          $waiverStatusArray = assocArraySelectQuery($waiverSelectQuery, $link, "Failed to select waiver_status in getPresentWaiverlessMembers");
          if ($waiverStatusArray == [] || $waiverStatusArray[0]['completed'] != 1) {
            $memberIds[] = $c['member_id'];
          }
        }

        $memberObjects = [];
        foreach ($memberIds as $id) {
          $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
          $memberArray = assocArraySelectQuery($memberSelectQuery, $link, "Failed to select member in getPresentWaiverlessMembers");
          assert(count($memberArray) == 1);
          $memberObjects[] = $memberArray[0];
        }

        $toReturn['members'] = $memberObjects;
        $toReturn['succeeded'] = true;
      } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Only the safety and facilities officer can modify waiver information.";
      }
      return $toReturn;
    }
    $authRole = $_POST['auth_role'];
    $when = $_POST['when'];

    $result = getWaiverlessMembers($authRole, $when, $link);
    $data = setSucceededAndReason($data, $result);
    if (array_key_exists('members', $result)) {
      $data['members'] = $result['reason'];
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
    function getCompetitionTeamList($term, $dbLink) {
      $membershipSelectQuery = "SELECT * FROM `membership` WHERE `kind`='Competition' AND `term`='" . $term . "'";
      $membershipArray = assocArraySelectQuery($membershipSelectQuery, $dbLink, "Failed to select memberships in getCompetitionTeamList");

      $memberObjects = [];
      foreach($membershipArray as $membership) {
        $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $membership['member_id'] . "'";
        $memberArray = assocArraySelectQuery($memberSelectQuery, $dbLink, "Failed to select member in getPresentWaiverlessMembers");
        assert(count($memberArray) == 1);
        $memberObjects[] = $memberArray[0];
      }

      for ( $i = 0; $i < count($memberObjects); $i++ ) {
        $memberObjects[$i]['balance'] = calculateOutstandingDues($memberObjects[$i]['id'], $dbLink);
      }
      return $memberObjects;
    }
    $data = getCompetitionTeamList($CURRENT_TERM, $link);
    break;

  case "getTransactions":
    function getTransactions($methods, $startDate, $endDate, $dbLink) {
      $methodConditions = "";
      if ( $methods !== null ) {
        foreach($methods as $m) {
          $methodConditions = $methodConditions . " OR `method`='" . mysql_escape_string($m) . "'";
        }
        if ( count($methods) > 0 ) {
          $methodConditions = " AND (0" . $methodConditions . ")";
        }
      }

      $query = "SELECT * FROM `debit_credit` WHERE `date_time` BETWEEN '" . $startDate . "' AND '" . $endDate . "'" . $methodConditions . " ORDER BY `date_time`";
      $transactions = assocArraySelectQuery($query, $dbLink, "Failed to select from debit_credit in getTransactions");

      for ( $i = 0; $i < count($transactions); $i++ ) {
        $query = "SELECT * FROM `member` WHERE `id`='" . $transactions[$i]['member_id'] . "'";
        $memberArray = assocArraySelectQuery($query, $dbLink, "Failed to select member in getTransactions");
        assert(count($memberArray)==1);
        $member = $memberArray[0];
        $transactions[$i]['member_name'] = $member['first_name'] . " " . $member['last_name'];
      }
      return $transactions;
    }
    $methods = array_key_exists('methods', $_POST) ? $_POST['methods'] : null;
    $startDate = array_key_exists('startDate', $_POST) ?  $_POST['startDate'] : $CURRENT_START_DATE;
    $endDate = array_key_exists('endDate', $_POST) ?  $_POST['endDate'] : $CURRENT_END_DATE;
    $data = array("transactions" => getTransactions($methods, $startDate, $endDate, $link));
    break;

  case "getSummaryData":
    function getSummaryData($summary_kind, $day, $dbLink) {
      global $CURRENT_TERM;
      global $CURRENT_START_DATE;
      global $CURRENT_END_DATE;

      // defaults to "term"
      if ($summary_kind == "day") {
        $dateCondition = " DATE(`date_time`) = DATE(" . $day . ")";
      } else if ($summary_kind == "week") {
        $dateCondition = " WEEKOFYEAR(`date_time`)=WEEKOFYEAR(" . $day . ")";
      } else {
        $dateCondition = " DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
      }

      // collect checkins
      $query = "SELECT * FROM `checkin` WHERE" . $dateCondition . " ORDER BY `date_time`";
      $checkins = assocArraySelectQuery($query, $dbLink, "Failed to select checkins in getSummaryData");
      // associate membership with checkin
      for ($i = 0; $i < count($checkins); $i++) {
        $query = "SELECT * FROM `membership` WHERE `member_id`='" . $checkins[$i]['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
        $membershipArray = assocArraySelectQuery($query, $dbLink, "Failed to select membership for checkin in getSummaryData");
        assert(count($membershipArray) < 2);
        $checkins[$i]['membership'] = count($membershipArray) > 0 ? $membershipArray[0]['kind'] : 'None';
        $checkins[$i]['membership_id'] = count($membershipArray) > 0 ? $membershipArray[0]['id'] : 0;
      }

      // collect new memberships, does not include updated memberships
      $query = "SELECT * FROM `membership` WHERE" . $dateCondition;
      $newMemberShips = assocArraySelectQuery($query, $dbLink, "Failed to select memberships in getSummaryData");

      // collect income
      $query = "SELECT * FROM `debit_credit` WHERE (`method`='Cash' OR `method`='Check') AND `amount`>0 AND" . $dateCondition;
      $credits = assocArraySelectQuery($query, $dbLink, "Failed to select credits in getSummaryData");

      return [
          'checkins' => $checkins,
          'newMemberships' => $newMemberShips,
          'credits' => $credits,
      ];
    }
    $summary_kind = $_POST['summary_kind'];
    $day = $_POST['day'] ? "'" . $_POST['day'] . "'" : "NOW()";
    $result = getSummaryData($summary_kind, $day, $link);

    $data['checkins'] = $result['checkins'];
    $data['newMemberships'] = $result['newMemberShips'];
    $data['credits'] = $result['credits'];
    break;

  case "getComplexMembers":
    $thisTerm = $_POST['thisTerm'] ? true : false;
    $data['members'] = array_values(getComplexMembers($thisTerm, $link));
    break;
}


$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>
