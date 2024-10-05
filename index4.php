<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentence Expansion and Definition Automation</title>

    <!-- Include necessary JavaScript libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="brain.js"></script>

    <script>
        let net = new brain.recurrent.LSTM(); // Initialize Brain.js LSTM network for training
        let trainingData = []; // Store all sentences for training
        let wordDefinitions = {}; // Store word definitions fetched from the database

        $(document).ready(function () {
            // Fetch word definitions from the database
            fetchWordDefinitions();

            // Attach event listener to the form submit button for seed sentence input
            $("#seedForm").on("submit", function (event) {
                event.preventDefault();
                const seedSentence = $("#seedInput").val().trim();
                if (seedSentence) {
                    const expandedSentence = expandSentenceWithDefinitions(seedSentence);
                    displayExpandedSentence(expandedSentence);
                    saveExpandedSentence(expandedSentence); // Save to server-side file
                }
            });
        });

        // Function to fetch word definitions from the database
        function fetchWordDefinitions() {
            $.ajax({
                url: 'getWordDefinitions.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    wordDefinitions = data;
                    console.log("Word definitions fetched successfully:", wordDefinitions);
                },
                error: function (error) {
                    console.error("Error fetching word definitions from database:", error);
                }
            });
        }

        // Function to expand a sentence by adding definitions for each word
        function expandSentenceWithDefinitions(sentence) {
            const words = sentence.split(' ');
            let expandedSentence = sentence + " | "; // Start with the original sentence and separator

            words.forEach(word => {
                const lowerCaseWord = word.toLowerCase();
                if (wordDefinitions[lowerCaseWord]) {
                    // If the word exists in the database, use its definition
                    const definition = wordDefinitions[lowerCaseWord];
                    expandedSentence += `${word}(${definition}) `;
                } else {
                    // If no definition is found, keep the word as is
                    expandedSentence += `${word} `;
                }
            });

            return expandedSentence.trim();
        }

        // Function to display the expanded sentence in the output section
        function displayExpandedSentence(expandedSentence) {
            const sentenceElement = document.createElement("div");
            sentenceElement.innerHTML = `<strong>Expanded Sentence:</strong> ${expandedSentence}`;
            document.getElementById("output").appendChild(sentenceElement);
        }

        // Function to save the expanded sentence to a server-side file
        function saveExpandedSentence(expandedSentence) {
            $.ajax({
                url: 'saveExpandedSentence.php',
                method: 'POST',
                data: { expandedSentence: expandedSentence },
                success: function () {
                    console.log("Expanded sentence saved successfully.");
                },
                error: function (error) {
                    console.error("Error saving expanded sentence:", error);
                }
            });
        }
    </script>
</head>
<body>
    <h1>Sentence Expansion and Definition Automation</h1>
    <p>Enter a sentence, and each word will be expanded with its definition using the database. The AI will be retrained with the expanded sentence structure.</p>

    <!-- Form for user to input a seed sentence -->
    <form id="seedForm">
        <label for="seedInput">Enter a seed sentence:</label><br>
        <input type="text" id="seedInput" name="seedInput" style="width: 400px;"><br><br>
        <input type="submit" value="Expand and Process Sentence">
    </form>

    <!-- Display the expanded sentences here -->
    <h2>Expanded Sentence Preview:</h2>
    <div id="output" style="border: 1px solid #ddd; padding: 10px; width: 100%; max-width: 600px;"></div>
</body>
</html>
