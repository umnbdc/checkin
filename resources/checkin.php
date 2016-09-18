<?php

require_once "config.php";
require_once "db.php";
require_once "mailchimp.php";


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
    global $CHECK_IN_PERIOD;
    global $BEGINNER_LESSON_TIME;

    $membershipSelectQuery = "SELECT `kind` FROM `membership` WHERE `member_id`='" . $safeId . "' AND `term`='" . $CURRENT_TERM . "'";
    $membershipArray = assocArraySelectQuery($membershipSelectQuery, $link, "Failed to select membership in memberAllowedToCheckIn");
    assert(count($membershipArray) < 2, "Multiple memberships: (member_id, term) = (". $safeId . ", " . $CURRENT_TERM . ")");

    $toReturn = array( "permitted" => false, "reason" => "" );
    $toReturn['date'] = date("F j, Y: h:iA e");

    if ( count($membershipArray) == 1 ) {
        $kind = $membershipArray[0]['kind'];
        if ( $kind == 'Competition' ) {
            // Comp Team is always allowed
            $toReturn['permitted'] = true;
            $toReturn['reason'] = "Competition Team";
        } else {
            if ( calculateOutstandingDues($safeId) < 0 ) {
                // Not allowed if they have a membership but they haven't paid
                $toReturn['permitted'] = false;
                $toReturn['reason'] = "Outstanding dues";
            } else {
                // Allow if they haven't hit the limit of their checkins per week
                // note: week of year starts monday which is OK for us
                $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND WEEKOFYEAR(`date_time`)=WEEKOFYEAR(NOW())";
                $checkinsThisWeek = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this week in memberAllowedToCheckIn");
                $toReturn['permitted'] = count($checkinsThisWeek) < $CHECKINS_PER_WEEK[$kind];
                $toReturn['reason'] = $kind . " Membership allowed " . $CHECKINS_PER_WEEK[$kind] . " check-ins per week";
            }
        }
    } else {
        // No membership, so limit by number free checkins per semester
        $checkinSelectQuery = "SELECT * FROM `checkin` WHERE `member_id`='" . $safeId . "' AND DATE(`date_time`) BETWEEN '" . $CURRENT_START_DATE . "' AND '" . $CURRENT_END_DATE . "'";
        $checkinsThisTerm = assocArraySelectQuery($checkinSelectQuery, $link, "Failed to select checkins for this term in memberAllowedToCheckIn");
        $toReturn['permitted'] = count($checkinsThisTerm) < $NUMBER_OF_FREE_CHECKINS;
        $toReturn['reason'] = $NUMBER_OF_FREE_CHECKINS . " free check-ins";
    }

    if ($toReturn['permitted'] && $toReturn['reason'] != "Competition Team") {
        $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $safeId . "'";
        $member = assocArraySelectQuery($memberSelectQuery, $link, "Failed to get member in memberAllowedToCheckIn")[0];

        $dayOfWeek = date("w");
        if ($dayOfWeek === '2' || $dayOfWeek === '4') {
            // On Tuesdays and Thursdays, beginners can only check in within a certain time before the beginner lesson starts
            if ($member['proficiency'] == 'Beginner' && (time() + $CHECK_IN_PERIOD * 60) < strtotime($BEGINNER_LESSON_TIME)) {
                $toReturn['permitted'] = false;
                $toReturn['reason'] = "Beginner members may not check in earlier than " . $CHECK_IN_PERIOD . " minutes before the beginner lesson.";
            }
        } else if ($dayOfWeek === '0') {
            // Only advanced members can check in on Sundays
            if ($member['proficiency'] !== 'Advanced') {
                $toReturn['permitted'] = false;
                $toReturn['reason'] = "Only Advanced members may check in on Sundays";
            }
        } else {
            // There are no lessons on other days
            $toReturn['permitted'] = false;
            $toReturn['reason'] = "PHP thinks it is " . date("D, F j, Y: h:iA e") . " right now. Checkin is only allowed on Tuesdays, Thursdays and Sundays, except for comp team members.";
        }
    }
    return $toReturn;
}

function checkinMember($id, $override, $data, $dbLink) {
    $allowedResponse = memberAllowedToCheckIn($id, $dbLink);
    if ( $override || $allowedResponse['permitted'] ) {
        $data['permitted'] = true;
        if ( !checkedInToday($id, $dbLink) ) {
            $insertQuery = "INSERT INTO `checkin`(`member_id`, `date_time`) VALUES ('" . $id . "',CURRENT_TIMESTAMP)";
            safeQuery($insertQuery, $dbLink, "Failed to insert new checkin");
            $data['wasAlreadyCheckedIn'] = false;

            // Get the member info
            $memberSelectQuery = "SELECT * FROM `member` WHERE `id`='" . $id . "'";
            $member = assocArraySelectQuery($memberSelectQuery, $dbLink, "Failed to getMemberInfo member")[0]; // assume only one member with id
            $memberDataForMailchimp = [
                'email' => $member['email'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
            ];

            // Subscribe the user if they are not already subscribed
            $data['mailchimpOutput'] = subscribeToMailchimp($memberDataForMailchimp);
        } else {
            $data['wasAlreadyCheckedIn'] = true;
        }
    } else {
        $data['permitted'] = false;
        $data['permission_reason'] = $allowedResponse['reason'];
    }
    return $data;
}
