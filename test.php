<?php

// Create connection
$servername = "localhost";
$username = "adminPmvkzYa";
$password = "qRK-zD3sIbU9";
$dbname = "php";
$link = new mysqli($servername, $username, $password, $dbname);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
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
    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    $data = $rows[0];
  }
} else if ( $_POST['type'] == "getMembers" ) {
  $query = "%" . mysql_escape_string($_POST['query']) . "%";
  
  $selectQuery = "SELECT * FROM `member` WHERE `first_name` LIKE '" . $query . "' OR `last_name` LIKE '" . $query . "' OR `nick_name` LIKE '" . $query . "' ORDER BY `last_name`";
  $result = $link->query($selectQuery);
  if ( !$result ) {
    die("Failed to search members");
  } else {
    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    $data = $rows;
  }
} else if ( $_POST['type'] == "getMemberInfo" ) {
  $id = mysql_escape_string($_POST['id']);
  
  // Get all object related to member id
}

$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>