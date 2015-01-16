<?php

function formatCSVCell($s) {
  return "\"" . str_replace("\"", "\"\"", $s) . "\"";
}

// assume $_POST['csv_data'] is a JSON string representing an array of objects
$objects = json_decode($_POST['objects']);
$keys = json_decode($_POST['keys']);

$csvString = "";

// add header row
foreach($keys as $key) {
  $csvString = $csvString . formatCSVCell($key) . ",";
}

foreach($objects as $obj) {
  $rowString = "";
  foreach($keys as $key) {
    $rowString = $rowString . formatCSVCell($obj->$key) . ",";
  }
  $csvString = $csvString . "\n" . $rowString;
}

header('Content-Type: application/csv');
header('Content-Disposition: attachement; filename="transactions.csv"');
exit($csvString);

?>
