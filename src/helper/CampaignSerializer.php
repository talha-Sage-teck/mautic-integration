<?php

use PhpParser\Node\Expr\Instanceof_;

use function PHPUnit\Framework\isInstanceOf;
use MauticWrapper\MauticAPI;


// Canvas setting for events to appear on canvas
class CanvasSettings {
    public $droppedX;
    public $droppedY;

    public function __construct($droppedX = null, $droppedY = null) {
        $this->droppedX = $droppedX;
        $this->droppedY = $droppedY;
    }
}


//Properties class to hold any type of properties inside data
class Properties {
    public $canvasSettings;
    public $name;
    public $triggerMode;
    public $triggerDate;
    public $triggerInterval;
    public $triggerIntervalUnit;
    public $triggerHour;
    public $triggerRestrictedDaysOfWeek;
    public $triggerRestrictedStartHour;
    public $triggerRestrictedStopHour;
    public $anchor;
    public $properties;
    public $type;
    public $eventType;
    public $anchorEventType;
    public $campaignId;
    public $_token;
    public $buttons;
    public $email;
    public $email_type;
    public $priority;
    public $attempts;

    public function __construct() {
        $this->canvasSettings = new CanvasSettings();
        $this->properties = new stdClass();
        $this->buttons = new stdClass();
    }
}

// tha main event which holds both canvas and properties
class Event {
    public $id;
    public $name;
    public $description;
    public $type;
    public $eventType;
    public $channel;
    public $channelId;
    public $order;
    public $properties;
    public $triggerDate;
    public $triggerInterval;
    public $triggerIntervalUnit;
    public $triggerHour;
    public $triggerRestrictedStartHour;
    public $triggerRestrictedStopHour;
    public $triggerRestrictedDaysOfWeek;
    public $triggerMode;
    public $decisionPath;
    public $parent;
    public $children;

    public function __construct() {
        $this->properties = new Properties();
        $this->triggerRestrictedDaysOfWeek = [];
        $this->children = [];
    }
}

// Segment/List Class to hold the related attributes
class ListItem {
    public $createdByUser;
    public $modifiedByUser;
    public $id;
    public $name;
    public $publicName;
    public $alias;
    public $description;
    public $category;

    public function __construct() {
        $this->category = null;
    }
}

// Form class to hold the related attributes of form
class Form {
    public $createdByUser;
    public $modifiedByUser;
    public $id;
    public $name;
    public $alias;
    public $category;

    public function __construct() {
        $this->category = null;
    }
}

class Category {
    public $createdByUser;
    public $modifiedByUser;
    public $id;
    public $title;
    public $alias;
    public $description;
    public $color;
    public $bundle;

    public function __construct() {
        $this->modifiedByUser = null;
    }
}


// Main data to send back containing Events, either Lists or Forms
class MainData {
    public $events;
    public $forms;
    public $lists;

    public $category;
    public function __construct() {
        $this->events = [];
        $this->forms = [];
        $this->lists = [];
    }


    public function addEvent(Event $event) {
        $this->events[] = $event;
    }

   
    public function addList(ListItem $listItem) {
        $this->lists[] = $listItem;
    }

    public function addForm(Form $form) {
        $this->forms[] = $form;
    }

    public function addCategory(Category $category) {
        $this->category[] = $category;
    }

    public function toJSON() {
        return json_encode($this);
    }
}


//Fetching the information about the Segments/Lists from DB to add in the events
function getListData($CampaignTypeName, $mautic)
{
    // Get all segments from Mautic
    $response = $mautic->getAllSegments();

    // Check if the response contains segments
    if (isset($response) && is_array($response)) {
        // Iterate through the segments to find the one matching the CampaignTypeName
        foreach ($response as $segment) {
            if (isset($segment['name']) && $segment['name'] === $CampaignTypeName) {
                // Create and populate the ListItem object
                $listItem = new ListItem();
                $listItem->id = $segment['id'];
                $listItem->createdByUser = $segment['createdByUser'];
                $listItem->name = $segment['name']; // Name from the segment
                $listItem->publicName = $segment['publicName'];
                $listItem->alias = $segment['alias'];
                $listItem->description = $segment['description'];
                $listItem->category = $segment['category'] ?? null; // Handle if category is not set

                // Return the populated ListItem object
                return $listItem;
            }
        }
    }

    // Throw an exception if no matching segment is found
    throw new Exception("Segment with name '{$CampaignTypeName}' not found.");
}


