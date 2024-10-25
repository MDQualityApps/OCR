<!doctype html>
<html lang="en">
<head>
    <title>Table 06</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,100,300,700' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="css/style.css">
 
</head>
<?php
require 'vendor/autoload.php';
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

function performOcr($imagePath) {
    $imageAnnotator = new ImageAnnotatorClient([
        'credentials' => 'C:/xampp/htdocs/OCR/sustained-kit-439515-f6-c8af444089d3.json'
    ]);

    try {
        $imageData = file_get_contents($imagePath);
        $response = $imageAnnotator->textDetection($imageData);
        $texts = $response->getTextAnnotations();

        if ($texts) {
            return $texts[0]->getDescription();
        } else {
            return null;
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
        return null;
    } finally {
        $imageAnnotator->close();
    }
}

function extractData($text) {
    $data = [];
    $lines = explode("\n", $text);

    // Initialize empty values
    $data['name'] = '';
    $data['email'] = '';
    $data['company_name'] = '';
    $data['website'] = '';
    $data['position'] = '';

    // Define common phrases to match for company names
    $companyPatterns = [
        'Solutions', 
        'Pvt Ltd', 
        'Private Limited', 
        'LLP', 
        'Limited', 
        'Inc', 
        'Corporation', 
        'Company', 
        'Org', 
        'Organization'
    ];

    // Define common patterns to match for positions
    $positionPatterns = [
        'Manager', 
        'Developer', 
        'Engineer', 
        'Director', 
        'Coordinator', 
        'Specialist', 
        'Lead', 
        'Consultant', 
        'Analyst', 
        'Intern',
        'Co-founder',
        'Founder',
        'COO'
    ];

    foreach ($lines as $line) {
        $trimmedLine = trim($line);

        // Skip empty lines
        if (empty($trimmedLine)) {
            continue;
        }

        // Check for email using regex
        if (preg_match("/[a-z0-9_\-.]+@[a-z0-9\-]+\.[a-z]{2,6}/i", $trimmedLine, $emailMatches)) {
            $data['email'] = $emailMatches[0];
            continue; // Skip to the next line after finding an email
        }

        // Check for website using regex
        if (preg_match("/\b(https?:\/\/[^\s]+|www\.[^\s]+)\b/i", $trimmedLine, $websiteMatches)) {
            $data['website'] = $websiteMatches[0];
            continue; // Skip to the next line after finding a website
        }

        // Heuristic for name detection
        if (empty($data['name']) && preg_match("/^[A-Za-z\s.]+$/", $trimmedLine) && !preg_match("/\d{1,}/", $trimmedLine) && !preg_match("/[,\-]/", $trimmedLine)) {
            $data['name'] = $trimmedLine;
        } elseif (empty($data['company_name'])) {
            // Exclude lines that match typical address patterns
            if (!preg_match("/\d{1,}/", $trimmedLine) && 
                !preg_match("/Street|St\.?|Avenue|Ave\.?|Road|Rd\.?|Boulevard|Blvd\.?|Nagar|Lane|Ln\.?|Place|Pl\.?|Drive|Dr\.?|Court|Ct\.?|Terrace|Tce\.?|Circle|Cir\.?|Park|Pk\.?|#|\\//i", $trimmedLine)) {
                
                // Check if the line contains any of the company patterns
                foreach ($companyPatterns as $pattern) {
                    if (stripos($trimmedLine, $pattern) !== false) {
                        $data['company_name'] = $trimmedLine; // Set as company name if it matches any pattern
                        break; // Break out of the loop once a match is found
                    }
                }
            }
        }

        // Check for position titles using the defined patterns
        foreach ($positionPatterns as $pattern) {
            if (stripos($trimmedLine, $pattern) !== false) {
                $data['position'] = $trimmedLine; // Set as position if it matches any pattern
                break; // Break out of the loop once a match is found
            }
        }
    }

    return $data;
}

function displayDataFromDatabase() {
    $servername = "localhost";
    $username = "root"; // Replace with your MySQL username
    $password = ""; // Replace with your MySQL password
    $dbname = "ocr_db"; // Replace with your actual database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to fetch all data
    $sql = "SELECT name, email, company_name, website, position FROM extracted_data";
    $result = $conn->query($sql);

    // Check if there are results and output as table
    if ($result->num_rows > 0) {
        ?>
        <body>
        <section class="ftco-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h3 class="h5 mb-4 text-center">Table Accordion</h3>
                        <div class="table-wrap">
                            <table class="table">
                              <thead class="thead-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Company_name</th>
                                    <th>Website</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php 
                                 $serialNo = 1;
                                   while($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td><?php echo $serialNo; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['position']; ?></td>
                                    <td><?php echo $row['company_name']; ?></td>
                                    <td><?php echo $row['website']; ?></td>
                                </tr>
                                
                                <?php 
                             $serialNo++;
                             } ?>
                              </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}

function saveToDatabase($data) {
    $servername = "localhost";
    $username = "root"; // Replace with your MySQL username
    $password = ""; // Replace with your MySQL password
    $dbname = "ocr_db"; // Replace with your actual database name

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo "<script>showToast('Connection failed: " . $conn->connect_error . "', true);</script>";
        return;
    }

    $stmt = $conn->prepare("INSERT INTO extracted_data (name, email, company_name, website, position) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data['name'], $data['email'], $data['company_name'], $data['website'], $data['position']);

    if ($stmt->execute()) {
        echo "<script>showToast('New record created successfully');
         header('Location: index.php');</script>";
    } else {
        echo "<script>showToast('Error: " . addslashes($stmt->error) . "', true);</script>";
    }

  
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $targetDir = "uploads/";

    // Loop through each uploaded file
    foreach ($_FILES['image']['name'] as $key => $name) {
        $targetFile = $targetDir . basename($name); // Use $name instead of $_FILES["image"]["name"]

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["image"]["tmp_name"][$key], $targetFile)) {
            // Perform OCR on the uploaded file
            $extractedText = performOcr($targetFile);
            if ($extractedText) {
                // Split the extracted text for each card if necessary
                $cardTexts = explode("CARD_END_MARKER", $extractedText); // Adjust this if you have a way to differentiate cards
                foreach ($cardTexts as $cardText) {
                    $data = extractData($cardText);
                    saveToDatabase($data);
                }
                header("Location: tabledata.php");
            }
        } else {
            echo "<script>showToast('Failed to upload file: $name', true);</script>";
        }
    }
}


 

?>

</html>
