<?php

/***************************************************************************
 * Check the END OF FILE to get idea of how this function (addCampaign) works
 ****************************************************************************/



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helper/CampaignSerializer.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '//..//..//');
$dotenv->load();

use MauticWrapper\MauticAPI;

class Node
{
    public string $id;
    public int $positionX;
    public int $positionY;

    public function __construct(string $id, int $positionX, int $positionY)
    {
        $this->id = $id;
        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }
}

class Anchors
{
    public string $source;
    public string $target;

    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }
}

class Connection
{
    public string $sourceId;
    public string $targetId;
    public Anchors $anchors;

    public function __construct(string $sourceId, string $targetId, Anchors $anchors)
    {
        $this->sourceId = $sourceId;
        $this->targetId = $targetId;
        $this->anchors = $anchors;
    }
}

class CanvasSetting
{
    public array $nodes = [];
    public array $connections = [];

    public function __construct(array $nodes, array $connections)
    {
        $this->nodes = $nodes;
        $this->connections = $connections;
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}

//Setting the anchor values for each nodes inside canvas settings
function getAnchor($source = 'leadsource', $target = 'top')
{
    return new Anchors($source, $target);
}
function getNodes($id, $positionX, $positionY)
{
    return new Node($id, $positionX, $positionY);
}

function getConnection($sourceId, $targetId, $Anchor)
{
    return new Connection($sourceId, $targetId, $Anchor);
}

function populateCanvasSetting($event, $CampaignType)
{
    $nodes = [];
    $connection = [];
    $positionX = 1;
    $positionY = 350;
    foreach ($event['events'] as $index => $singleEvent) {
        // Update the nodes array with the event ID
        $nodes[] = getNodes($singleEvent['id'], $positionX, $positionY);
        $positionX += 600;
        $connection[] = getConnection($CampaignType, $singleEvent['id'], getAnchor());
    }
    $nodes[] = getNodes(id: $CampaignType, positionX: 753, positionY: 50);


    return new CanvasSetting($nodes, $connection);
}


function getEventId($mautic)
{
    $response = $mautic->getLatestCampaign();
    // Check if 'events' exists in the response and is an array
    if (isset($response['events']) && is_array($response['events']) && count($response['events']) > 0) {
        // Get the ID of the last event
        $lastEventId = $response['events'][count($response['events']) - 1]['id'];
    } else {
        throw new Exception("No events found in the campaign response.");
    }
    return $lastEventId;
}

/************************************************************************
 * This Is the main function the crust of all.
 * Responsible for calling all the API calls and Helper functions
 * It should be called with all the required parameters to make it work correctly.
 * Otherwise it may give exception
 **************************************************************************/


function addCampaign($isPublished, $campaignType, $campaignTypeName, $categoryName, $listOfEvents)
{

    $apiUrl = $_ENV['API_URL'];
    $username = $_ENV['USERNAME'];
    $password = $_ENV['PASSWORD'];

    $apiData = [
        'apiUrl' => $apiUrl,
        'username' => $username,
        'password' => $password
    ];

    // Initialize Mautic API
    $mautic = new MauticAPI($apiUrl, $username, $password);
    $EventId = getEventId($mautic);


    $event = addEvent($apiData, $EventId, $campaignTypeName, $campaignType, $categoryName, $listOfEvents);

    $responseData['campaign']['isPublished'] = $isPublished;
    $responseData['campaign']['name'] = $campaignTypeName;
    $responseData['campaign']['category'] = $event['category'][0];
    $responseData['campaign']['events'] = $event['events'];
    $responseData['campaign']['lists'] = $event['lists'];
    $responseData['campaign']['forms'] = $event['forms'];
    $responseData['campaign']['canvasSettings'] = populateCanvasSetting($event, $campaignType);



    $finalCampaignData = ['name' => $responseData['campaign']['name'], 'events' => $responseData['campaign']['events'], 'forms' => $responseData['campaign']['forms'], 'lists' => $responseData['campaign']['lists'], 'canvasSettings' => $responseData['campaign']['canvasSettings']];

    $response = $mautic->createCampaign($finalCampaignData);

    $newId = $mautic->getLatestCampaign();

    $finalCampaignData = ['name' => $responseData['campaign']['name'], 'isPublished' => $responseData['campaign']['isPublished'], 'category' => $responseData['campaign']['category'], 'events' => $responseData['campaign']['events'], 'lists' => $responseData['campaign']['lists'], 'forms' => $responseData['campaign']['forms']];

    $response = $mautic->updateCampaign($newId['id'], $finalCampaignData);
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    echo '<br><br>';
    echo "<p style='color:green;'>Campaign has been successfully added</p>";
    echo '<br><br>';
}


// This function is changing the date to make it compatible with the data to be added 
class TriggerDateFormatter
{
    public static function formatTriggerDate(array $dateTime)
    {
        // Validate and check each required field in the array
        $requiredFields = ['year', 'month', 'date', 'hour', 'minute'];
        foreach ($requiredFields as $field) {
            if (!isset($dateTime[$field])) {
                throw new InvalidArgumentException("Missing required field: $field.");
            }
            if (!is_int($dateTime[$field])) {
                throw new InvalidArgumentException("Invalid type for field '$field'. Expected integer.");
            }
        }

        // Validate the year, month, day, hour, and minute
        if ($dateTime['year'] < 1970 || $dateTime['year'] > 2100) {
            throw new InvalidArgumentException("Year must be between 1970 and 2100.");
        }
        if ($dateTime['month'] < 1 || $dateTime['month'] > 12) {
            throw new InvalidArgumentException("Month must be between 1 and 12.");
        }

        // Check the day based on month and leap year status
        if (!self::isValidDayForMonth($dateTime['year'], $dateTime['month'], $dateTime['date'])) {
            throw new InvalidArgumentException("Invalid day for the specified month and year.");
        }

        if ($dateTime['hour'] < 0 || $dateTime['hour'] > 23) {
            throw new InvalidArgumentException("Hour must be between 0 and 23.");
        }
        if ($dateTime['minute'] < 0 || $dateTime['minute'] > 59) {
            throw new InvalidArgumentException("Minute must be between 0 and 59.");
        }

        // Format date string in "Y-m-d H:i:s" format
        $dateString = sprintf(
            '%04d-%02d-%02d %02d:%02d:00',
            $dateTime['year'],
            $dateTime['month'],
            $dateTime['date'],
            $dateTime['hour'],
            $dateTime['minute']
        );

        // Try to create a DateTime object from the formatted string
        try {
            $dateTimeObject = DateTime::createFromFormat('Y-m-d H:i:s', $dateString, new DateTimeZone('UTC'));
            if ($dateTimeObject === false) {
                throw new Exception("Failed to create DateTime object from the date string.");
            }
        } catch (Exception $e) {
            throw new RuntimeException("Error formatting date: " . $e->getMessage());
        }

        // Return the DateTime object in ISO 8601 format with timezone offset
        return $dateTimeObject->format(DateTime::ATOM); // ISO 8601 format
    }

