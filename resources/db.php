<?php

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

// Create connection
$servername = "localhost";
$username = "adminPmvkzYa";
$password = "qRK-zD3sIbU9";
$dbname = "php";
$link = new mysqli($servername, $username, $password, $dbname);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}
