<?php

date_default_timezone_set('America/Chicago');

// Import some configurations
require_once "resources/config.php";
require_once "resources/db.php";

// Handle authorization for accessing this endpoint (if authorization fails, the request will fail)
require_once 'auth.php';

// Require the rest of the code
require_once 'resources/mailchimp.php';
require_once 'resources/referrals.php';
require_once 'resources/checkin.php';
require_once 'resources/memberInfo.php';
require_once 'resources/fees.php';
require_once 'resources/compteam.php';
require_once 'resources/waivers.php';


// Safeguard against forgetting to change the current term start and end
if ( strtotime($CURRENT_START_DATE) > strtotime('today') || strtotime($CURRENT_END_DATE) < strtotime('today') ) {
  die("Current term (range) is out of date.");
}


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

    $result = updateMember($firstName, $nickName, $lastName, $email, $proficiency, $id, $authRole, $link);
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
    $member_id = mysql_escape_string($_POST['member_id']);
    $authRole = $_POST['auth_role'];

    $result = addNewCompMemberDiscount($member_id, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "updateWaiver":
    $member_id = mysql_escape_string($_POST['member_id']);
    $completed = mysql_escape_string($_POST['completed']);
    $term = mysql_escape_string($_POST['term']);
    $authRole = $_POST['auth_role'];

    $result = updateWaiver($member_id, $completed, $term, $authRole, $link);
    $data = setSucceededAndReason($data, $result);
    break;

  case "getWaiverlessMembers":
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
    $data = getCompetitionTeamList($CURRENT_TERM, $link);
    break;

  case "getTransactions":
    $methods = array_key_exists('methods', $_POST) ? $_POST['methods'] : null;
    $startDate = array_key_exists('startDate', $_POST) ?  $_POST['startDate'] : $CURRENT_START_DATE;
    $endDate = array_key_exists('endDate', $_POST) ?  $_POST['endDate'] : $CURRENT_END_DATE;
    $data = array("transactions" => getTransactions($methods, $startDate, $endDate, $link));
    break;

  case "getSummaryData":
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
