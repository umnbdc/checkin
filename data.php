<?php

date_default_timezone_set('America/Chicago');

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
$COMP_DUE_DATE_TABLE = array(
  // term -> fee_status -> date -> min_outstanding_at_date (i.e. cumulative)
  "Spring2015" => array(
    "StudentServicesFees" => array(
      "2015-02-12" => -9000,
      "2015-03-06" => -4500,
      "2015-04-03" => 0
    ),
    "Affiliate" => array(
      "2015-02-12" => 0
    )
  )
);
$LATE_FEE_AMOUNT = 200;
$COMP_PRACTICES_TABLE = array(
  "Spring2015" => array(
    "2015-02-03", "2015-02-05", "2015-02-06", // Feb
    "2015-02-10", "2015-02-12", "2015-02-13",
    "2015-02-17", "2015-02-19", "2015-02-20",
    "2015-02-24", "2015-02-26", "2015-02-27",
    "2015-03-03", "2015-03-05", "2015-03-06", // March
    "2015-03-10", "2015-03-12", "2015-03-13",
    // Spring Break
    "2015-03-24", "2015-03-26", "2015-03-27",
    "2015-03-31",
                  "2015-04-02", "2015-04-03", // April
    "2015-04-07", "2015-04-09", "2015-04-10",
    "2015-04-14", "2015-04-16", "2015-04-17",
    "2015-04-21", "2015-04-23", "2015-04-24"
  )
                        
);

// Safeguard against forgetting to change the current term start and end
if ( strtotime($CURRENT_START_DATE) > strtotime('today') || strtotime($CURRENT_END_DATE) < strtotime('today') ) {
  die("Current term (range) is out of date.");
}

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
  $feeTable['Affiliate']['Competition'] = 6000;
  
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

function generateOrdinalString($number) {
  // http://stackoverflow.com/questions/3109978/php-display-number-with-ordinal-suffix
  $ends = array('th','st','nd','rd','th','th','th','th','th','th');
  if (($number %100) >= 11 && ($number%100) <= 13)
     $abbreviation = $number. 'th';
  else
     $abbreviation = $number. $ends[$number % 10];
  return $abbreviation;
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
  
  $toReturn = array( "permitted" => false, "reason" => "" );
  
  if ( count($membershipArray) == 1 ) {
    $kind = $membershipArray[0]['kind'];
    if ( $kind == 'Competition' ) {
      $toReturn['permitted'] = true;
      $toReturn['reason'] = "Competition Team";
    } else {
      // note: week of year starts monday which is OK for us
      if ( calculateOutstandingDues($safeId) < 0 ) {
        $toReturn['permitted'] = false;
        $toReturn['reason'] = "Outstanding dues";
      } else {
        $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND WEEKOFYEAR(`date_time`)=WEEKOFYEAR(NOW())";
        $checkinsThisWeek = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this week in memberAllowedToCheckIn");
        $toReturn['permitted'] = count($checkinsThisWeek) < $CHECKINS_PER_WEEK[$kind];
        $toReturn['reason'] = $kind . " Membership allowed " . $CHECKINS_PER_WEEK[$kind] . " check-ins per week";
      }
    }
  } else { // no membership
    $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
    $checkinsThisTerm = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this term in memberAllowedToCheckIn");
    $toReturn['permitted'] = count($checkinsThisTerm) < $NUMBER_OF_FREE_CHECKINS;
    $toReturn['reason'] = $NUMBER_OF_FREE_CHECKINS . " free check-ins";
  }
  
  return $toReturn;
}

function hasHadMembership($safeId) {
  global $link;
  
  $selectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $safeId . "'";
  $membershipArray = assocArraySelectQuery($selectQuery, $link, "Failed to select membership in isFirstMembership");
  return $membershipArray != [];
}

