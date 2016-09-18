<?php

$MAILCHIMP_API_KEY = "9783f79a384c0b7ecb548bcbd76e827c-us11";
$MAILCHIMP_MAIN_LIST_ID = "dbe206ba93";
$MAILCHIMP_SEMESTER_LIST_ID = "345cd2b625";
$MAILCHIMP_BASE_URL = "https://us11.api.mailchimp.com/3.0/";


function mailchimpCurlRequestHelper($url, $method, $apiKey) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERPWD, "user:" . $apiKey);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    return $curl;
}

function getCurlResult($curl) {
    $result = curl_exec($curl);
    curl_close($curl);
    return is_string($result) ? json_decode($result, true) : $result;
}


/**
 * Attempt to subscribe the member to MailChimp, in case they are not already subscribed
 */
function subscribeToMailchimp($memberData) {
    global $MAILCHIMP_API_KEY;
    global $MAILCHIMP_MAIN_LIST_ID;
    global $MAILCHIMP_SEMESTER_LIST_ID;
    global $MAILCHIMP_BASE_URL;

    function isUserSubscribed($requestURL, $apiKey) {
        // Check if the user is subscribed
        $curl = mailchimpCurlRequestHelper($requestURL, 'GET', $apiKey);
        $curlResult = getCurlResult($curl);
        return [
            'mailchimp_response' => $curlResult,
            'success' => ($curlResult['status'] === 404 || $curlResult['title'] === "Resource Not Found"),
        ];
    }

    function subscribeUser($requestURL, $apiKey, $memberData) {
        $data = json_encode([
            "email_address" => $memberData["email"],
            "status" => "subscribed",
            "merge_fields" => array(
                "FNAME" => $memberData["first_name"],
                "LNAME" => $memberData["last_name"],
                "MMERGE3" => "Student", // Unsure how we can tell if this is a student... assume student
            ),
        ]);

        $curl = mailchimpCurlRequestHelper($requestURL, 'PUT', $apiKey);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        return getCurlResult($curl);
    }

    // Subscribe to the master list and the semester list
    $md5Email = md5(strtolower($memberData["email"]));
    $output = array();
    foreach ([$MAILCHIMP_MAIN_LIST_ID, $MAILCHIMP_SEMESTER_LIST_ID] as $listId) {
        $url = $MAILCHIMP_BASE_URL . "lists/" . $listId . "/members/" . $md5Email;

        try {
            // Check if the user is subscribed
            $result = isUserSubscribed($url, $MAILCHIMP_API_KEY);
            $output[] = $result['mailchimp_response'];

            if ($result['success']) {
                // The user is not subscribed or the list wasn't found. Try subscribing the user
                $output[] = subscribeUser($url, $MAILCHIMP_API_KEY, $memberData);
            }
        } catch (Exception $e) {
            $output[] = "Attempted subscription to list " . $listId . " failed with error: " . $e->getMessage();
        }
    }
    return $output;
}
