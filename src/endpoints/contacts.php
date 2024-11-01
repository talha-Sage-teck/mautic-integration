<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(require_once __DIR__ . '/../../vendor/autoload.php')

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '//..//..//');
$dotenv->load();

use MauticWrapper\MauticAPI;

$apiUrl = $_ENV['API_URL'];
$username = $_ENV['USERNAME'];
$password = $_ENV['PASSWORD'];


// Initialize Mautic API
$mautic = new MauticAPI($apiUrl, $username, $password);

echo "<h2>Mautic API Operations</h2><br>";

// Function to add a contact
function addContact($mautic, $data) {
    $response = $mautic->createContact($data);
    return isset($response['contact']) ? "Contact added successfully!" : "Failed to add contact: " . ($response['error'] ?? 'Unknown error.');
}

// Function to add a segment (with detailed response logging)
function addSegment($mautic, $data) {
    $response = $mautic->createSegment($data);
    if (isset($response['segment'])) {
        return "Segment added successfully!";
    } else {
        // Log full response for debugging
        return "Failed to add segment: " . json_encode($response);
    }
}




// Function to get all contacts
function getAllContacts($mautic) {
    $response = $mautic->getContacts();
    return isset($response['contacts']) ? $response['contacts'] : [];
}

// Function to get all segments
function getAllSegments($mautic) {
    $response = $mautic->getSegments();
    return isset($response['lists']) ? $response['lists'] : [];
}

// Test dummy data for contact
$contactData = [
    'firstname' => 'Johnnnnnnnnngm',
    'lastname' => 'Doennnnnnnnnnnnngm',
    'email' => 'johne.doeeeg@example.com',
];

// Add contact
$contactResponse = $mautic->createContact($contactData);
if (isset($contactResponse['contact']['id'])) {
    $contactId = $contactResponse['contact']['id'];
    echo "Contact added successfully with ID: $contactId<br>";
} else {
    echo "Failed to add contact: " . json_encode($contactResponse) . "<br>"; // Log full response
}

// Test dummy data for segment, using proper format
$segmentData = [
    'name'        => 'Segment Abcdeefgnerffertg',
    'description' => 'This is my first segment created via API.',
    'isPublished' => 1,
    'filters' => [
        [
            'glue'    => 'and',
            'field'   => 'email',
            'object'  => 'lead',
            'type'    => 'email',
            'filter'  => '*@gmail.com',
            'operator' => 'like',
        ],
    ],
];

// // Add segment and handle response
$segmentResponse = $mautic->createSegment($segmentData);

// Check if segment was added successfully
if (isset($segmentResponse['list']['id'])) {
    $segmentId = $segmentResponse['list']['id'];
    echo "Segment added successfully with ID: $segmentId<br>";
} else {
    // Log the entire response for debugging purposes
    echo "Failed to add segment: " . json_encode($segmentResponse, JSON_PRETTY_PRINT) . "<br>";
}


// Function to add a contact to a segment
function addContactToSegment($mautic, $c_id, $s_id) {
    $response = $mautic->addContactToSegment($c_id, $s_id);
    
    echo "Response from adding contact: " . json_encode($response) . "<br>";
    return isset($response['success']) ? "Contact added to segment successfully!" : "Failed to add contact to segment: " . ($response['error'] ?? 'Unknown error.');
}

// Add contact to segment (only if both contact and segment were created)
if (isset($contactData) && isset($segmentData)) {
    echo addContactToSegment($mautic, $contactResponse['contact']['id'], $segmentResponse['list']['id']);
} else {
    echo "Unable to add contact to segment due to previous errors.<br>";
}

// echo "<br><h3>All Contacts:</h3>";
// $contacts = getAllContacts($mautic);
// if (!empty($contacts)) {
//     foreach ($contacts as $id => $contact) {
//         echo "ID: $id, Name: {$contact['firstname']} {$contact['lastname']}, Email: {$contact['email']}<br>";
//     }
// } else {
//     echo "No contacts found.<br>";
// }

// echo "<br><h3>All Segments:</h3>";
// $segments = getAllSegments($mautic);
// if (!empty($segments)) {
//     foreach ($segments as $id => $segment) {
//         echo "ID: $id, Name: {$segment['name']}, Description: {$segment['description']}<br>";
//     }
// } else {
//     echo "No segments found.<br>";
// }
?>
