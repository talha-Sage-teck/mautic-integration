<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helper/CampaignSerializer.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '//..//..//');
$dotenv->load();

use MauticWrapper\MauticAPI;

/********************************************************
 * Check the end of file to get idea of how this function (addCampaign) works
 * 
 * 
 * 
 *********************************************************/

function getEventId($mautic){
    $response = $mautic->getLatestCampaign();
    print_r($response );


    // Check if 'events' exists in the response and is an array
    if (isset($response['events']) && is_array($response['events']) && count($response['events']) > 0) {
        // Get the ID of the last event
        $lastEventId = $response['events'][count($response['events']) - 1]['id'];
    
        echo '<br><b>Last Event ID: ' . $lastEventId . '</b><br>';
    } else {
        throw new Exception("No events found in the campaign response.");
    }
    return $lastEventId ;
}

function addCampaign($isPublished ,$numOfEvents,$CampaignTypeName, $categoryName, $listOfEvents){

    $apiUrl = $_ENV['API_URL'];
    $username = $_ENV['USERNAME'];
    $password = $_ENV['PASSWORD'];

    $apiData=[
        'apiUrl'=>$apiUrl,
        'username'=>$username,
        'password'=> $password
    ];
  
    // Initialize Mautic API
    $mautic = new MauticAPI($apiUrl, $username, $password);
    $EventId=getEventId($mautic);
   
    switch($numOfEvents){
        case 1:
            $oldCampaignId=273;
            break;
        case 2:
            $oldCampaignId=136;
            break;
        case 3:
            $oldCampaignId=137;
            break;
        default:
        throw new Exception("No template found in the campaign response.");
    }
    // Change this into clone a campaign id
    $responseData = $mautic->cloneCampaign($oldCampaignId);

    $campaignId=$responseData ['campaign']['id'];
    $CampaignType=$responseData['campaign']['canvasSettings']['connections'][0]['sourceId'];
   
    $event=addEvent($apiData,$EventId, $CampaignTypeName, $CampaignType, $categoryName, $listOfEvents);
    echo $CampaignType;

    $responseData['campaign']['isPublished']=$isPublished;
    $responseData['campaign']['name']=$CampaignTypeName;
    $responseData['campaign']['category']=$event['category'][0];
    $responseData['campaign']['events']=$event['events'];
    $responseData['campaign']['lists']=$event['lists'];
    $responseData['campaign']['forms']=$event['forms'];

    // Assuming $event['events'] is an array of events
    foreach ($event['events'] as $index => $singleEvent) {
        // Update the nodes array with the event ID
        $responseData['campaign']['canvasSettings']['nodes'][$index]['id'] = $singleEvent['id'];

        // Update the connections array with the event ID (make sure connections array is large enough)
        if (isset($responseData['campaign']['canvasSettings']['connections'][$index])) {
            $responseData['campaign']['canvasSettings']['connections'][$index]['targetId'] = $singleEvent['id'];
        }
    }



    $finalCampaignData = ['name'=>$responseData['campaign']['name'], 'isPublished'=> $responseData['campaign']['isPublished'],'category'=>$responseData['campaign']['category'],'events'=> $responseData['campaign']['events'],'forms'=>$responseData['campaign']['forms'],'lists'=> $responseData['campaign']['lists'], 'canvasSettings'=>$responseData['campaign']['canvasSettings']];

    echo '<pre>';
    print_r (json_encode($finalCampaignData));
    echo '</pre>';

    //$response=$mautic->createCampaign($finalCampaignData);
    $response=$mautic->updateCampaign($campaignId, $finalCampaignData);
    print_r($response);

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
    echo $triggerDate; // If successful, it outputs the formatted date

} catch (Exception $e) {
    // Display the error message and stop further execution
    echo "Error: " . $e->getMessage();
    exit; // Stop the program
}



// Helper array to add the specified unit of time
$triggerIntervalUnit=[
'minutes'=>'i',
'hours'=>'h',
'days'=>'d',
'months'=>'m',
'years'=>'y',
];

$listOfEvents=[
    [
       "eventName"=>'Sending emails through API',
       "triggerMode"=>'immediate',
       "triggerDate"=> null,
       "triggerInterval"=> 1,
       "triggerHour"=> null,
       "triggerRestrictedDaysOfWeek"=>null,
       "triggerIntervalUnit"=> $triggerIntervalUnit['days'],
       "email"=> "Triggering",
       "email_type"=> "marketing",
       "priority"=> "2",
       "attempts"=> "3",
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


// use this for adding number of events you want to add
$numOfEvents=3;

//Segment name to add in this campaign
//this name will become name of campign 
$CampaignTypeName='testingsegment';
//Add the category name which you want to associate other wise add null
$categoryName = 'acc';
$isPublished = true;
addCampaign($isPublished,$numOfEvents, $CampaignTypeName, $categoryName, $listOfEvents);

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













?>


