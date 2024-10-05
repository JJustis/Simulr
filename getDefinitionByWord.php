<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservesphp";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the 'word' parameter is provided
if (isset($_POST['word'])) {
    $word = $_POST['word'];

    // Query to fetch the definition of the provided word from the 'word' table
    $sql = "SELECT definition FROM word WHERE word = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $word);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $definition = $result->fetch_assoc()['definition'];
        echo json_encode($definition);
    } else {
        echo json_encode("Definition not found");
    }

    $stmt->close();
}

$conn->close();
?>
