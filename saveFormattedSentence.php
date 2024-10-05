<?php
if (isset($_POST['formattedSentence'])) {
    $formattedSentence = $_POST['formattedSentence'];

    // Filepath for formatted training data
    $filename = 'trainingdata_with_images.txt';

    // Attempt to open the file for appending
    $file = fopen($filename, 'a'); // Open the file in append mode
    if ($file) {
        // Use file locking to prevent write conflicts
        if (flock($file, LOCK_EX)) { // Acquire an exclusive lock
            fwrite($file, $formattedSentence . "\n"); // Write the formatted sentence to the file
            fflush($file); // Flush the output to the file
            flock($file, LOCK_UN); // Release the lock
            echo "Formatted sentence saved successfully.";
        } else {
            echo "Could not lock the file for writing.";
        }
        fclose($file);
    } else {
        echo "Could not open the file for writing.";
    }
} else {
    echo "No formatted sentence received.";
}
?>