    // Helper function to check if a given day is valid for the month and year
    private static function isValidDayForMonth(int $year, int $month, int $day): bool
    {
        // Days in each month
        $daysInMonth = [1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31];

        // Check for leap year and adjust February's days
        if ($month === 2 && self::isLeapYear($year)) {
            $daysInMonth[2] = 29;
        }

        // Validate day range
        return $day >= 1 && $day <= $daysInMonth[$month];
    }

    // Helper function to determine if a year is a leap year
    private static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}

/*************************************************************************************
 * TESTING PHASE starts from here you can remove this part
 **************************************************************************************/


try {
    $dateArray = [
        'year' => 2024,
        'month' => 9,
        'date' => 30, // Invalid day for February in a leap year
        'hour' => 5,
        'minute' => 35
    ];

    // Attempt to format the trigger date
    $triggerDate = TriggerDateFormatter::formatTriggerDate($dateArray);
} catch (Exception $e) {
    // Display the error message and stop further execution
    echo "Error: " . $e->getMessage();
    exit; // Stop the program
}



// Helper array to add the specified unit of time
$triggerIntervalUnit = [
    'minutes' => 'i',
    'hours' => 'h',
    'days' => 'd',
    'months' => 'm',
    'years' => 'y',
];

$listOfEvents = [
    [
        "eventName" => 'Sending emails through API',
        "triggerMode" => 'immediate',
        "triggerDate" => null,
        "triggerInterval" => 1,
        "triggerHour" => null,
        "triggerRestrictedDaysOfWeek" => null,
        "triggerIntervalUnit" => $triggerIntervalUnit['days'],
        "email" => "Triggering",
        "email_type" => "marketing",
        "priority" => "2",
        "attempts" => "3",
        "type" => "email.send",
    ],

];


// use this for adding Form or Segement
$campaignType = 'lists';
//Segment name to add in this campaign
//this name will become name of campign 
$campaignTypeName = 'testingsegment';
//Add the category name which you want to associate other wise add null
$categoryName = 'acc';
//This flag is use to publish the campaign
$isPublished = true;

// Send all the above parameters to the function
addCampaign($isPublished, $campaignType, $campaignTypeName, $categoryName, $listOfEvents);

/**********************************************************************************
     *  First of all you have to provide a campaign template which you want to make
     *  To make a campaign you have to specify the campaignid from the ones given below
     * 
     *  Use this for adding one event : 
     *  numOfEvents = 1
     *  Similarly change the number to add the number of events you want to add
     * 
     *  Then you have to specify the SegmentName which will become the name of Campaign
     *  You must enter the exact name of the Segment Example
     *  Example of a list
     *  $CampaignTypeName='TestingSegment';
     *  Exmaple of a Form
     *  $CampaignTypeName='testingform';
 * 
 *  Add the category name which you want to associate other wise add null
 *  If you have a category:
 *  $categoryName = 'acc'; 
 *  otherwise leave it null:
 *  $categoryName = null; 
 * 
 *  Now specify that you want to publish the campign or not
 *  In case you want it to publish :
 *  $isPublished = true; 
 *  In case you do not want to publish :
 *  $isPublished = false; 
 * 
 *  Then you have to specify the events and their details
 *  You need to make an array which will have arrays of events
 *  Here is an exmaple given below
 * 
$listOfEvents=[
    [
       "eventName"=>'Sending emails through API',
       "triggerMode"=>'immediate',
       "triggerDate"=> null,
       "triggerInterval"=> 1,
       "triggerHour"=> null,
       "triggerRestrictedDaysOfWeek"=>null,
       "triggerIntervalUnit"=> $triggerIntervalUnit['days'],
       "email"=> "golang",
       "email_type"=> "marketing",
       "priority"=> 2,
       "attempts"=> 3,
       "type"=> "email.send",
    ],
    [
        "eventName"=>'Sending emails through API on date',
        "triggerMode"=>'immediate',
        "triggerDate"=> $triggerDate,
        "triggerInterval"=> 1,
        "triggerHour"=> null,
        "triggerRestrictedDaysOfWeek"=>null,
        "triggerIntervalUnit"=> $triggerIntervalUnit['days'],
        "email"=> "golang",
        "email_type"=> "marketing",
        "priority"=> 2,
        "attempts"=> 3,
        "type"=> "email.send",
     ],
         [
        "eventName"=>'Sending emails through API relatively',
        "triggerMode"=>'immediate',
        "triggerDate"=> null,
        "triggerInterval"=> "9",
        "triggerHour"=> '02:00',
        "triggerRestrictedDaysOfWeek"=>[
            1,2,3,4,5
        ],
        "triggerIntervalUnit"=> $triggerIntervalUnit['months'],
        "email"=> "golang",
        "email_type"=> "marketing",
        "priority"=> "2",
        "attempts"=> "3",
        "type"=> "email.send",
    ]
    ];
Most of the will not change but you need to are :

* eventName // add the name you want to give to event
```

* triggerDate //If you want to send mail on specific date 
or that you must change the **$dateArray** 
$dateArray = [
    'year' => 2024,
    'month' => 10,
    'date' => 31,
    'time' => 2
];
Add the values which you want to add in event
```

* triggerInterval // If you want to send relative emails after some days or months or year
you have to specify any valid integer value
```

* triggerHour //In case you want to specify the time of the day at which you want to trigger mail
Simply give the value in time format 02:00
```

* triggerRestrictedDaysOfWeek // In case you want to restrict not to send to any specific day
you have to specify Array
0->sunday,
1->monday and so on
[0,1,2,3] will restruct for Sunday, Monday, Tuesday and Wednesday
```

* triggerIntervalUnit //Give it the value of days, hours or months to send the mail after
For this you just have to soecify what type you want to choose and add that in array
For example for months you have to specify  $triggerIntervalUnit['months']
For example for days you have to specify  $triggerIntervalUnit['days']
```
* email // add the email you want to send to contacts
---->>> You must give the exact name of Email to send mail to else it will not work <<<-----
"email"=> "Triggering", 
```

* email_type // Change this to select the type of mail you want to send

These below fields usually remains the same: 
But can be change if you want just specify the value

*priority=> "2",
*"attempts"=> "3",
*"type"=> "email.send",

********************************************************************************************
*/
