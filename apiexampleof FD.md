
Public
ENVIRONMENT
Sandbox
LAYOUT
Single Column
LANGUAGE
PHP - HTTP_Request2
First API
Introduction
Making a Request
Responses and Status Codes
Platform API
First API
Making a Request
Auth tokens are different between sandbox and production environments.

Auth:
First uses Basic Authentication. Your 'Authorization' header should look something like this (the 'Bearer' prefix is optional, and won't make a difference to the request):

Plain Text
Authorization: Bearer YOUR_API_KEY
Content Type
All API request/response bodies are JSON - the content-type header for all requests must be set to application/json.

Plain Text
Content-Type: application/json
Root URL
All requests to First's APIs should be HTTPS, and to either our sandbox or production environment, under the /api namespace.

Production: https://api.firstdelivery.com/api/

Dashboard is available at https://dispatch.firstdelivery.com
Sandbox: https://sandbox.firstdelivery.com/api/

Dashboard is available at: https://dispatch-v3-sandbox.herokuapp.com/
Sandbox data and authorization are ephemeral and subject to change every few months. Reach out if you need to be re-granted sandbox access!

Retries
We highly reccomend implementing a retry mechanism with exponential backoff for API calls in your critical path (i.e typically Get Provider Quotes and Create Order) where First's response is one of:

408 - Request Timeout

500 - Unexpected error

502 - Bad Gateway

503 - Service Unavailable

Responses and Status Codes
Responses typically follow the format of the previous query, response codes follow typical HTTP best practices:

View More
Status Code	Name	Description
200/201	Success	Everything worked as expected 🎉
400	Bad request	The request didn’t work, often because a required parameter is missing or the content-type hasn’t been set e.g. an incorrect JSON request
401	Unauthorized	Authorization token is invalid, or being used in the wrong environment
403	Forbidden	Requesting user lacks permission to perform
404	Not found	Resource not available on requested endpoint
429	Too many requests	Rate limiter kicked in, scale back request frequency
5xx	Server error	Something went wrong on our end - there will be a TraceID in the response you can send us to help figure out the issue!
POST
Create Runner
https://sandbox.firstdelivery.com/api/v3/runners
Creates a runner in a given territory. The id field in a successful response should be saved on your end - this needs to be sent as the runner_id field when assigning orders.

Field	Data Type	Required	Valid Inputs	Description	Default
name	string	Yes	Any string	Runner name	
email	string	Yes	Valid email address	Runner email	
phone	string	Yes	Valid phone number	Runner phone number	
address	string	Yes	Any string	Runner address	
territory_id	UUID	Yes	Valid UUID	Runner territory id (see GET /territories)	
transport_type	string	No	'car', 'bike'	Transportation type used for scheduling	'car'
profile_pic	string (optional)	No	Valid URI	Runner profile picture	
AUTHORIZATION
Bearer Token
Token
0adf1500-8bf2-11ea-8896-01957a882901

Body
raw (json)
json
{
    "address": "2000 Market Street, Philadelphia PA, 19125",
    "email": "Chase61@yahoo.com",
    "name": "Casey Hand",
    "phone": "335-997-2012",
    "transport_type": "car",
    "territory_id": "db93f018-36e2-416b-8196-5d3a36033440"
}
Example Request
Create Runner
View More
php
<?php
require_once 'HTTP/Request2.php';
$request = new HTTP_Request2();
$request->setUrl('https://sandbox.firstdelivery.com/api/v3/runners');
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setConfig(array(
  'follow_redirects' => TRUE
));
$request->setHeader(array(
  'Authorization' => '0adf1500-8bf2-11ea-8896-01957a882901'
));
$request->setBody('{\n    "address": "2000 Market Street, Philadelphia PA, 19125", \n    "email": "test-runnera@firstdelivery.com", \n    "name": "Mike Smith",\n    "phone": "+15551234561",\n    "transport_type": "car",\n    "territory_id": "db93f018-36e2-416b-8196-5d3a36033440"\n}');
try {
  $response = $request->send();
  if ($response->getStatus() == 200) {
    echo $response->getBody();
  }
  else {
    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
    $response->getReasonPhrase();
  }
}
catch(HTTP_Request2_Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
200 OK
Example Response
Body
Headers (8)
View More
json
{
  "type": "success",
  "user": {
    "id": "fe19da88-5ab9-4693-9786-ede9d8294126",
    "name": "Mike Smith",
    "email": "test-runnera@firstdelivery.com",
    "phone": "+15551234561",
    "address": "2000 Market Street, 2000 Market St, Philadelphia, PA 19103, USA",
    "profile_pic": null,
    "transport_type": "car",
    "archived": false,
    "territory_id": "db93f018-36e2-416b-8196-5d3a36033440"
  }
}
