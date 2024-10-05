<?php
// Database connection details
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "reservesphp"; // Replace with your database name

// Create connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve words and definitions from the `word` table
$sql = "SELECT word, definition FROM word";
$result = $conn->query($sql);

// Prepare to store word pairs in an array
$word_pairs = [];

// Check if there are any rows returned from the database
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $word = $row['word'];
        $definition = $row['definition'];

        // Extract words that have 5 or more characters from the definition
        preg_match_all('/\b\w{5,}\b/', $definition, $matches);
        $long_words = $matches[0]; // Array of words 5 or more characters long

        // Ensure there are at least 2 long words in the definition
        if (count($long_words) >= 2) {
            // Use a frequency-based method to find the most common co-occurring word
            $similar_word = find_most_frequent_word($long_words);

            // Store the word pair in the array as "word - similar_word"
            $word_pairs[] = "$word - $similar_word";
        }
    }
}

// Write the word pairs to the wordlist.txt file, each pair on a new line
file_put_contents('wordlist.txt', implode("\n", $word_pairs));

// Close the database connection
$conn->close();

// Function to find the most frequently occurring word in an array
function find_most_frequent_word($word_array) {
    // Count the frequency of each word
    $word_frequency = array_count_values($word_array);
    
    // Sort the array by frequency in descending order
    arsort($word_frequency);

    // Return the word with the highest frequency
    return array_key_first($word_frequency);
}

echo "Word pairs have been successfully written to wordlist.txt";
?>
