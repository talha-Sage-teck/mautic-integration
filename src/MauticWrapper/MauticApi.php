<?php
namespace MauticWrapper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MauticAPI {
    private $client;

    public function __construct($apiUrl, $username, $password) {
        $this->client = new Client([
            'base_uri' => rtrim($apiUrl, '/') . '/',
            'auth' => [$username, $password],
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

    }
    public function setClientOptions(array $options)
    {
        if (property_exists($this, 'client')) {
            foreach ($options as $key => $value) {
                $this->client->setDefaultOption($key, $value);
            }
        }
    }
    // Emails functions
    public function getEmails() {
        try {
            $response = $this->client->get('emails');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Campaigns functions
    public function getCampaigns() {
        try {
            $response = $this->client->get('campaigns');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getCampaign($id) {
        try {
            $response = $this->client->get("campaigns/$id");
            return json_decode($response->getBody(), false);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function createCampaign($data) {
        try {
            $response = $this->client->post('campaigns/new', [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function cloneCampaign($id) {
        try {
            $response = $this->client->post("campaigns/clone/$id", [
                'json'=>null
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function updateCampaign($id, $data) {
        try {

            $response = $this->client->put("campaigns/$id/edit", [
              'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function deleteCampaign($id) {
        try {
            $response = $this->client->delete("campaigns/$id");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Contacts functions
    public function getContacts() {
        try {
            $response = $this->client->get('contacts');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getContact($id) {
        try {
            $response = $this->client->get("contacts/$id");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
 
    public function createContact($data)
    {
        try {
            // Send the POST request to add the contact
            $response = $this->client->post('contacts/new', [
                'json' => $data,
            ]);

            // Return the response
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle exceptions
            return ['error' => $e->getMessage()];
        }
    }

    public function createSegment($data)
    {
        try {
            // Send the POST request to add the segment
            $response = $this->client->post('segments/new', [
                'json' => $data,
            ]);

            // Return the response
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle exceptions
            return ['error' => $e->getMessage()];
        }
    }
    // Segment API functionality integrated directly into MauticAPI
    public function addContactToSegment($contactId, $segmentId) {
        try {
                // Step 3: Add the contact to the segment
            $addContactToSegmentResponse = $this->addContactToSegmentApi($segmentId, $contactId);
            // Print the full response for debugging
            echo "Response from adding contact to segment: " . json_encode($addContactToSegmentResponse) . "<br>";

            echo "isset('success'): " . (isset($addContactToSegmentResponse['success']) ? 'true' : 'false') . "<br>";

            echo "Success value: " . $addContactToSegmentResponse['success'] . "<br>";

            // Check for success using the specific response format
            if (isset($addContactToSegmentResponse['success']) && $addContactToSegmentResponse['success'] === 1) {
                echo "Contact added to segment successfully.<br>";
            } else {
                echo "Failed to add contact to segment--------: " . json_encode($addContactToSegmentResponse) . "<br>";
            }
            return $addContactToSegmentResponse;

        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
    // Segment API functionality integrated directly into MauticAPI
    public function createContactToSegment($contactData, $segmentData) {
        try {
            // Add contact
            $contactResponse = $this->createContact($contactData);
            if (isset($contactResponse['contact']['id'])) {
                $contactId = $contactResponse['contact']['id'];
                echo "Contact added successfully with ID: $contactId<br>";
            } else {
                echo "Failed to add contact: " . json_encode($contactResponse) . "<br>"; // Log full response
            }

            // // Add segment and handle response
            $segmentResponse = $this->createSegment($segmentData);

            // Check if segment was added successfully
            if (isset($segmentResponse['list']['id'])) {
                $segmentId = $segmentResponse['list']['id'];
                echo "Segment added successfully with ID: $segmentId<br>";
            } else {
                // Log the entire response for debugging purposes
                echo "Failed to add segment: " . json_encode($segmentResponse, JSON_PRETTY_PRINT) . "<br>";
            }

                // Step 3: Add the contact to the segment
            $addContactToSegmentResponse = $this->addContactToSegmentApi($segmentId, $contactId);
            // Print the full response for debugging
            echo "Response from adding contact to segment: " . json_encode($addContactToSegmentResponse) . "<br>";

            echo "isset('success'): " . (isset($addContactToSegmentResponse['success']) ? 'true' : 'false') . "<br>";

            echo "Success value: " . $addContactToSegmentResponse['success'] . "<br>";

            // Check for success using the specific response format
            if (isset($addContactToSegmentResponse['success']) && $addContactToSegmentResponse['success'] === 1) {
                echo "Contact added to segment successfully.<br>";
            } else {
                echo "Failed to add contact to segment--------: " . json_encode($addContactToSegmentResponse) . "<br>";
            }
            return $addContactToSegmentResponse;

        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
    private function addContactToSegmentApi($segmentId, $contactId) {
        try {
            $response = $this->client->post("segments/$segmentId/contact/$contactId/add");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
        
    }


}
?>
