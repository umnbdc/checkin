<?php

/**
 * This file is related to authorizing users on the client (they must be authorized in order to make requests)
 *
 * When this file is required, it checks the information in $_POST to authorize the user
 *
 * This file also sets up some helper functions for determining what the user can and cannot access
 */
// from data.php:
//// $data = $_POST;

require "resources/lib/password.php";

/* BEGIN HELPER FUNCTIONS */

function isAuthorized($type, $auth_username, $auth_role, $auth_token, $link) {
  global $link;
  $publicTypes = ["login", "logout"];
  
  $auth_username = mysql_escape_string($auth_username);
  $auth_role = mysql_escape_string($auth_role);
  $auth_token = mysql_escape_string($auth_token);
  
  if ( in_array($_POST['type'],$publicTypes) ) {
    return true;
  }
  
  $selectQuery = "SELECT * FROM `user` WHERE `role`='" . $auth_role . "' AND `username`='" . $auth_username . "'";
  $selectResult = assocArraySelectQuery($selectQuery, $link, "Failed to select user in isAuthorized");
  if ( $selectResult == [] ) {
    return false;
  }
  
  $selectQuery = "SELECT * FROM `auth_token` WHERE `token`='" . $auth_token . "' AND `username`='" . $auth_username . "'";
  $selectResult = assocArraySelectQuery($selectQuery, $link, "Failed to select auth_token in isAuthorized");
  if ( $selectResult == [] ) {
    return false;
  }
  $tokenData = $selectResult[0];
  
  // check to see if it's expired
  if ( time()-strtotime($tokenData['issue_date_time']) > $tokenData['period'] ) {
    $deleteQuery = "DELETE FROM `auth_token` WHERE `token`='" . $auth_token . "' AND `username`='" . $auth_username . "'";
    safeQuery($deleteQuery, $link, "Failed to delete expired auth_token in isAuthorized");
    return false;
  }
  
  return true;
}

// create token, default expiry 4 hours
function generateAuthToken($safeUsername) {
  global $link;
  $token = uniqid(mt_rand(), true);
  $insertQuery = "INSERT INTO `auth_token`(`username`, `token`) VALUES ('" . $safeUsername . "','" . $token . "')";
  safeQuery($insertQuery, $link, "Failed to insert new auth token in generateAuthToken");
  return $token;
}

function returnUnauthorized() {
  header('Content-Type: application/json');
  exit(json_encode(array("unauthorized" => true)));
}

function isVolunteer() {
  return $_POST['auth_role'] == 'Volunteer';
}

if ( !isAuthorized($_POST['type'], $_POST['auth_username'], $_POST['auth_role'], $_POST['auth_token'], $link) ) {
  returnUnauthorized();
}

/* END HELPER FUNCTIONS */
/* BEGIN POST REQUEST HANDLING */

if ( $_POST['type'] == "createUser" ) {
  $data = array("succeeded" => false, "reason" => "");
  
  $username = mysql_escape_string($_POST['username']);
  $role = mysql_escape_string($_POST['role']);
  $passwordHash = mysql_escape_string($_POST['passwordHash']);

  if ( $_POST['auth_role'] == 'Admin' ) {  
    if ( $username == '' || $passwordHash == '' || !in_array($role, array("President", "VicePresident", "Treasurer", "Secretary", "Travel", "SafetyAndFacilities", "Fundraising", "Dance", "Music", "Publicity", "Web", "Volunteer")) ) {
      $data["reason"] = "Invalid fields";
    } else {  
      // confirm no other user with username
      $userSelectQuery = "SELECT * FROM `user` WHERE `username`='" . $username . "'";
      $userSelectResult = assocArraySelectQuery($userSelectQuery, $link, "Failed to select user by username in createUser");
      if ( count($userSelectResult) != 0 ) {
        $data["reason"] = "Username taken";
      } else {
        $salt = uniqid(mt_rand(), true);
        $hash = password_hash($passwordHash, PASSWORD_DEFAULT, ['salt' => $salt]);
        $insertQuery = "INSERT INTO `user`(`username`, `role`, `hash`, `salt`) VALUES ('" . $username . "','" . $role . "','" . $hash . "','" . $salt . "')";
        safeQuery($insertQuery, $link, "Failed to insert new user in createUser");
        $data["succeeded"] = true;
      }
    }
  } else {
    $data["reason"] = "Not authorized to create user";
  }
} else if ( $_POST['type'] == "login" ) {
  $data = array("succeeded" => false, "auth_token" => null);
  
  $username = mysql_escape_string($_POST['username']);
  $passwordHash = mysql_escape_string($_POST['passwordHash']);
    
  $userSelectQuery = "SELECT * FROM `user` WHERE `username`='" . $username . "'";
  $userSelectResult = assocArraySelectQuery($userSelectQuery, $link, "Failed to select user by username in login");
  if ( count($userSelectResult) == 0 ) {
    $data["succeeded"] = false;
  } else {
    $userData = $userSelectResult[0];
    $hash = password_hash($passwordHash, PASSWORD_DEFAULT, ['salt' => $userData['salt']]);
    if ( $hash != $userData['hash'] ) {
      $data["succeeded"] = false;
    } else {
      $data["auth_token"] = generateAuthToken($username);
      $data["auth_role"] = $userData['role'];
      $data["succeeded"] = true;
    }
  }
} else if ( $_POST['type'] == "logout" ) {
  $auth_token = mysql_escape_string($_POST['auth_token']);
  $deleteQuery = "DELETE FROM `auth_token` WHERE `token`='" . $auth_token . "'";
  safeQuery($deleteQuery, $link, "Failed to delete auth_token in logout");
}

if ( $_POST['type'] != "login" ) {
  unset($data['auth_token']);
}

// from data.php:
//// header('Content-Type: application/json');
//// exit(json_encode($data));
?>
