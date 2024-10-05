<?php
// Database connection function
function getDatabaseConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "reservesphp";

    // Create and return connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }

    return $conn;
}

// Fetch sentences function
function fetchSentences($conn) {
    $sql = "SELECT sentance FROM sentances";
    $result = $conn->query($sql);

    $sentences = array();
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sentences[] = $row['sentance'];
        }
    }
    return $sentences;
}

// Main execution
$conn = getDatabaseConnection();
$sentences = fetchSentences($conn);
$conn->close();

// Return JSON encoded sentences
header('Content-Type: application/json');
echo json_encode($sentences);
?>
