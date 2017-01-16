<?php

/**
 * This file is spent handling the POST requests sent by the client.
 * The general structure is:
 *  1) Import configurations
 *  2) Create a connection to the MySQL database (in db.php)
 *  3) Verify that the logged-in user on the client is authorized (in auth.php)
 *  4) Import other code that might be used
 *  5) Prevent the system from being used before the Web Coordinator has prepared it for the semester
 *  6) Determine what type of request is being made, and handle it
 *  7) Close off the database link, and send the response back to the client
 */
$data = $_POST;


/** STEP 1
 * Import some configurations
 */
require_once "resources/config.php";


/** STEP 2
 * Connect to the MySQL database (if db.php is imported more than once, things will break)
 * Also imports some helper functions for accessing the database
 */
require_once "resources/db.php";


/** STEP 3
 * Verify that the logged-in user on the client is authorized (in auth.php)
 * Also imports some helper functions for determining what the user can and cannot do
 * NOTE: because db.php must only be called once, (auth.php cannot "require 'db.php';") auth.php MUST be imported after db.php
 */
require_once 'auth.php';


/** STEP 4
 * Import code that might be used (I organized code into files so data.php file isn't miles long. data.php used to have everything...)
 */
require_once 'resources/mailchimp.php';
require_once 'resources/referrals.php';
require_once 'resources/checkins.php';
require_once 'resources/memberInfo.php';
require_once 'resources/fees.php';
require_once 'resources/compteam.php';
require_once 'resources/summaryInfo.php';
require_once 'resources/waivers.php';

// These lines of code kept happening in a lot of places, so I put it in a function so I could avoid repeating myself
function setSucceededAndReason($data, $result) {
  $data['succeeded'] = $result['succeeded'];
  if (array_key_exists('reason', $result)) {
    $data['reason'] = $result['reason'];
  }
  return $data;
}


/** STEP 5
 * Prevent the system from being used before the Web Coordinator has prepared it for the semester
 * Safeguard against forgetting to change the current term start and end (locks you out of login)
 */
if ( strtotime($CURRENT_START_DATE) > strtotime('today') || strtotime($CURRENT_END_DATE) < strtotime('today') ) {
  die("Current term (range) is out of date.");
}


/** STEP 6
 * Determine what type of request is being made, and handle it
 */
switch ( $_POST['type'] ) {
  case "environment":
    // Tell the client what term it is
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
    $data['newMemberships'] = $result['newMemberships'];
    $data['credits'] = $result['credits'];
    break;

  case "getComplexMembers":
    $thisTerm = $_POST['thisTerm'] ? true : false;
    $data['members'] = array_values(getComplexMembers($thisTerm, $link));
    break;
}


/** STEP 7
 * Close off the database link and send the response back to the client
 */
// Close the link to the database
$link->close();

// Send back the response and exit
header('Content-Type: application/json');
exit(json_encode($data));
?>