// assumes that the member identified by $referrerMemberId
// has just made a referral for which no reward has been generated
function generateRewardPostReferral($referrerMemberId) {
  global $link;
  global $CURRENT_TERM;
  
  // "Bring a Friend" Program
  $referralSelectQuery = "SELECT * FROM `referral` WHERE `referrer_id`='" . $referrerMemberId . "' AND `term`='" . $CURRENT_TERM . "'";
  $referrals = assocArraySelectQuery($referralSelectQuery, $link, "Failed to select referrals in generateRewardPostReferral");
  $refCount = count($referrals);
  $description = generateOrdinalString($refCount) . " referral";
  if ( $refCount < 3 ) {
    // generate $25 reward
    $rewardKind = "Bring a Friend (" . $description . ", $25 membership credit)";
    $rewardInsertQuery = "INSERT INTO `reward`(`member_id`, `kind`, `term`, `claim_date_time`, `claimed`) VALUES ('" . $referrerMemberId . "','" . $rewardKind . "','" . $CURRENT_TERM . "',CURRENT_TIMESTAMP,1)";
    safeQuery($rewardInsertQuery, $link, "Failed to insert reward (Bring a friend, $25) in generateRewardPostReferral");
    $creditKind = "Membership (Bring a Friend, " . $description . ", " . $CURRENT_TERM . ")";
    $creditMethod = "Reward (Bring a Friend, " . $description . ", " . $CURRENT_TERM . ")";
    $creditInsertQuery = "INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`, `method`) VALUES ('" . $referrerMemberId . "',2500,'" . $creditKind . "','" . $creditMethod . "')";
    safeQuery($creditInsertQuery, $link, "Failed to insert reward credit (Bring a friend, $25) in generateRewardPostReferral");
  } else {
    // generate free shoe reward
    $rewardKind = "Bring a Friend (" . $description . ", Free pair of shoes)";
    $rewardInsertQuery = "INSERT INTO `reward`(`member_id`, `kind`, `term`, `claimed`) VALUES ('" . $referrerMemberId . "','" . $rewardKind . "','" . $CURRENT_TERM . "',0)";
    safeQuery($rewardInsertQuery, $link, "Failed to insert reward (Bring a friend, shoes) in generateRewardPostReferral");
  }
}

function generateReferral($safeId) {
  global $link;
  global $CURRENT_TERM;
  
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
      $referralInsertQuery = "INSERT INTO `referral`(`referrer_id`, `referred_id`, `term`) VALUES ('" . $referrerMemberId . "','" . $referredMemberId . "','" . $CURRENT_TERM . "')";
      safeQuery($referralInsertQuery, $link, "Failed to insert new referral");
      
      generateRewardPostReferral($referrerMemberId);
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

$data = $_POST;

include('auth.php');

if ( $_POST['type'] == "environment" ) {
  $data = [];
  $data['CURRENT_TERM'] = $CURRENT_TERM;
} else if ( $_POST['type'] == "newMember" ) {
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
} else if ( $_POST['type'] == "getMemberInfo" ) {
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
} else if ( $_POST['type'] == "checkInMember" ) {
  $id = mysql_escape_string($_POST['id']);
  $override = array_key_exists('override', $_POST) && $_POST['override'] == "true";
  
  $allowedResponse = memberAllowedToCheckIn($id, $link);
  if ( $override || $allowedResponse['permitted'] ) {  
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
    $data['permission_reason'] = $allowedResponse['reason'];
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
  $amount = $points*1200;
  
  if ( $_POST['auth_role'] == "Fundraising" || $_POST['auth_role'] == "Admin" ) {
    insertPayment($member_id, $amount, $method, $kind);
    $data['succeeded'] = true;
  } else {
    $data['succeeded'] = false;
    $data['reason'] = "Only the fundraising officer can add volunteer points.";
  }
} else if ( $_POST['type'] == "updateWaiver" ) {
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
} else if ( $_POST['type'] == "getPresentWaiverlessMembers" ) {

  if ( $_POST['auth_role'] == "SafetyAndFacilities" || $_POST['auth_role'] == "Admin" ) {
    $checkinSelectQuery = "SELECT * FROM `checkin` WHERE DATE(`date_time`) = DATE(NOW())";
    $todaysCheckins = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select today's checkins in getPresentWaiverlessMembers");
  
    $memberIds = [];
    foreach ( $todaysCheckins as $c ) {
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
} else if ( $_POST['type'] == "claimReward" ) {
  $reward = $_POST['reward'];
  $rewardId = mysql_escape_string($reward['id']);
  if ( $reward['claimed'] == '0' ) {
    $updateQuery = "UPDATE `reward` SET `claim_date_time`=CURRENT_TIMESTAMP,`claimed`=1 WHERE `id`='" . $rewardId . "'";
    safeQuery($updateQuery, $link, "Failed to update reward in claimReward");
  }
} else if ( $_POST['type'] == "getCompetitionTeamList" ) {
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
} else if ( $_POST['type'] == "getTransactions" ) {
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
} else if ( $_POST['type'] == "getSummaryData" ) {
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
  $query = "SELECT * FROM `checkin` WHERE" . $dateCondition;
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
}

$link->close();

header('Content-Type: application/json');
exit(json_encode($data));
?>