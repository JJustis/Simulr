<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentence Relationship Learning with Definitions and Images</title>

    <!-- Include necessary JavaScript libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        const commonWords = ["by", "a", "is", "and", "the", "in", "on", "at", "to", "of", "for"]; // Common words to exclude

        $(document).ready(function () {
            // Load and process training data when the page loads
            loadAndProcessTrainingData();

            // Attach event listener to the form submit button for seed sentence input
            $("#seedForm").on("submit", function (event) {
                event.preventDefault();
                const seedSentence = $("#seedInput").val().trim();
                if (seedSentence) {
                    processSeedSentence(seedSentence);
                }
            });
        });

        // Function to process the seed sentence entered by the user
        function processSeedSentence(sentence) {
            formatSentenceWithDefinitionsAndImages(sentence, function (formattedSentence) {
                displaySentence(formattedSentence);
                saveFormattedSentence(formattedSentence); // Save to server-side file
            });
        }

        // Function to load and process training data
        function loadAndProcessTrainingData() {
            $.ajax({
                url: 'getTrainingData.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    if (data.length > 0) {
                        // Process and format each sentence
                        data.forEach((sentence) => {
                            formatSentenceWithDefinitionsAndImages(sentence, function (formattedSentence) {
                                displaySentence(formattedSentence);
                                // Save formatted sentence to server-side file
                                saveFormattedSentence(formattedSentence);
                            });
                        });
                    } else {
                        console.error("No training data found.");
                    }
                },
                error: function (error) {
                    console.error("Error loading training data: ", error);
                }
            });
        }

        // Function to format a sentence by adding definitions and image links
        function formatSentenceWithDefinitionsAndImages(sentence, callback) {
            const words = sentence.split(' '); // Split the sentence into words
            let formattedSentence = sentence + " | "; // Start with the original sentence and add separator

            // Recursively add definitions and images for each word
            function processWord(index) {
                if (index >= words.length) {
                    callback(formattedSentence); // Once done, return the formatted sentence
                    return;
                }

                const word = words[index];
                if (!commonWords.includes(word.toLowerCase())) {
                    // Fetch definition and image for the keyword
                    fetchWikipediaDefinitionAndImage(word, function (definition, imageLink) {
                        // Format the word with definition and image link
                        const wordWithDefinitionAndImage = `${word}(${definition} <img src="${imageLink}" alt="${word}" style="width: 25px; height: 25px;">) `;
                        formattedSentence += wordWithDefinitionAndImage; // Append formatted word to sentence

                        // Process the next word
                        processWord(index + 1);
                    });
                } else {
                    formattedSentence += word + " "; // Append common word as is
                    processWord(index + 1); // Skip common words and continue
                }
            }

            processWord(0); // Start processing words
        }

        // Function to fetch definition and image for a word from Wikipedia
        function fetchWikipediaDefinitionAndImage(word, callback) {
            const wikipediaApiUrl = `https://en.wikipedia.org/w/api.php?action=query&prop=extracts|pageimages&format=json&exintro=&titles=${encodeURIComponent(word)}&pithumbsize=100&origin=*`;

            $.ajax({
                url: wikipediaApiUrl,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    let definition = "Definition not found";
                    let imageLink = `https://via.placeholder.com/25?text=${word}`; // Default image if not found

                    // Get the first page from the Wikipedia response
                    const pages = data.query.pages;
                    const page = Object.values(pages)[0];

                    if (page.extract) {
                        definition = page.extract.split(".")[0]; // Get the first sentence of the extract
                    }

                    if (page.thumbnail && page.thumbnail.source) {
                        imageLink = page.thumbnail.source; // Get the image link if it exists
                    }

                    callback(definition, imageLink);
                },
                error: function (error) {
                    console.error("Error fetching Wikipedia data: ", error);
                    callback("Definition not found", `https://via.placeholder.com/25?text=${word}`); // Return default image if error
                }
            });
        }

        // Function to display a formatted sentence in the preview section
        function displaySentence(formattedSentence) {
            const sentenceElement = document.createElement("div");
            sentenceElement.innerHTML = formattedSentence;
            document.getElementById("output").appendChild(sentenceElement);
        }

        // Function to save the formatted sentence to a server-side file (trainingdata_with_images.txt)
        function saveFormattedSentence(formattedSentence) {
            $.ajax({
                url: 'saveFormattedSentence.php',
                method: 'POST',
                data: { formattedSentence: formattedSentence },
                success: function () {
                    console.log("Formatted sentence saved successfully.");
                },
                error: function (error) {
                    console.error("Error saving formatted sentence: ", error);
                }
            });
        }
    </script>
</head>
<body>
    <h1>Sentence Relationship Learning with Definitions and Images</h1>
    <p>Enter a sentence, and each word will be defined using Wikipedia data. Image links from Wikipedia will be shown next to each word.</p>

    <!-- Form for user to input a seed sentence -->
    <form id="seedForm">
        <label for="seedInput">Enter a seed sentence:</label><br>
        <input type="text" id="seedInput" name="seedInput" style="width: 400px;"><br><br>
        <input type="submit" value="Process Sentence">
    </form>

    <!-- Display the sentences with images and definitions here -->
    <h2>Formatted Sentence Preview:</h2>
    <div id="output" style="border: 1px solid #ddd; padding: 10px; width: 100%; max-width: 600px;"></div>
</body>
</html>
