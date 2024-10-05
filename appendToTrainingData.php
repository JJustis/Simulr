<?php
if (isset($_POST['newEntry'])) {
    $newEntry = $_POST['newEntry'];

    // Specify the path to the training data file
    $filename = 'trainingdata_with_images.txt';

    // Append the new entry to the file
    if (file_put_contents($filename, $newEntry, FILE_APPEND)) {
        echo "New entry successfully appended to training data file.";
    } else {
        echo "Error appending new entry to training data file.";
    }
} else {
    echo "No data received.";
}
?>
