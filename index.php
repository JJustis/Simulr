<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentence Relationship Learning with Definitions and Images using Brain.js</title>

    <!-- Include necessary JavaScript libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	 	<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjs/9.5.0/math.js"></script>
    <script src="brain.js"></script>

    <script>
        const commonWords = ["by", "a", "is", "and", "the", "in", "on", "at", "to", "of", "for"]; // Common words to exclude
         let trainingData = []; // Store all sentences for training
        let net = new brain.recurrent.LSTM(); // Create a new Brain.js LSTM network for sentence prediction
        let lastAISentence = ""; // Store the last AI-generated sentence
        $(document).ready(function () {
            // Load and process training data from the file when the page loads
            loadTrainingDataFromFile();

            // Attach event listener to the form submit button for seed sentence input
            $("#seedForm").on("submit", function (event) {
                event.preventDefault();
                const seedSentence = $("#seedInput").val().trim();
                if (seedSentence) {
                    processSeedSentence(seedSentence);
                }
            });
        });
        // Function to load training data from trainingdata_with_images.txt
        function loadTrainingDataFromFile() {
            $.ajax({
                url: 'trainingdata_with_images.txt',
                method: 'GET',
                dataType: 'text',
                success: function (data) {
                    const lines = data.split('\n').filter(line => line.trim() !== '');
                    lines.forEach(line => {
                        const parts = line.split('|');
                        if (parts.length > 1) {
                            const inputSentence = parts[0].trim();
                            const outputSentence = parts[1].trim();
                            if (inputSentence && outputSentence) {
                                trainingData.push({ input: inputSentence, output: outputSentence });
                            }
                        }
                    });

                    // Once the training data is loaded, train the Brain.js network
                    retrainBrainJS();
                },
                error: function (error) {
                    console.error("Error loading training data from file: ", error);
                }
            });
        }

        // Function to process the seed sentence entered by the user

        // Function to fetch definition, image, and additional info for a word from Wikipedia with variations
        function fetchWikipediaDefinitionAndImage(word, callback) {
            const variations = [word, word.replace(/s$/, ''), word.replace(/ed$/, ''), word.replace(/ing$/, ''), word.replace(/ly$/, '')]; // Variations to try

            function tryNextVariation(index) {
                if (index >= variations.length) {
                    callback("Definition not found", `https://via.placeholder.com/50?text=${word}`, "Additional information not found"); // No valid variation found
                    return;
                }

                const currentWord = variations[index];
                const wikipediaApiUrl = `https://en.wikipedia.org/w/api.php?action=query&prop=extracts|pageimages&format=json&exintro=&titles=${encodeURIComponent(currentWord)}&pithumbsize=100&origin=*`;

                $.ajax({
                    url: wikipediaApiUrl,
                    method: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        if (!data || !data.query || !data.query.pages) {
                            callback("Definition not found", `https://via.placeholder.com/50?text=${currentWord}`, "Additional information not found");
                            return;
                        }

                        const pages = data.query.pages;
                        const page = Object.values(pages)[0];

                        if (page && page.extract) {
                            let definition = page.extract.split(".")[0];
                            let imageLink = `https://via.placeholder.com/50?text=${currentWord}`;
                            let additionalInfo = page.extract.split(".").slice(1).join(". ");
                            if (page.thumbnail && page.thumbnail.source) {
                                imageLink = page.thumbnail.source;
                            }
                            callback(definition, imageLink, additionalInfo);
                        } else {
                            tryNextVariation(index + 1);
                        }
                    },
                    error: function (error) {
                        console.error(`Error fetching Wikipedia data for word: ${currentWord}`, error);
                        tryNextVariation(index + 1);
                    }
                });
            }

            tryNextVariation(0);
        }
