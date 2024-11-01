<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helper/CampaignSerializer.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '//..//..//');
$dotenv->load();

use MauticWrapper\MauticAPI;



function addCampaign($isPublished ,$oldCampaignId,$CampaignTypeName, $categoryName, $listOfEvents){

    $apiUrl = $_ENV['API_URL'];
    $username = $_ENV['USERNAME'];
    $password = $_ENV['PASSWORD'];

    // Initialize Mautic API
    $mautic = new MauticAPI($apiUrl, $username, $password);


    // Change this into clone a campaign id
    $responseData = $mautic->cloneCampaign($oldCampaignId);

    $campaignId=$responseData ['campaign']['id'];
    $eventName='Sending email through API';
    $CampaignType=$responseData['campaign']['canvasSettings']['connections'][0]['sourceId'];

    echo "<pre>";
    print_r($responseData);
    echo "</pre>";

    $event=addEvent($campaignId, $CampaignTypeName, $CampaignType, $categoryName, $listOfEvents);
    echo $CampaignType;

    echo "<pre>";
    print_r($event);
    echo "</pre>";

    print_r($event['events']);

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

    echo '<pre>';
    print_r (json_encode($responseData));
    echo '</pre>';

    $finalCampaignData = ['name'=>$responseData['campaign']['name'], 'isPublished'=> $responseData['campaign']['isPublished'],'category'=>$responseData['campaign']['category'],'events'=> $responseData['campaign']['events'],'forms'=>$responseData['campaign']['forms'],'lists'=> $responseData['campaign']['lists'], 'canvasSettings'=>$responseData['campaign']['canvasSettings']];

    echo '<pre>';
    print_r (json_encode($finalCampaignData));
    echo '</pre>';

    $response=$mautic->updateCampaign($campaignId, $finalCampaignData);
    print_r($response);

    echo '<br><br>';
    echo "<p style='color:green;'>Campaign has been successfully added</p>";
    echo '<br><br>';

}


// This function is changing the date to make it compatible with the data to be added
function formatTriggerDate($dateTime) {
    // Ensure the input is an array and contains required keys
    if (!is_array($dateTime) || 
        !isset($dateTime['date']) || 
        !isset($dateTime['month']) || 
        !isset($dateTime['year']) || 
        !isset($dateTime['time'])) {
        throw new InvalidArgumentException("Invalid dateTime array provided.");
    }

    // Convert array values into a date string in "Y-m-d H:i:s" format
    $dateString = sprintf(
        '%04d-%02d-%02d %02d:00:00', 
        $dateTime['year'], 
        $dateTime['month'], 
        $dateTime['date'], 
        $dateTime['time']
    );

    // Create a DateTime object from the formatted string in UTC
    $dateTimeObject = DateTime::createFromFormat('Y-m-d H:i:s', $dateString, new DateTimeZone('UTC'));

    // Return the date in ISO 8601 format with timezone offset
    return $dateTimeObject;
}

// Example usage:
$dateArray = [
    'year' => 2024,
    'month' => 10,
    'date' => 31,
    'time' => 2
];
$triggerDate=formatTriggerDate($dateArray); // Output: "2024-10-31T02:00:00+00:00"

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
        "eventName"=>'Sending emails through API relatively',
        "triggerMode"=>'immediate',
        "triggerDate"=> null,
        "triggerInterval"=> "9",
        "triggerHour"=> '02:00',
        "triggerRestrictedDaysOfWeek"=>[
            1,2,3,4,5
        ],
        "triggerIntervalUnit"=> $triggerIntervalUnit['months'],
        "email"=> "Triggering",
        "email_type"=> "marketing",
        "priority"=> "2",
        "attempts"=> "3",
        "type"=> "email.send",
    ]
    ];


// use this for adding two event
$oldCampaignId=136;

//Segment name to add in this campaign
//this name will become name of campign 
$CampaignTypeName='testingsegment';

//Add the category name which you want to associate other wise add null
$categoryName = 'acc';
$isPublished = true;
addCampaign($isPublished,$oldCampaignId, $CampaignTypeName, $categoryName, $listOfEvents);

/**********************************************************************************
 *  First of all you have to provide a campaign template which you want to make
 *  To make a campaign you have to specify the campaignid from the ones given below
 * 
 *  Use this for adding one event : 
 *  $oldCampaignId=273;
 *  Use this for adding two event : 
 *  $oldCampaignId=136;
 *  Use this for adding three event :  
 *  $oldCampaignId=137;
 * 
 *  Then you have to specify the SegmentName which will become the name of Campaign
 *  You must enter the exact name of the Segment Example
 *  $CampaignTypeName='TestingSegment';
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


