<?php

// Environment
$CURRENT_TERM = "Spring2015";
$CURRENT_START_DATE = "2015-01-01";
$CURRENT_END_DATE = "2015-05-31";
$CHECKINS_PER_WEEK = array(
  "Single" => 1,
  "Standard" => 2,
  "Social" => 2,
  "Competition" => INF,
  "Summer" => INF,
);
$NUMBER_OF_FREE_CHECKINS = 2;

// return positive integer, number of cents
function calculateDues($membership, $feeStatus, $term) {
  $feeTable = [];
  
  $feeTable['StudentServicesFees'] = [];
  $feeTable['StudentServicesFees']['Standard'] = 5000;
  $feeTable['StudentServicesFees']['Single'] = 2500;
  $feeTable['StudentServicesFees']['Social'] = 1500;
  $feeTable['StudentServicesFees']['Competition'] = 20000;
  
  $feeTable['URCMembership'] = [];
  $feeTable['URCMembership']['Standard'] = 6000;
  $feeTable['URCMembership']['Single'] = 3000;
  $feeTable['URCMembership']['Social'] = 1800;
  $feeTable['URCMembership']['Competition'] = 20000;
  
  $feeTable['Affiliate'] = [];
  $feeTable['Affiliate']['Competition'] = 5000;
  
  // Summer membership/feeStatus should only be available in during summer terms
  if ( strpos($term, "Summer") === 0 ) {
    $feeTable['Summer'] = [];
    $feeTable['Summer']['Summer'] = 0;
  }
  
  if (array_key_exists($feeStatus, $feeTable) && array_key_exists($membership, $feeTable[$feeStatus])) {
    return $feeTable[$feeStatus][$membership];
  } else {
    die("membership-feeStatus-term combination is invalid");
  }
}

function createMembershipDueKind($membership, $feeStatus, $term) {
  return "Membership (" . $membership . ", " . $feeStatus . ", " . $term . ")";
}

function resultToAssocArray($result) {
  $rows = array();
  while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
  }
  return $rows;
}

function safeQuery($query, $link, $errorMessage) {
  $result = $link->query($query);
  if ( !$result ) {
    die($errorMessage);
  }
  return $result;
}

function assocArraySelectQuery($query, $link, $errorMessage) {
  return resultToAssocArray(safeQuery($query, $link, $errorMessage));
}

// Create connection
$servername = "localhost";
$username = "adminPmvkzYa";
$password = "qRK-zD3sIbU9";
$dbname = "php";
$link = new mysqli($servername, $username, $password, $dbname);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

function checkedInToday($safeId, $link) {
  $selectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND DATE(`date_time`) = DATE(NOW())";
  $checkins = assocArraySelectQuery($selectQuery, $link, "Failed to select from checkin");
  return $checkins != [];
}

function memberAllowedToCheckIn($safeId, $link) {
  global $CURRENT_TERM;
  global $CURRENT_START_DATE;
  global $CURRENT_END_DATE;
  global $CHECKINS_PER_WEEK;
  global $NUMBER_OF_FREE_CHECKINS;
  
  $membershipSelectQuery = "SELECT `kind` FROM `membership` WHERE `member_id`='" . $safeId . "' AND `term`='" . $CURRENT_TERM . "'";
  $membershipArray = assocArraySelectQuery($membershipSelectQuery, $link, "Failed to select membership in memberAllowedToCheckIn");
  assert(count($membershipArray) < 2, "Multiple memberships: (member_id, term) = (". $safeId . ", " . $CURRENT_TERM . ")");
  
  if ( count($membershipArray) == 1 ) {
    $kind = $membershipArray[0]['kind'];
    if ( $kind == 'Competition' ) {
      return true;
    } else {
      // note: week of year starts monday which is OK for us
      $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND WEEKOFYEAR(`date_time`)=WEEKOFYEAR(NOW())";
      $checkinsThisWeek = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this week in memberAllowedToCheckIn");
      return count($checkinsThisWeek) < $CHECKINS_PER_WEEK;
    }
  } else { // no membership
    $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
    $checkinsThisTerm = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this term in memberAllowedToCheckIn");
    return count($checkinsThisTerm) < $NUMBER_OF_FREE_CHECKINS;
  }
}

function hasHadMembership($safeId) {
  global $link;
  
  $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safeId . "'";
  $membershipArray = assocArraySelectQuery($selectQuery, $link, "Failed to select membership in isFirstMembership");
  return $membershipArray != [];
}

function generateReferral($safeId) {
  global $link;
  
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
      $referralInsertQuery = "INSERT INTO `referral`(`referrer_id`, `referred_id`) VALUES ('" . $referrerMemberId . "','" . $referredMemberId . "')";
      safeQuery($referralInsertQuery, $link, "Failed to insert new referral");
      // TODO run rewards
    }
  }
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

$data = $_POST;

