<?php

require_once "config.php";
require_once "db.php";


function updateWaiver($member_id, $completed, $term, $authRole, $dbLink) {
    $toReturn = [];
    assert($completed == 0 || $completed == 1);
    if ( $authRole == "SafetyAndFacilities" || $authRole == "Admin" ) {
        $deleteQuery = "DELETE FROM `waiver_status` WHERE `member_id`='" . $member_id . "' AND `term`='" . $term . "'";
        safeQuery($deleteQuery, $dbLink, "Failed to delete waiver status in updateWaiver");
        $insertQuery = "INSERT INTO `waiver_status`(`member_id`, `term`, `completed`) VALUES ('" . $member_id . "','" . $term . "','" . $completed . "')";
        safeQuery($insertQuery, $dbLink, "Failed to insert new waiver status in updateWaiver");
        $toReturn['succeeded'] = true;
    } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Only the safety and facilities officer can modify waiver information.";
    }
    return $toReturn;
}


function getWaiverlessMembers($authRole, $when, $link) {
    global $CURRENT_TERM;
    global $CURRENT_START_DATE;
    global $CURRENT_END_DATE;

    $toReturn = [];
    if ($authRole == "SafetyAndFacilities" || $authRole == "Admin") {
        if ($when == 'today') {
            $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) = DATE(NOW())";
        } else {
            $checkinSelectQuery = "SELECT DISTINCT `member_id` FROM `checkin` WHERE DATE(`date_time`) BETWEEN DATE('" . $CURRENT_START_DATE . "') AND DATE('" . $CURRENT_END_DATE . "')";
        }
        $checkins = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins in getWaiverlessMembers");

        $memberIds = [];
        foreach ($checkins as $c) {
            $waiverSelectQuery = "SELECT * FROM `waiver_status` WHERE `member_id`='" . $c['member_id'] . "' AND `term`='" . $CURRENT_TERM . "'";
            $waiverStatusArray = assocArraySelectQuery($waiverSelectQuery, $link, "Failed to select waiver_status in getPresentWaiverlessMembers");
            if ($waiverStatusArray == [] || $waiverStatusArray[0]['completed'] != 1) {
                $memberIds[] = $c['member_id'];
            }
        }

        $memberObjects = [];
        foreach ($memberIds as $id) {
            $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
            $memberArray = assocArraySelectQuery($memberSelectQuery, $link, "Failed to select member in getPresentWaiverlessMembers");
            assert(count($memberArray) == 1);
            $memberObjects[] = $memberArray[0];
        }

        $toReturn['members'] = $memberObjects;
        $toReturn['succeeded'] = true;
    } else {
        $toReturn['succeeded'] = false;
        $toReturn['reason'] = "Only the safety and facilities officer can modify waiver information.";
    }
    return $toReturn;
}
