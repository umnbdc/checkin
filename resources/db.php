<?php
/**
 * Provide an abstraction of the MySQL accessing tool, with some helpful functions for accessing data
 * Also, create a single connection to the database that will be used anywhere we need the db
 *
 * NOTE: this file should only be imported ONCE because it creates the database link. Otherwise things will break
 */

/**
 * Convert the results returned (retrieved using the MySQL library we use) to a more usable format
 * @param $result mysqli_result for SELECT queries, or boolean (true indicates success)
 * @return array where each element is an entry in the database with the fields that were requested in SELECT etc.
 */
function resultToAssocArray($result) {
    $rows = array();
    while($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
    return $rows;
}

/**
 * In general, use this when you don't want to use any data retrieved (otherwise use assocArraySelectQuery)
 *
 * This is "safe" by killing the request with an error message if the query fails
 *
 * @param $query string text of the MySQL query
 * @param $link mysqli a connection to the database (created once per server request and reused if necessary)
 * @param $errorMessage string the message to display in case something goes wrong (for debugging)
 * @return mysqli_result for SELECT queries, or boolean (true indicates success)
 */
function safeQuery($query, $link, $errorMessage) {
    $result = $link->query($query);
    if ( !$result ) {
        die($errorMessage);
    }
    return $result;
}

/**
 * In general, use this when you want to use the data retrieved from the database
 *
 * Call safeQuery to get the results of a query,
 * and then get the results of a query in a more usable format
 *
 * @param $query string text of the MySQL query
 * @param $link mysqli a connection to the database (created once per server request and reused if necessary)
 * @param $errorMessage string the message to display in case something goes wrong (for debugging)
 * @return array where each element is an (assoc key to value) entry in the database with the SELECTed fields
 */
function assocArraySelectQuery($query, $link, $errorMessage) {
    return resultToAssocArray(safeQuery($query, $link, $errorMessage));
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
