<?php
// Filepath for training data
$filename = 'trainingdata.txt';

// Check if file exists and load the data
if (file_exists($filename)) {
    $fileContents = file_get_contents($filename);
    $lines = explode("\n", trim($fileContents)); // Split the file into lines
    echo json_encode($lines); // Return as JSON array
} else {
    echo json_encode([]); // Return empty array if no file exists
}
?>
