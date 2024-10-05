<?php
if (isset($_POST['expandedSentence'])) {
    $expandedSentence = $_POST['expandedSentence'];

    // Filepath for expanded training data
    $filename = 'expanded_training_data.txt';

    // Save the expanded sentence to the file, appending it to the end of the file
    file_put_contents($filename, $expandedSentence . "\n", FILE_APPEND);

    echo "Expanded sentence saved successfully.";
} else {
    echo "No expanded sentence received.";
}
?>
