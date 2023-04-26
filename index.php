<?php

if (!file_exists("tableNames.php")) {
    header("location: install.php");
}

require("vendor/autoload.php");
require_once("database.php");

$conn = new DataBase($db);

// run a select all query. By default it will select one row. If you want all rows, enter "all" as second parameter
// $all = $conn->query_anytable(TableNames::konaklama, "one");
// print_r($all);

// To run a single conditional query
// $array = ["columnName" => "value"];
// $oneCondition = $conn->conditional_query(TableNames::konaklama, $array);
// print_r($oneCondition);

// To run a multi conditional query
// $array = ["columnName1" => "value1", "columnName2" => 1000];
// $multipleCondition = $conn->conditional_query(TableNames::konaklama, $array);
// print_r($multipleCondition);

// To add row with dynamic values
// $array = ["columnName1" => "value1", "columnName2" => "value2"];
// $conn->insert_into(TableNames::butceler, $array);