// Function to retrain the Brain.js network on the current training data
        function retrainBrainJS() {
            if (trainingData.length === 0) {
                console.warn("No training data available for training Brain.js.");
                return;
            }

            try {
                net.train(trainingData.map(item => ({
                    input: item.input, // Ensure the input is a string
                    output: item.output // Ensure the output is a string
                })), {
                    iterations: 40, // Increase for better training (trades off with speed)
                    errorThresh: 0.011, // Define error threshold for training
                    log: true,  // Enable logging
                    logPeriod: 10 // Log progress every 10 iterations
                });
                console.log("Brain.js network retrained with current training data.");
            } catch (error) {
                console.error("Error training Brain.js network:", error);
            }
        }


        // Function to append a new sentence and its AI response to trainingdata_with_images.txt
        function appendToTrainingFile(seedSentence, aiSentence) {
            const newEntry = `${seedSentence} | ${aiSentence}\n`;
            $.ajax({
                url: 'appendToTrainingData.php',
                method: 'POST',
                data: { newEntry: newEntry },
                success: function () {
                    console.log("New entry appended to training data file.");
                },
                error: function (error) {
                    console.error("Error appending new entry to training data file: ", error);
                }
            });
        }


        // Function to process the seed sentence entered by the user
       // Function to preprocess a sentence by removing words connected to symbols and convert to lowercase
                function preprocessSentence(sentence) {
            // Remove any word that has symbols connected to it (excluding spaces)
            return sentence
                .split(' ') // Split into words
                .filter(word => /^[a-zA-Z]+$/.test(word)) // Keep only words with alphabetical characters
                .join(' ') // Join the words back with spaces
                .toLowerCase(); // Convert to lowercase
        }

        // Function to calculate similarity between two sentences using word overlap (with preprocessing)
        function calculateWordOverlap(sentence1, sentence2) {
            // Preprocess sentences to remove symbols and connected words
            const cleanSentence1 = preprocessSentence(sentence1);
            const cleanSentence2 = preprocessSentence(sentence2);

            // Convert sentences to sets of singular words excluding common words
            const words1 = new Set(cleanSentence1.split(' ').filter(word => !commonWords.includes(word)));
            const words2 = new Set(cleanSentence2.split(' ').filter(word => !commonWords.includes(word)));

            // Calculate intersection and union for Jaccard similarity
            const intersection = new Set([...words1].filter(word => words2.has(word)));
            const union = new Set([...words1, ...words2]);

            return intersection.size / union.size; // Jaccard similarity index
        }
        // Function to process the seed sentence entered by the user
        function processSeedSentence(sentence) {
            if (isMathematicalExpression(sentence)) {
                const solution = solveEquation(sentence);
                displayAISentence(`Solution: ${solution}`);
            } else {
                const preprocessedSentence = preprocessSentence(sentence);

                formatSentenceWithDefinitionsAndImages(preprocessedSentence, function (formattedSentence) {
                    displaySentence(formattedSentence);
                    saveFormattedSentence(formattedSentence); // Save to server-side file
                    addToTrainingData(preprocessedSentence, formattedSentence, 1); // Add with default weight
                    retrainBrainJS(); // Retrain Brain.js on updated training data

                    let aiSentence = "";
                    try {
                        aiSentence = net.run(preprocessedSentence); // Attempt to run the model on preprocessed input
                    } catch (err) {
                        console.error("Error running the Brain.js network:", err);
                        aiSentence = "AI couldn't generate a response. Please try again after re-training.";
                    }

                    lastAISentence = aiSentence;
                    displayAISentence(aiSentence);
                    appendToTrainingFile(preprocessedSentence, aiSentence); // Append new AI sentence to training data file
                });
            }
        }

        // Function to detect if a sentence is a mathematical expression
        function isMathematicalExpression(sentence) {
            const mathSymbols = /[+\-*/=()^]/; // Basic math symbols to look for
            const variablePattern = /^[a-zA-Z]$/; // Single letter variables

            const words = sentence.split(' ');
            return words.every(word => mathSymbols.test(word) || variablePattern.test(word));
        }
 // Function to strip HTML tags from a given string
        function stripHTMLTags(str) {
            const temporaryDiv = document.createElement("div");
            temporaryDiv.innerHTML = str;
            return temporaryDiv.innerText || temporaryDiv.textContent || ""; // Return only the text content
        }

 function solveEquation(equation) {
            try {
                // Check if the equation contains an '=' sign for splitting into left and right parts
                if (equation.includes('=')) {
                    const [left, right] = equation.split('=');

                    // Move all terms to the left side and set the right side to zero
                    const rearrangedEquation = `(${left}) - (${right})`; // Create a new equation where the right side is subtracted

                    // Use math.evaluate to solve for zero
                    const solution = math.simplify(rearrangedEquation).toString(); // Simplify the equation first
                    return solution;
                } else {
                    // Directly evaluate and simplify the equation without an '='
                    const evaluatedEquation = math.evaluate(equation);
                    return evaluatedEquation.toString();
                }
            } catch (error) {
                console.error("Error solving equation:", error);
                return "Unable to solve the equation. Please check the format.";
            }
        }

        // Function to display the formatted sentence in the preview section
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
        // Function to format a sentence by adding expanded definitions, image links, and more details for each word
        function formatSentenceWithDefinitionsAndImages(sentence, callback) {
            const words = sentence.split(' '); // Split the sentence into words
            let formattedSentence = `<p><strong>Original Sentence:</strong> ${sentence}</p>`; // Start with the original sentence

            // Recursively add expanded information for each word
            function processWord(index) {
                if (index >= words.length) {
                    callback(formattedSentence); // Once done, return the formatted sentence
                    return;
                }

                const word = words[index];
                if (!commonWords.includes(word.toLowerCase())) {
                    // Fetch expanded information for the keyword
                    fetchWikipediaDefinitionAndImage(word, function (definition, imageLink, additionalInfo) {
                        // Create a detailed paragraph for the word
                        const wordWithDetails = `
                            <div style="margin-bottom: 15px;">
                                <h4>${word}</h4>
                                <p><strong>Definition:</strong> ${definition}</p>
                                <p><strong>Additional Info:</strong> ${additionalInfo}</p>
                                <img src="${imageLink}" alt="${word}" style="width: 50px; height: 50px; display: block; margin-top: 5px;">
                            </div>
                        `;
                        formattedSentence += wordWithDetails; // Append detailed information for the word

                        // Append the image to the image list section below the main output
                        addImageToList(word, imageLink);

                        // Process the next word
                        processWord(index + 1);
                    });
                } else {
                    formattedSentence += `<span>${word} </span>`; // Append common word as is
                    processWord(index + 1); // Skip common words and continue
                }
            }

            processWord(0); // Start processing words
        }

        // Function to add an image to the image list section
        function addImageToList(word, imageLink) {
            const imageContainer = document.createElement("div");
            imageContainer.style.display = "inline-block";
            imageContainer.style.margin = "5px";
            imageContainer.innerHTML = `<img src="${imageLink}" alt="${word}" style="width: 50px; height: 50px;"> <br> <span>${word}</span>`;
            document.getElementById("imageList").appendChild(imageContainer);
        }
        // Function to display the AI-generated sentence
        function displayAISentence(sentence) {
            const aiSentenceElement = document.createElement("div");
            aiSentenceElement.innerHTML = `<strong>AI Generated Sentence:</strong> ${sentence}`;
            document.getElementById("output").appendChild(aiSentenceElement);

            // Show feedback form for AI response
            $("#feedbackForm").show();
        }

