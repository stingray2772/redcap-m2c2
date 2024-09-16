<?php
namespace PSU\M2C2;

//display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "loaded page";
die;

// // decode POST data
// $rawData = file_get_contents("php://input");
// $decodedData = json_decode($rawData, true);

// // Check for JSON decoding errors
// $pid = $decodedData['pid'];
// $filename = $decodedData['filename'];
// $file_contents = $decodedData['file_contents'];
// $file_extension = $decodedData['file_extension'];

// // echo data back to screen for debugging
// echo "PID: " . $pid . "<br>";
// echo "Filename: " . $filename . "<br>";
// echo "File Extension: " . $file_extension . "<br>";
// echo "File Contents: " . $file_contents . "<br>";
// die;

// // Create M2C2 object
// $m2c2 = new M2C2();

// // Save file to repository
// $m2c2->saveToFileRepository($filename, $file_contents, $file_extension);