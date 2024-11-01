<?php

use PhpParser\Node\Expr\Instanceof_;

use function PHPUnit\Framework\isInstanceOf;

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
function getListByCampaignTypeId($CampaignTypeName, $pdo) {
    // Prepare SQL statement to select specified columns from lists table
    $sql = "SELECT created_by_user AS createdByUser, 
                   id, 
                   public_name AS publicName, 
                   alias, 
                   description, 
                   category_id AS category 
            FROM lead_lists 
           WHERE name = :CampaignTypeName LIMIT 1";

    // Debug: Output the SQL query
    echo "Executing query: " . $sql . "\n";
    echo "With parameter: " . $CampaignTypeName . "\n";

    // Prepare statement
    $stmt = $pdo->prepare($sql);

    // Bind parameter and debug
    $stmt->bindParam(':CampaignTypeName', $CampaignTypeName, PDO::PARAM_STR); // Change to PDO::PARAM_STR if name is a string
    echo "Parameter bound: " . $CampaignTypeName . "\n";

    // Execute and fetch
    if ($stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Check if a row was returned
        if ($row) {
            echo "Row fetched successfully: \n";
            print_r($row); // Output the fetched row for inspection
            
            // Create and populate ListItem object
            $listItem = new ListItem();
            $listItem->id = $row['id'];
            $listItem->createdByUser = $row['createdByUser'];
            $listItem->name = $CampaignTypeName;
            $listItem->publicName = $row['publicName'];
            $listItem->alias = $row['alias'];
            $listItem->description = $row['description'];
            $listItem->category = $row['category'];
            
            return $listItem;
        } else {
            // Debug: No rows found
            echo "No row found for Campaign Type Name: " . $CampaignTypeName . "\n";
        }
    } else {
        // Debug: Execution failed
        echo "Query execution failed: " . implode(", ", $stmt->errorInfo()) . "\n";
    }
    
    // Return null if no match found
    return null;
}


//Fetching the information about the Forms from DB to add in the events
function getFormByCampaignTypeId($CampaignTypeName, $pdo) {
    // Prepare SQL statement to select specified columns from forms table
    $sql = "SELECT created_by_user AS createdByUser, 
                   modified_by_user AS modifiedByUser, 
                   id, 
                   alias 
            FROM forms 
            WHERE name = :categoryName LIMIT 1";

    // Debug: Output the SQL query
    echo "Executing query: " . $sql . "\n";
    echo "With parameter: " . $CampaignTypeName . "\n";

    // Prepare statement
    $stmt = $pdo->prepare($sql);

    // Bind parameter and debug
    $stmt->bindParam(':categoryName', $CampaignTypeName, PDO::PARAM_STR); // Use PDO::PARAM_STR if name is a string
    echo "Parameter bound: " . $CampaignTypeName . "\n";

    // Execute and fetch
    if ($stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Check if a row was returned
        if ($row) {
            echo "Row fetched successfully: \n";
            print_r($row); // Output the fetched row for inspection
            
            // Create and populate Form object
            $form = new Form();
            $form->id = $row['id'];
            $form->createdByUser = $row['createdByUser'];
            $form->modifiedByUser = $row['modifiedByUser'];
            $form->name = $CampaignTypeName;
            $form->alias = $row['alias'];
            
            return $form;
        } else {
            // Debug: No rows found
            echo "No row found for Campaign Type Name: " . $CampaignTypeName . "\n";
        }
    } else {
        // Debug: Execution failed
        echo "Query execution failed: " . implode(", ", $stmt->errorInfo()) . "\n";
    }
    
    // Return null if no match found
    return null;
}


//Fetching the category by the given title to add in campaigns
function getCategoryData($categoryName, $pdo) {
    // Prepare SQL query to fetch the required data based on the category name (title)
    $sql = "SELECT id, created_by_user AS createdByUser, modified_by_user AS modifiedByUser, title, alias, description, color, bundle
            FROM categories
            WHERE title = :categoryName LIMIT 1"; // Replace 'categories_table' with the actual table name

    // Prepare the statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);

    // Execute and fetch
    if ($stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // Create and populate Category object
            $category = new Category();
            $category->id = $row['id'];
            $category->createdByUser = $row['createdByUser'];
            $category->modifiedByUser = $row['modifiedByUser'];
            $category->title = $row['title'];
            $category->alias = $row['alias'];
            $category->description = $row['description'];
            $category->color = $row['color'];
            $category->bundle = $row['bundle'];

            return $category;
        }
    }

    // Return null if no match found
    return null;
}


