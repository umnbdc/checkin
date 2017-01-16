<?php

require_once 'config.php';
require_once 'db.php';
require_once 'memberInfo.php';
require_once 'referrals.php';

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
function calculateOutstandingDues($safe_member_id, $dbLink) {
    $selectQuery = "SELECT * FROM `debit_credit` WHERE `member_id`='" . $safe_member_id . "' AND `kind` LIKE 'Membership%'";
    $transactions = assocArraySelectQuery($selectQuery, $dbLink, "Failed to select debit_credit from calculateOutstandingDues");

    $balance = 0;
    foreach( $transactions as $t ) {
        $balance += $t['amount'];
    }
    return $balance;
}


function insertPayment($member_id, $amount, $method, $kind, $dbLink) {
    // assumes that the parameters are safe
    $insertQuery = sprintf("INSERT INTO `debit_credit`(`member_id`, `amount`, `method`, `kind`, `date_time`) VALUES (%s,%s,%s,%s,CURRENT_TIMESTAMP)",
        "'" . $member_id . "'",
        "'" . $amount . "'",
        "'" . $method . "'",
        "'" . $kind . "'");
    safeQuery($insertQuery, $dbLink, "Failed to insert new payment");
}


function getFeeStatus($safe_member_id, $term, $dbLink) {
    $selectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $safe_member_id . "' AND `term`='" . $term . "'";
    $feeStatusArray = assocArraySelectQuery($selectQuery, $dbLink, "Failed to select fee status in getMembershipAndFeeStatus");
    return ( $feeStatusArray != [] )
        ? $feeStatusArray[0]['kind']
        : '';
}


function doPurchase($member_id, $kind, $method, $authRole, $dbLink) {
    global $PURCHASE_TABLE;
    $toReturn = [];
    if ( $authRole == 'Volunteer' ) {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Volunteers cannot process purchases.";
    } else if ( array_key_exists($kind, $PURCHASE_TABLE) ) {
        $amount = $PURCHASE_TABLE[$kind];
        insertPayment($member_id, $amount, $method, $kind, $dbLink); // credit
        insertPayment($member_id, -1*$amount, "", $kind, $dbLink); // debit
        $toReturn['succeeded'] = true;
    } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = 'Cost of item "' . $kind . '" could not be found.';
    }
    return $toReturn;
}


function doPayment($member_id, $kind, $method, $amount, $authRole, $dbLink) {
    $toReturn = [];
    $authorized = $authRole == "President" || $authRole == "Treasurer" || $authRole == "Admin";
    if ( $authRole == 'Volunteer' ) {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Volunteers cannot process payments.";
    } else if ( $method == "Cash" || $method == "Check" || ($authorized && $method == "Forgiveness") ) {
        if ($method == "Forgiveness") {
            $method = $method . " (" . $authRole . ")";
        }
        insertPayment($member_id, $amount, $method, $kind, $dbLink);
        $toReturn['succeeded'] = true;
    } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Payment method not accepted";
    }
    return $toReturn;
}


