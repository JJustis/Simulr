<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservesphp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all words and their definitions
$sql = "SELECT word, definition FROM word";
$result = $conn->query($sql);

$wordDefinitions = array();

// Fetch and store each word and its definition
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $wordDefinitions[$row['word']] = $row['definition'];
    }
}

// Close the connection
$conn->close();

// Return JSON encoded word definitions
header('Content-Type: application/json');
echo json_encode($wordDefinitions);
?>
