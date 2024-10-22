<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("Autoload file not found. Run 'composer install'.<br>");
}


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);  
$dotenv->load();



function main(){

    $username = $_ENV['MAUTIC_USERNAME']; 
    $password = $_ENV['MAUTIC_PASSWORD'];
    $baseurl = $_ENV['MAUTIC_API_URL'];

    $data = [
        "companyname" => "Mantre", // Replace with actual company name
        "isPublished" => 1, // or 0
        "overwriteWithBlank" => false // or true
    ];
    $updateData = [

    ];

    if (isset($_GET['endpoint'])) {
        $endpoint = $_GET['endpoint']; 
    } else {
       
        $endpoint = ''; 
    }

    $clientId = $_ENV['MAUTIC_CLIENT_ID'];
    $clientSecret = $_ENV['MAUTIC_CLIENT_SECRET'];
    $accessToken=Oauth2AccessToken($username,$password,$baseurl, $clientId, $clientSecret);
    $url = $baseurl . $endpoint;
    echo $url;
    // if (strpos($endpoint, '/new') !== false) {
    //     echo "in post";
    //     $response = postResponse($url, $accessToken, $username, $password,$data);
    // } else {
    //     // GET request for all other endpoints
    //     $response = getResponse($url, $accessToken, $username, $password);
    // }
    // echo "<h2>Email Data:</h2>";
    // echo  $response;
    $response = updateCampaign($url,  $username, $password, $updateData);
    echo  $response;
}


function Oauth2AccessToken($username, $password, $baseurl,$clientId, $clientSecret) {
    $tokenUrl = $baseurl . '/oauth/v2/token';
    $data = [
        'grant_type' => 'password',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'username' => $username,
        'password' => $password,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    $accessToken = $tokenData['access_token'] ?? null;

    return  $accessToken;
}


function getResponse($url, $accessToken, $username, $password) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if (!$accessToken) {
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
        ]);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        $data = json_decode($response, true);
        $customHtml = $data['email']['customHtml'] ?? null;
        $customMjml = $data['email']['grapesjsbuilder']['customMjml'] ?? null;

        if ($customHtml) {
            echo "Custom HTML Content:\n";
            $customHtml = str_replace('https://mautic.ddev.site', 'https://mautic.ddev.site:8443', $customHtml);
            echo $customHtml; // Rendering the main HTML content
        } elseif ($customMjml) {
            echo "MJML Content:\n";
            echo $customMjml;

            $html = convertMjmlToHtml($customMjml);
            if ($html) {
                echo "Converted HTML:\n";
                echo $html;
            }
        } else {
            echo "No HTML content available.";
        }
    }

    curl_close($ch);
    return  $response;
}

function postResponse($url, $accessToken, $username, $password, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Set authentication headers
    if (!$accessToken) {
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json", // Set the content type to JSON
        ]);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);


    curl_close($ch);
    return $response;
}

function convertMjmlToHtml($customMjml) {
    $mjmlApiUrl = 'https://api.mjml.io/v1/render';
    $mjmlAppId = 'your_app_id';
    $mjmlSecretKey = 'your_secret_key';

    $postData = json_encode(['mjml' => $customMjml]);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $mjmlApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$mjmlAppId:$mjmlSecretKey")
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        return null;
    }

    curl_close($ch);
    $result = json_decode($response, true);
    return $result['html'] ?? null;
}

function updateCampaign($url, $username, $password, $updateFields) {
    // Step 1: Get current campaign data
    $getUrl = $url; // Using the provided URL for GET request
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $getUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local/test environments
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Basic authentication

    // Execute GET request
    $response = curl_exec($ch);
    
    // Check for errors in GET request
    if (!$response) {
        echo "Error: GET request failed.<br>";
        echo 'cURL error: ' . curl_error($ch) . "<br>";
        curl_close($ch);
        return false;
    }

    // Decode response to associative array
    $responseData = json_decode($response, true);

    // Check if JSON decoding was successful
    if (!is_array($responseData)) {
        echo "Error: Failed to decode JSON response.<br>";
        curl_close($ch);
        return false;
    }

    // Print the current campaign data for debugging
    echo "Current Campaign Data: <pre>" . print_r($responseData, true) . "</pre><br>";

    curl_close($ch); // Close GET request

    // Step 2: Update specific fields in the response data
    // Update the name of the 4th event as an example
    if (isset($responseData['campaign']['events'][3]['name'])) {
        $responseData['campaign']['events'][3]['name'] = "Updates Form Field Values"; // Update the event name
    }
    if (isset($responseData['campaign']['events'][0]['children'][0]['name'])) {
        $responseData['campaign']['events'][0]['children'][0]['name'] = "Updates Form Field Values"; // Update the event name
    }

    // Prepare the new associative array with updated campaign data
    $newCampaignData = [
        'name' => $responseData['campaign']['name'],
        'events' => $responseData['campaign']['events'],
    ];

    // Print the updated campaign data for debugging
    echo "UPDATED Campaign Data: <pre>" . print_r($newCampaignData, true) . "</pre><br>";

    // Create a new array to copy all fields from responseData['campaign']
    $finalCampaignData = [];
    foreach ($responseData['campaign'] as $key => $value) {
        $finalCampaignData[$key] = $value; // Copy all fields
    }

    // Step 3: Send PUT request to update the campaign
    $putUrl = $url . '/edit'; // Append /edit to the URL for PUT request
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $putUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // Use PUT method
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($finalCampaignData)); // Send updated data in JSON format
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Basic authentication
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local/test environments

    // Execute PUT request
    $putResponse = curl_exec($ch);

    // Check for errors in PUT request
    if (!$putResponse) {
        echo "Error: PUT request failed.<br>";
        echo 'cURL error: ' . curl_error($ch) . "<br>";
        curl_close($ch);
        return false;
    }

    // Decode and print the PUT response for debugging
    $putResponseDecoded = json_decode($putResponse, true);
    echo "PUT Response: <pre>" . print_r($putResponseDecoded, true) . "</pre><br>";

    // Check for HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP Status Code: $httpCode<br>";

    curl_close($ch); // Close PUT request

    return $putResponseDecoded; // Return the response from the PUT request
}


main();

