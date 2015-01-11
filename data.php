<?php

// return positive integer, number of cents
function calculateDues($membership, $feeStatus, $term) {
  $feeTable = [];
  
  $feeTable['StudentServicesFees'] = [];
  $feeTable['StudentServicesFees']['Standard'] = 5000;
  $feeTable['StudentServicesFees']['Single'] = 2500;
  $feeTable['StudentServicesFees']['Social'] = 1500;
  $feeTable['StudentServicesFees']['Competition'] = 14000;
  
  $feeTable['URCMembership'] = [];
  $feeTable['URCMembership']['Standard'] = 6000;
  $feeTable['URCMembership']['Single'] = 3000;
  $feeTable['URCMembership']['Social'] = 1800;
  $feeTable['URCMembership']['Competition'] = 14000;
  
  $feeTable['Affiliate'] = [];
  $feeTable['Affiliate']['Competition'] = 5000;
  
  $feeTable['Summer'] = [];
  $feeTable['Summer']['Summer'] = 0;
  
  return $feeTable[$feeStatus][$membership];
}

function createMembershipDueKind($membership, $feeStatus, $term) {
  return "Membership (" . $membership . ", " . $feeStatus . ", " . $term . ")";
}

function resultToArray($result) {
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
  return resultToArray(safeQuery($query, $link, $errorMessage));
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
  $result = $link->query($selectQuery);
  if ( !$result ) {
    die("Failed to select from checkin");
  } else {
    $checkins = resultToArray($result);
  }
  return $checkins != [];
}

$data = $_POST;

if ( $_POST['type'] == "newMember" ) {
  $member = $_POST['member'];
  
  $insertQuery = sprintf("INSERT INTO `member`(`first_name`, `last_name`, `nick_name`, `email`, `join_date`, `referred_by`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP,%s)",
      "'" . mysql_escape_string($member['firstName']) . "'",
      "'" . mysql_escape_string($member['lastName']) . "'",
      $member['nickname'] ? "'" . mysql_escape_string($member['nickname']) . "'" : 'NULL',
      "'" . mysql_escape_string($member['email']) . "'",
      $member['referredBy'] ? mysql_escape_string($member['referredBy']) : 'NULL');
      
  $result = $link->query($insertQuery);
  if ( !$result ) {
    die("Failed to insert new member");
  }
  
  $selectQuery = sprintf("SELECT * FROM `member` WHERE `email`='%s'", $member['email']);
  $result = $link->query($selectQuery);
  if ( !$result ) {
    die("Failed to fetch the new member");
  } else {
    $data = resultToArray($result);
  }
} else if ( $_POST['type'] == "getMembers" ) {
  $query = "%" . mysql_escape_string($_POST['query']) . "%";
  
  $selectQuery = "SELECT * FROM `member` WHERE `first_name` LIKE '" . $query . "' OR `last_name` LIKE '" . $query . "' OR `nick_name` LIKE '" . $query . "' ORDER BY `last_name`";
  $result = $link->query($selectQuery);
  if ( !$result ) {
    die("Failed to search members");
  } else {
    $data = resultToArray($result);
  }
} else if ( $_POST['type'] == "getMemberInfo" ) {
  $id = mysql_escape_string($_POST['id']);
  
  $data = [];
  
  $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
  $result = $link->query($memberSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo member");
  } else {
    $data['member'] = resultToArray($result)[0]; // assume only one member with id
  }
  
  $membershipSelectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $id . "'";
  $result = $link->query($membershipSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo membership");
  } else {
    $data['memberships'] = resultToArray($result);
  }
  
  $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
  $result = $link->query($checkinSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo checkin");
  } else {
    $data['checkIns'] = resultToArray($result);
  }
  
  $debitCreditSelectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $id . "' ORDER BY `date_time`";
  $result = $link->query($debitCreditSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo debit/credit");
  } else {
    $data['debitCredits'] = resultToArray($result);
  }
  
  $feeStatusSelectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $id . "'";
  $result = $link->query($feeStatusSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo fee status");
  } else {
    $data['feeStatus'] = resultToArray($result);
  }
  
  $waiverStatusSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $id . "'";
  $result = $link->query($waiverStatusSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo waiver status");
  } else {
    $data['waiverStatus'] = resultToArray($result);
  }
  
  $referralSelectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $id . "'";
  $result = $link->query($referralSelectQuery);
  if ( !$result ) {
    die("Failed to getMemberInfo referral");
  } else {
    $data['references'] = resultToArray($result);
  }
} else if ( $_POST['type'] == "checkInMember" ) {
  $id = mysql_escape_string($_POST['id']);
  
  if ( !checkedInToday($id, $link) ) {
    $insertQuery = "INSERT INTO `checkin`(`member_id`, `date_time`) VALUES ('" . $id . "',CURRENT_TIMESTAMP)";
    $result = $link->query($insertQuery);
    if ( !$result ) {
      die("Failed to insert new checkin");
    }
    $data['wasAlreadyCheckedIn'] = false;
  } else {
    $data['wasAlreadyCheckedIn'] = true;
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
  
  // TODO Handle Referral Rewards
}

$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>