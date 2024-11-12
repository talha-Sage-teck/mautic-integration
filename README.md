# Mautic-Custom-API
This is mautic API for adding campaigns
You need to add this folder in htdocs
To access this api navigate to:
http://localhost:8080/mautic-api/?endpoint=campaigns
Before moving on you have to update the <i>src\endpoints\campaigns.php</i> file with your own data<br>
All the information about how to add the data is provided there<br>

## Working Flow
It will first need some inputs to function correctly, provide them in the above mentioned file or Pass in as parameter from somewhere else by calling the function.<br>
This will then create the type of campaign you want to add such as 
<ul>
  <li>One event</li>
  <li>Two events</li>
  <li>Three events</li>
</ul>
<br>
Then it will move to add the events information in the database based on the information younhave provided.
It will fetch some ither required data from the database
<ul>
</ul><li>Lists/Segments</li>
<li>Forms</li>
<li>Category</li>  
</ul>
<br>
Then convert these whole information into JSON to send a PUT request to that campaign.

## Function implementation logic
Moving onto the functions in 
<i>src\endpoints\campaigns.php</i>
<br>
### addCampaign()
<br>
This is the main function for calling all the campaign needed operations from cloning to editing the newly created campaigns.
<br>
Moving onto the functions in 
src\helper\CampaignSerializer.php
<br>

### addEvent()
<br>
This function is responsible for managing all the workingof events.
From calling the function to serialize the data.
From calling adding the events, Lists, categories to main data to send back in JSON form.
<br>

### addEventToMainData()
This function will assign the values to the events which will then b e sent using JSON format.
Then this will add the uodated values to the main data.

###  getEventId()
This function is reponsible for getting the event id by calling the API and getting the recent event id. If you do not have any recent campaign it will assume the id to be one and returns it.

### Helper Functions
#### getEmailData()
This will access the API to fetch the information about the email by name which the user wants to send in each events.
#### getCategoryData()
This will access the API to fetch the information about the category by name which the user wants to add in each campaign.
#### getFormData()
This will access the API to fetch the information about the Contact Form by name which the user wants to add in Event.
#### getListData() 
This will access the API to fetch the information about the Lists/Segments by name which the user wants to add in Event.
####  getAnchor()
Simply populates the value passed and return an Anchor object.
####  getNodes()
Simply populates the value passed and return an Nodes object.
####  getConnection()
Simply populates the value passed and return an Connection object.
####  populateCanvasSetting()
This will take nodes and connection objects needed for making CanvasSetting