function getFormData($CampaignTypeName,$mautic)
{
    // Get all segments from Mautic
    $response = $mautic->getAllForms();

    // Check if the response contains segments
    if (isset($response) && is_array($response)) {
        // Iterate through the segments to find the one matching the CampaignTypeName
        foreach ($response as $form) {
            if (isset($form['name']) && $form['name'] === $CampaignTypeName) {
                // Create and populate the ListItem object
                $formData = new Form();
                $formData->id = $form['id'];
                $formData->createdByUser = $form['createdByUser'];
                $formData->modifiedByUser = $form['modifiedByUser'];
                $formData->name = $CampaignTypeName;
                $formData->alias = $form['alias'];

                // Return the populated ListItem object
                return $formData;
            }
        }
    }

    // Return null if no matching segment is found
    throw new Exception("Segment with name '{$CampaignTypeName}' not found.");
}


function getEmailData($emailName,$mautic){
    $response = $mautic->getAllEmails();
    foreach ($response as $email) {
        if (isset($email['name']) && $email['name'] === $emailName) {

            // Return the populated ListItem object
            return $email['id'];
        }
    }

// Return null if no matching segment is found
throw new Exception("Segment with name '{$emailName}' not found.");
}

function getCategoryData($categoryName, $mautic) {
    // Get all segments from Mautic
    $response = $mautic->getAllCategories();
    print_r($response);
    // Check if the response contains segments
    if (isset($response) && is_array($response)) {
        // Iterate through the segments to find the one matching the CampaignTypeName
        foreach ($response as $row) {
            print_r($row);
            if (isset($row['title']) && $row['title'] === $categoryName) {
                $category = new Category();
                $category->id = $row['id'];
                $category->createdByUser = 'Admin Mautic';
                $category->modifiedByUser =null;
                $category->title = $row['title'];
                $category->alias = $row['alias'];
                $category->description = $row['description'];
                $category->color = $row['color'];
                $category->bundle = $row['bundle'];
    
                return $category;
            }
        }
    }

    // Return null if no match found
    return null;
}


function addEventToMainData($data, $newEventId, $eventData)
{

$event = new Event();
$event->id = $newEventId;
$event->name = $eventData['eventName'];
$event->type = "email.send";
$event->eventType='action';
$event->channel='email';
$event->channelId=$eventData['email'];
$event->order=1;
$event->properties->canvasSettings->droppedX = "208";
$event->properties->canvasSettings->droppedY = "249";
$event->properties->name =$eventData['eventName'];
$event->properties->triggerMode = $eventData['triggerMode'];
$event->properties->triggerInterval=$eventData['triggerInterval'];
$event->properties->triggerDate=$eventData['triggerDate'];
$event->properties->triggerHour=$eventData['triggerHour'];
$event->properties->triggerRestrictedDaysOfWeek=$eventData['triggerRestrictedDaysOfWeek'];
$event->properties->triggerIntervalUnit=$eventData['triggerIntervalUnit'];
$event->properties->anchor="leadsource";
$event->properties->properties->email = $eventData['email'];
$event->properties->properties->email_type = $eventData['email_type'];
$event->properties->properties->priority = "2";
$event->properties->properties->attempts = "3";
$event->properties->type=$eventData['type'];;
$event->properties->eventType='action';
$event->properties->anchorEventType='source';
$event->properties->campaignId = 'mautic_' . hash('sha1', uniqid('', true));
$event->properties->_token=bin2hex(random_bytes(32));
$event->properties->email = $eventData['email'];
$event->properties->email_type =$eventData['email_type'];
$event->properties->priority = 2;
$event->properties->attempts = 3;
$event->triggerIntervalUnit=$eventData['triggerIntervalUnit'];
$event->triggerInterval=$eventData['triggerInterval'];
$event->triggerMode = $eventData['triggerMode'];
$event->triggerDate=$eventData['triggerDate'];
$event->triggerHour=$eventData['triggerHour'];
$event->triggerRestrictedDaysOfWeek=$eventData['triggerRestrictedDaysOfWeek'];
$event->properties->buttons->save='';
$data->addEvent($event);

// this is adding the above created events to the database
return $event;
}

function addEvent($apiData,$eventId, $CampaignTypeName, $CampaignType, $categoryName, $listOfEvents){
$data = new MainData();

$mautic = new MauticAPI($apiData['apiUrl'], $apiData['username'], $apiData['password']);


foreach ($listOfEvents as $eventData) {
    $eventId+=1;
    //Fetching the email ID from the Name
    $emailData=getEmailData($eventData['email'], $mautic);
    $eventData['email']=$emailData;
    // Adding an event toi main data
    $event = addEventToMainData($data, $eventId, $eventData);
}


// Adding the Lists/Segments or Forms to main data
switch( $CampaignType){
    case 'lists':
                $listItem = getListData($CampaignTypeName, $mautic);
                $data->addList($listItem);
                break;
    case  'forms':
                $form = getFormData($CampaignTypeName, $mautic);
                $data->addForm($form);
                break;
}
// Adding the category to main data
$category = getCategoryData($categoryName,$mautic);
$data->addCategory($category);


$response = json_decode(json_encode($data), true); 

return $response;

}
?>