function adjustTrainingDataWeight(sentence, adjustment) {
            trainingData.forEach(item => {
                if (item.output === sentence) {
                    item.weight = Math.max(1, item.weight + adjustment); // Ensure weight doesn't go below 1
                }
            });
        }

        // Function to add a new training data entry with a specified weight
        function addToTrainingData(input, output, weight = 1) {
            trainingData.push({ input: input, output: output, weight: weight });
        }
        // Function to append a new sentence and its AI response to trainingdata_with_images.txt
        function appendToTrainingFile(seedSentence, aiSentence) {
            const newEntry = `${seedSentence} | ${aiSentence}\n`;
            $.ajax({
                url: 'appendToTrainingData.php',
                method: 'POST',
                data: { newEntry: newEntry },
                success: function () {
                    console.log("New entry appended to training data file.");
                },
                error: function (error) {
                    console.error("Error appending new entry to training data file: ", error);
                }
            });
        }

                // Function to format a sentence by adding expanded definitions, image links, and more details for each word
        function formatSentenceWithDefinitionsAndImages(sentence, callback) {
            const words = sentence.split(' '); // Split the sentence into words
            let formattedSentence = `<p><strong>Original Sentence:</strong> ${sentence}</p>`; // Start with the original sentence

            // Recursively add expanded information for each word
            function processWord(index) {
                if (index >= words.length) {
                    callback(formattedSentence); // Once done, return the formatted sentence
                    return;
                }

                const word = words[index];
                if (!commonWords.includes(word.toLowerCase())) {
                    // Fetch expanded information for the keyword
                    fetchWikipediaDefinitionAndImage(word, function (definition, imageLink, additionalInfo) {
                        // Create a detailed paragraph for the word
                        const wordWithDetails = `
                            <div style="margin-bottom: 15px;">
                                <h4>${word}</h4>
                                <p><strong>Definition:</strong> ${definition}</p>
                                <p><strong>Additional Info:</strong> ${additionalInfo}</p>
                                <img src="${imageLink}" alt="${word}" style="width: 50px; height: 50px; display: block; margin-top: 5px;">
                            </div>
                        `;
                        formattedSentence += wordWithDetails; // Append detailed information for the word

                        // Append the image to the image list section below the main output
                        addImageToList(word, imageLink);

                        // Process the next word
                        processWord(index + 1);
                    });
                } else {
                    formattedSentence += `<span>${word} </span>`; // Append common word as is
                    processWord(index + 1); // Skip common words and continue
                }
            }

            processWord(0); // Start processing words
        }

        // Function to add an image to the image list section
        function addImageToList(word, imageLink) {
            const imageContainer = document.createElement("div");
            imageContainer.style.display = "inline-block";
            imageContainer.style.margin = "5px";
            imageContainer.innerHTML = `<img src="${imageLink}" alt="${word}" style="width: 50px; height: 50px;"> <br> <span>${word}</span>`;
            document.getElementById("imageList").appendChild(imageContainer);
        }
		        // Function to process feedback on the AI's last generated sentence
        function processFeedback(feedback) {
            if (feedback === "false") {
                console.log("AI response was marked as off-topic or incorrect.");
                // Optional: Remove the AI's response from training data or lower its weight
                trainingData = trainingData.filter(item => item.output !== lastAISentence);
                retrainBrainJS(); // Retrain Brain.js to exclude incorrect response
            } else {
                console.log("AI response was marked as correct or on-topic.");
            }

            // Hide the feedback form after submission
            $("#feedbackForm").hide();
        }
    </script>