function updateMembershipAndFeeStatus($authRole, $membership, $id, $feeStatus, $term, $dbLink) {
    $newData = [];

    if ($authRole == 'Volunteer' && $membership == 'Competition') {
        $newData['succeeded'] = false;
        $newData['reason'] = 'Volunteers cannot assign competition team membership';
        return $newData;
    } else {
        $oldFeeStatus = '';
        $oldMembership = '';

        // STEP 1: Determine if this is the member's first membership (if so and the membership is paid, reward referrer later)

        // we don't want to generate the referral before in case something fails
        // but we need to check for previous membership before memberships are inserted into the DB
        // also only generate referral for paid memberships
        // Note:
        // Also if the new combination is invalid, it will error out before DB changes are made
        $generateReferralAtEnd = !hasHadMembership($id, $dbLink) && calculateDues($membership, $feeStatus, $term) > 0;

        // STEP 2: modify the `fee_status` table if necessary

        // Determine current fee status for this semester
        $feeStatusSelectQuery = "SELECT * FROM `fee_status` WHERE `member_id`='" . $id . "' AND `term`='" . $term . "'";
        $feeStatusResult = assocArraySelectQuery($feeStatusSelectQuery, $dbLink, "Failed to select fee status");

        // Update or insert new fee status
        if ($feeStatusResult) {
            $fee_status_id = $feeStatusResult[0]['id'];
            $oldFeeStatus = $feeStatusResult[0]['kind'];
            $feeStatusUpdateQuery = "UPDATE `fee_status` SET `kind`='" . $feeStatus . "' WHERE `id`='" . $fee_status_id . "'";
            safeQuery($feeStatusUpdateQuery, $dbLink, "Failed to update new fee status");
        } else {
            $feeStatusInsertQuery = sprintf("INSERT INTO `fee_status`(`member_id`, `term`, `kind`) VALUES (%s,%s,%s)",
                "'" . $id . "'",
                "'" . $term . "'",
                "'" . $feeStatus . "'");
            safeQuery($feeStatusInsertQuery, $dbLink, "Failed to insert new fee status");
        }

        // STEP 3: modify the `membership` table if necessary

        // Get the member's membership for the semester, if any
        $membershipSelectQuery = "SELECT * FROM `membership` WHERE `member_id`='" . $id . "' AND `term`='" . $term . "'";
        $membershipResult = assocArraySelectQuery($membershipSelectQuery, $dbLink, "Failed to select membership");

        // Update/insert membership
        if ($membershipResult) {
            $membership_id = $membershipResult[0]['id'];
            $oldMembership = $membershipResult[0]['kind'];
            $membershipUpdateQuery = "UPDATE `membership` SET `kind`='" . $membership . "' WHERE `id`='" . $membership_id . "'";
            safeQuery($membershipUpdateQuery, $dbLink, "Failed to update new membership");
        } else {
            $membershipInsertQuery = sprintf("INSERT INTO `membership`(`member_id`, `term`, `kind`) VALUES (%s,%s,%s)",
                "'" . $id . "'",
                "'" . $term . "'",
                "'" . $membership . "'");
            safeQuery($membershipInsertQuery, $dbLink, "Failed to insert new membership");
        }

        // STEP 4: modify the `debit_credit` table if necessary

        // Update dues if necessary
        if ($oldFeeStatus != $feeStatus || $oldMembership != $membership) {
            // Check for (and delete) old feeStatus-membership debit
            if ($oldFeeStatus && $oldMembership) {
                // Note, could be done less precisely using LIKE keyword to wildcard membership and feeStatus
                $oldDueKind = createMembershipDueKind($oldMembership, $oldFeeStatus, $term);
                $duesDeleteQuery = "DELETE FROM `debit_credit` WHERE `member_id`='" . $id . "' AND `kind`='" . $oldDueKind . "'";
                safeQuery($duesDeleteQuery, $dbLink, "Failed to delete old membership debit");
                $newData['duesDeleteQuery'] = $duesDeleteQuery;
            }

            // Add new dues debit
            $newDueKind = createMembershipDueKind($membership, $feeStatus, $term);
            $amount = -1 * calculateDues($membership, $feeStatus, $term);
            $duesInsertQuery = sprintf("INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`, `date_time`) VALUES (%s,%s,%s,CURRENT_TIMESTAMP)",
                "'" . $id . "'",
                "'" . $amount . "'",
                "'" . $newDueKind . "'");
            safeQuery($duesInsertQuery, $dbLink, "Failed to insert new membership debit");

            // Possibly reward this member for previous referrals (using the system started in Fall2016-Spring2017)
            claimReferralRewards($id, $amount * -1);
        }

        // STEP 5: If this member has never had a membership, reward whoever referred them
        if ($generateReferralAtEnd) {
            generateReferral($id);
        }

        $newData['oldFeeStatus'] = $oldFeeStatus;
        $newData['oldMembership'] = $oldMembership;
        $newData['newFeeStatus'] = $feeStatus;
        $newData['newMembership'] = $membership;
        $newData['succeeded'] = true;
        return $newData;
    }
}