if ( $_POST['type'] == "environment" ) {
  $data = [];
  $data['CURRENT_TERM'] = $CURRENT_TERM;
} else if ( $_POST['type'] == "newMember" ) {
  $member = $_POST['member'];
  
  $insertQuery = sprintf("INSERT INTO `member`(`first_name`, `last_name`, `nick_name`, `email`, `join_date`, `referred_by`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP,%s)",
      "'" . mysql_escape_string($member['firstName']) . "'",
      "'" . mysql_escape_string($member['lastName']) . "'",
      $member['nickname'] ? "'" . mysql_escape_string($member['nickname']) . "'" : 'NULL',
      "'" . mysql_escape_string($member['email']) . "'",
      $member['referredBy'] ? mysql_escape_string($member['referredBy']) : 'NULL');
  safeQuery($insertQuery, $link, "Failed to insert new member");
  
  $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $member['email']);
  $data = assocArraySelectQuery($selectQuery, $link, "Failed to fetch the new member");
} else if ( $_POST['type'] == "updateMember" ) {
  $id = mysql_escape_string($_POST['id']);
  $firstName = mysql_escape_string($_POST['firstName']);
  $lastName = mysql_escape_string($_POST['lastName']);
  $nickName = mysql_escape_string($_POST['nickName']);
  $email = mysql_escape_string($_POST['email']);
  
  $updateQuery = "UPDATE `member` SET `first_name`='" . $firstName . "',`nick_name`='" . $nickName . "',`last_name`='" . $lastName . "',`email`='" . $email . "' WHERE `id`='" . $id . "'";
  $data['updateQuery'] = $updateQuery;
  safeQuery($updateQuery, $link, "Failed to update member info");
} else if ( $_POST['type'] == "getMembers" ) {
  $likeConditions = "";
  $searchTermParts = explode(" ", $_POST['query']);
  foreach ($searchTermParts as $s) {
    $s = "%" . mysql_escape_string($s) . "%";
    $likeConditions = $linkConditions . " LIKE '" . $s . "' OR `last_name` LIKE '" . $s . "' OR `nick_name` LIKE '" . $s . "'";
  }
    
  $selectQuery = "SELECT * FROM `member` WHERE `first_name`" . $likeConditions . " ORDER BY `last_name`";
  $data = assocArraySelectQuery($selectQuery, $link, "Failed to search members");
} else if ( $_POST['type'] == "getMemberInfo" ) {
  $id = mysql_escape_string($_POST['id']);
  
  $data = [];
  
  $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
  $data['member'] = assocArraySelectQuery($memberSelectQuery, $link, "Failed to getMemberInfo member")[0]; // assume only one member with id
  
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
  $data['references'] = assocArraySelectQuery($referralSelectQuery, $link, "Failed to getMemberInfo referral");
} else if ( $_POST['type'] == "checkInMember" ) {
  $id = mysql_escape_string($_POST['id']);
  $override = array_key_exists('override', $_POST) && $_POST['override'] == "true";
  
  if ( $override || memberAllowedToCheckIn($id, $link) ) {  
    $data['permitted'] = true;
    if ( !checkedInToday($id, $link) ) {
      $insertQuery = "INSERT INTO `checkin`(`member_id`, `date_time`) VALUES ('" . $id . "',CURRENT_TIMESTAMP)";
      safeQuery($insertQuery, $link, "Failed to insert new checkin");
      $data['wasAlreadyCheckedIn'] = false;
    } else {
      $data['wasAlreadyCheckedIn'] = true;
    }
  } else {
    $data['permitted'] = false;
  }
} else if ( $_POST['type'] == "checkedIn?" ) {
  $id = mysql_escape_string($_POST['id']);
  $data['checkedIn'] = checkedInToday($id, $link);
} else if ( $_POST['type'] == "updateMembershipAndFeeStatus" ) {
  $id = mysql_escape_string($_POST['id']);
  $feeStatus = mysql_escape_string($_POST['feeStatus']);
  $membership = mysql_escape_string($_POST['membership']);
  $term = mysql_escape_string($_POST['term']);
  
  $data = [];
  
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
} else if ( $_POST['type'] == "payment" ) {
  $member_id = mysql_escape_string($_POST['member_id']);
  $kind = mysql_escape_string($_POST['kind']);
  $method = mysql_escape_string($_POST['method']);
  $amount = mysql_escape_string($_POST['amount']); // should be in cents
  
  insertPayment($member_id, $amount, $method, $kind);
} else if ( $_POST['type'] == "addVolunteerPoints" ) {
  $member_id = mysql_escape_string($_POST['member_id']);
  $points = mysql_escape_string($_POST['points']);
  
  $method = "VolunteerPoints";
  $kind = "Membership (VolunteerPoints x " . $points . ")";
  $amount = $points*600;
  
  insertPayment($member_id, $amount, $method, $kind);
} else if ( $_POST['type'] == "updateWaiver" ) {
  $member_id = mysql_escape_string($_POST['member_id']);
  $completed = mysql_escape_string($_POST['completed']);
  $term = mysql_escape_string($_POST['term']);
  assert($completed == 0 || $completed == 1);
  
  $deleteQuery = "DELETE FROM `waiver_status` WHERE `member_id`='" . $member_id . "' AND `term`='" . $term . "'";
  safeQuery($deleteQuery, $link, "Failed to delete waiver status in updateWaiver");
  $insertQuery = "INSERT INTO `waiver_status`(`member_id`, `term`, `completed`) VALUES ('" . $member_id . "','" . $term . "','" . $completed . "')";
  safeQuery($insertQuery, $link, "Failed to insert new waiver status in updateWaiver");
}

$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>