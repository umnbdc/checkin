<?php

function resultToArray($result) {
  $rows = array();
  while($r = mysqli_fetch_assoc($result)) {
      $rows[] = $r;
  }
  return $rows;
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
    $data['member'] = resultToArray($result)[0];
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
}

$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>