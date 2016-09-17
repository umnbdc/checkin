<?php

require_once "config.php";


function updateCompetitionLateFees($safe_member_id, $term, $dbLink) {
    global $COMP_DUE_DATE_TABLE;
    global $COMP_PRACTICES_TABLE;
    global $LATE_FEE_AMOUNT;

    $membership = getMembership($safe_member_id, $term, $dbLink);
    $fee_status = getFeeStatus($safe_member_id, $term, $dbLink);
    if ( $membership != 'Competition' ) {
        return;
    }

    $balance = calculateOutstandingDues($safe_member_id, $dbLink);

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
            safeQuery($debitDeleteQuery, $dbLink, "Failed to delete old late fee debit in updateCompetitionLateFees");

            $kind = $kindPartial . ", " . $practicesLate . " practices late)";
            $amount = -1 * $LATE_FEE_AMOUNT * $practicesLate;
            $debitInsertQuery = "INSERT INTO `debit_credit`(`member_id`, `amount`, `kind`) VALUES ('" . $safe_member_id . "','" . $amount . "','" . $kind . "')";
            safeQuery($debitInsertQuery, $dbLink, "Failed to insert new late fee debit in updateCompetitionLateFees");
        }
    }
}


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
