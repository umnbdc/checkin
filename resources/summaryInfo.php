<?php

require_once "config.php";
require_once "db.php";


function getSummaryData($summary_kind, $day, $dbLink) {
    global $CURRENT_TERM;
    global $CURRENT_START_DATE;
    global $CURRENT_END_DATE;

    // defaults to "term"
    if ($summary_kind == "day") {
        $dateCondition = " DATE(`date_time`) = DATE(" . $day . ")";
    } else if ($summary_kind == "week") {
        $dateCondition = " WEEKOFYEAR(`date_time`)=WEEKOFYEAR(" . $day . ")";
    } else {
        $dateCondition = " DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
    }

    // collect checkins
    $query = "SELECT * FROM `checkin` WHERE" . $dateCondition . " ORDER BY `date_time`";
    $checkins = assocArraySelectQuery($query, $dbLink, "Failed to select checkins in getSummaryData");
    // associate membership with checkin
    for ($i = 0; $i < count($checkins); $i++) {
        $query = "SELECT * FROM `membership` WHERE `member_id`='" . $checkins[$i]['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
        $membershipArray = assocArraySelectQuery($query, $dbLink, "Failed to select membership for checkin in getSummaryData");
        assert(count($membershipArray) < 2);
        $checkins[$i]['membership'] = count($membershipArray) > 0 ? $membershipArray[0]['kind'] : 'None';
        $checkins[$i]['membership_id'] = count($membershipArray) > 0 ? $membershipArray[0]['id'] : 0;
    }

    // collect new memberships, does not include updated memberships
    $query = "SELECT * FROM `membership` WHERE" . $dateCondition;
    $newMemberShips = assocArraySelectQuery($query, $dbLink, "Failed to select memberships in getSummaryData");

    // collect income
    $query = "SELECT * FROM `debit_credit` WHERE (`method`='Cash' OR `method`='Check') AND `amount`>0 AND" . $dateCondition;
    $credits = assocArraySelectQuery($query, $dbLink, "Failed to select credits in getSummaryData");

    return [
        'checkins' => $checkins,
        'newMemberships' => $newMemberShips,
        'credits' => $credits,
    ];
}


function getTransactions($methods, $startDate, $endDate, $dbLink) {
    $methodConditions = "";
    if ( $methods !== null ) {
        foreach($methods as $m) {
            $methodConditions = $methodConditions . " OR `method`='" . mysql_escape_string($m) . "'";
        }
        if ( count($methods) > 0 ) {
            $methodConditions = " AND (0" . $methodConditions . ")";
        }
    }

    $query = "SELECT * FROM `debit_credit` WHERE `date_time` BETWEEN '" . $startDate . "' AND '" . $endDate . "'" . $methodConditions . " ORDER BY `date_time`";
    $transactions = assocArraySelectQuery($query, $dbLink, "Failed to select from debit_credit in getTransactions");

    for ( $i = 0; $i < count($transactions); $i++ ) {
        $query = "SELECT * FROM `member` WHERE `id`='" . $transactions[$i]['member_id'] . "'";
        $memberArray = assocArraySelectQuery($query, $dbLink, "Failed to select member in getTransactions");
        assert(count($memberArray)==1);
        $member = $memberArray[0];
        $transactions[$i]['member_name'] = $member['first_name'] . " " . $member['last_name'];
    }
    return $transactions;
}