</head>
<body>
    <h1>Sentence Relationship Learning with Definitions and Images using Brain.js</h1>
    <p>Enter a sentence, and the AI will process it, generate definitions, and create a new sentence based on the input.</p>

    <!-- Form for user to input a seed sentence -->
    <form id="seedForm">
        <label for="seedInput">Enter a seed sentence:</label><br>
        <input type="text" id="seedInput" name="seedInput" style="width: 400px;"><br><br>
        <input type="submit" value="Process Sentence">
    </form>

    <!-- Display the sentences with expanded details here -->
    <h2>Formatted Sentence Preview:</h2>
    <div id="output" style="border: 1px solid #ddd; padding: 10px; width: 100%; max-width: 600px;"></div>

    <!-- Form for providing feedback on AI response -->
    <form id="feedbackForm" style="display: none;">
        <h2>Provide Feedback on AI Response</h2>
        <label>
            <input type="radio" name="feedback" value="true"> Correct/On-Topic
        </label>
        <label>
            <input type="radio" name="feedback" value="false"> Incorrect/Off-Topic
        </label><br><br>
        <input type="submit" value="Submit Feedback">
    </form>

    <!-- Image list section to display all images found for the words -->
    <h2>Image List:</h2>
    <div id="imageList" style="border: 1px solid #ddd; padding: 10px; width: 100%; max-width: 600px; display: flex; flex-wrap: wrap;"></div>
</body>
</html>