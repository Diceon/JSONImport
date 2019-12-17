<?php

// NOS
ini_set('memory_limit', '-1');
set_time_limit(0);

$baseDIR = "dump/";

// Connect to Database
$DBHostname = "localhost";
$DBUsername = "root";
$DBPassword = "";
$DBDatabase = "stackoverflow";

$db = mysqli_connect($DBHostname, $DBUsername, $DBPassword, $DBDatabase);
if (mysqli_connect_errno()) {
    printf("DB Connect error: %s\n", mysqli_connect_error());
    die();
}


// FNC for date conversion from MSSQL to MySQL
function convert_date($date) {
    preg_match('/(\d{9,10})(\d{3})([\+\-]\d{4})/', $date, $matches);
    $dt = DateTime::createFromFormat("U.u.O", vsprintf('%2$s.%3$s.%4$s', $matches));
    return $dt->format('Y-m-d H:i:s');
}

// FNC for inserting array into DB
function insert_array_single($array, $database, $table) {
    $columns = implode(", ", array_keys($array));
    $escaped_values = array_map(function ($data) use ($database) {
        if (preg_match("~^\/?Date\([0-9]+~", $data)) {
            $data = convert_date($data);
        }
        return $database->real_escape_string($data);
    }, array_values($array));
    $values = "'" . implode("', '", $escaped_values) . "'";
    $query = "INSERT INTO `$table`($columns) VALUES ($values)";
    $res = $database->query($query);
    if (!$res) {
        printf("MySQL Query ERROR: %s", mysqli_error($database));
        die();
    }
}


// Get Files
$files = scandir($baseDIR);

// Slice of '.' & '..' from files
$files = array_slice($files, 2);

// Remove file extensions
$files = array_map(function($file) {
    return preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
}, $files);

// Insert JSON data from each file to DB
foreach ($files as $file) {
    $json = json_decode(file_get_contents("dump/" . $file .".json"), true);

    foreach ($json as $entry) {
        insert_array_single($entry, $db, $file);
    }
}

// No loose ends
$db->close();