// Storing the Events inside the DB to add that event in campaigns
function addEventToDatabase($event, $campaignId, $pdo, $datetime) {
    try {
        // Prepare the SQL insert statement
        $stmt = $pdo->prepare("
            INSERT INTO campaign_events (
                campaign_id,
                parent_id,
                name,
                description,
                type,
                event_type,
                event_order,
                properties,
                trigger_date,
                trigger_interval,
                trigger_interval_unit,
                trigger_hour,
                trigger_restricted_start_hour,
                trigger_restricted_stop_hour,
                trigger_restricted_dow,
                trigger_mode,
                decision_path,
                temp_id,
                channel,
                channel_id,
                failed_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Prepare the properties field as JSON
        //$propertiesJson = json_encode($event->properties);
        print_r($datetime);

        // Serialize the properties array
        $propertiesArray = [
            'canvasSettings' => [
                'droppedX' => (string)$event->properties->canvasSettings->droppedX,
                'droppedY' => (string)$event->properties->canvasSettings->droppedY,
            ],
            'name' => $event->properties->name,
            'triggerMode' => $event->properties->triggerMode,
            'triggerDate' => $datetime,
            'triggerInterval' => (string)$event->properties->triggerInterval,
            'triggerIntervalUnit' => (string)$event->properties->triggerIntervalUnit,
            'triggerHour' => (string)$event->properties->triggerHour ?? '',
            'triggerRestrictedDaysOfWeek'=> $event->properties->triggerRestrictedDaysOfWeek,
            'triggerRestrictedStartHour' => (string)$event->properties->triggerRestrictedStartHour ?? '',
            'triggerRestrictedStopHour' => (string)$event->properties->triggerRestrictedStopHour ?? '',
            'anchor' => $event->properties->anchor ?? '',
            'properties' => [
                'email' => (string)$event->properties->email,
                'email_type' => $event->properties->email_type,
                'priority' => (string)$event->properties->priority,
                'attempts' => (string)$event->properties->attempts,
            ],
            'type' => $event->properties->type,
            'eventType' => $event->properties->eventType,
            'anchorEventType' => $event->properties->anchorEventType,
            'campaignId' => (string)$event->properties->campaignId,
            '_token' => $event->properties->_token,
            'buttons' => [
                'save' => '', 
            ],
            'email' => (string)$event->properties->email,
            'email_type' => $event->properties->email_type,
            'priority' => (string)$event->properties->priority,
            'attempts' => (string)$event->properties->attempts,
        ];
        
        
        $propertiesSerialized = serialize($propertiesArray);
        $restrictedDowSerialized= serialize( $event->properties->triggerRestrictedDaysOfWeek);
        print_r($propertiesSerialized);
        if($datetime)
        $datetime= $datetime->format("Y-m-d H:i:s");
        echo '<br>'.$datetime.'<br>';
        $stmt->bindValue(1, $campaignId); 
        $stmt->bindValue(2, null); 
        $stmt->bindValue(3, $event->name); 
        $stmt->bindValue(4, null);
        $stmt->bindValue(5, $event->type); 
        $stmt->bindValue(6, $event->eventType); 
        $stmt->bindValue(7, $event->order); 
        $stmt->bindValue(8, $propertiesSerialized); 
        $stmt->bindValue(9, $datetime); 
        $stmt->bindValue(10, $event->properties->triggerInterval); 
        $stmt->bindValue(11, $event->properties->triggerIntervalUnit); 
        $stmt->bindValue(12, $event->properties->triggerHour); 
        $stmt->bindValue(13, $event->properties->triggerRestrictedStartHour); 
        $stmt->bindValue(14, $event->properties->triggerRestrictedStopHour);
        $stmt->bindValue(15,  $restrictedDowSerialized); 
        $stmt->bindValue(16, $event->properties->triggerMode); 
        $stmt->bindValue(17, null); 
        $stmt->bindValue(18, null);
        $stmt->bindValue(19, $event->channel); 
        $stmt->bindValue(20, $event->channelId); 
        $stmt->bindValue(21, 0); 

        // Execute the prepared statement
        $stmt->execute();

        echo "Event added successfully.";
    } catch (PDOException $e) {
        echo "Error adding event: " . $e->getMessage();
     }
}

function getEmailIdByName($emailName, $pdo) {
    // SQL query to fetch the email ID based on the name
    $sql = "SELECT id FROM emails WHERE name = :emailName"; 
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':emailName', $emailName, PDO::PARAM_STR);

    // Execute and fetch the email ID
    if ($stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['id'];
        }
    }
    
    // Return null if no match found
    return null;
}


function addEventToMainData($data, $newEventId, $eventData)
{

echo '<pre>';
print_r($eventData['triggerDate']);
echo '</pre>';

if($eventData['triggerDate'])
$eventData['triggerDate']=$eventData['triggerDate']->format("Y-m-d H:i:s");

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

function getEventId($pdo){
     // Query to get the latest id from the events table
     $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM campaign_events");
     $result = $stmt->fetch(PDO::FETCH_ASSOC);
 
     // Increment the latest id by 1
     $newEventId = $result['max_id'] + 1;
     return $newEventId;
}

function addEvent($campaignId, $CampaignTypeName, $CampaignType, $categoryName, $listOfEvents){
$data = new MainData();
$newEventId =0;
try {

    // Database connection (Update with your DDEV database credentials)

    // ****************************************************************
    $dsn = 'mysql:host=127.0.0.1;port=50676;dbname=db;charset=utf8';
    $username = 'db'; 
    $password = 'db'; 
    // ****************************************************************

    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password);

    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}



foreach ($listOfEvents as $eventData) {
    $datetime=$eventData['triggerDate'];
    $newEventId=getEventId($pdo);
    //Fetching the email ID from the Name
    $emailId=getEmailIdByName($eventData['email'], $pdo);
    $eventData['email']=$emailId;
    // Adding an event toi main data
    $event = addEventToMainData($data, $newEventId, $eventData);
    // this is adding the above created events to the database
    
    addEventToDatabase($event, $campaignId, $pdo,$datetime);
}


// Adding the Lists/Segments or Forms to main data
switch( $CampaignType){
    case 'lists':
        echo "lists1";
                $listItem = getListByCampaignTypeId($CampaignTypeName, $pdo);
                $data->addList($listItem);
                break;
    case 'forms':
                $form = getFormByCampaignTypeId($CampaignTypeName, $pdo);
                $data->addForm($form);
                break;
}
// Adding the category to main data
$category = getCategoryData($categoryName,$pdo);
$data->addCategory($category);


$response = json_decode(json_encode($data), true); 
//$response=json_encode($data);
echo "<pre>";
print_r($response);
echo "</pre>";

return $response;

}
?>

