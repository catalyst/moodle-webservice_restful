# moodle-webservice_restful
A REStful webservice plugin for Moodle LMS

This plugin allows Moodle's webservice interface to operate in a more RESTFul way.<br/>
Instead of each webservice call having a URL query parameter define what webservice function to use, webservice functions are made available by discrete URLS.

This makes it easier to integrate Moodle with modern interfaces that expect a RESTful interface from other systems.

This plugin also supports sending requests to Moodle webservices using the JSON format.

Finally, by default all Moodle webservice requests return the HTTP status code of 200 regardless of the success or failure of the call. This plugin will return 4XX series status codes if calls are malformed, missing data or unauthorised. This allows external services communicating with Moodle to determine the success or failure of a webservice call without the need to parse the body of the response.

## Why make this Plugin?
There were two related reasons for making this plugin. The first was to solve a technical problem; interfacing Moodle to a service that required each Moodle webservice to be callable from a unique URL. The second was to advance the maturity of Moodle's webservice interface.

The "Richardson Maturity Model" (https://martinfowler.com/articles/richardsonMaturityModel.html) describes the maturity of a web applications API/ webservice interface in a series of levels.

![Maturity Model](/pix/maturity.png?raw=true)

Moodle is currently Level 0 or in the "swamp of POX". As described be Fowler, Moodle "is using HTTP as a tunneling mechanism for your own remote interaction mechanism"

This plugin aims to extend the maturity of Moodle's webservice interface to "Level 1: Resources" by making each webservice function available as a discrete URL.

## Supported Moodle Versions
This plugin currently supports Moodle:

* 3.1
* 3.2
* 3.3
* 3.4

## Moodle Plugin Installation
The following sections outline how to install the Moodle plugin.

### Command Line Installation
To install the plugin in Moodle via the command line: (assumes a Linux based system)

1. Get the code from GitHub or the Moodle Plugin Directory.
2. Copy or clone code into: `<moodledir>/webservice/restful`
3. Run the upgrade: `sudo -u www-data php admin/cli/upgrade` **Note:** the user may be different to www-data on your system.

### User Interface Installation
To install the plugin in Moodle via the Moodle User Interface:

1. Log into your Moodle as an Administrator.
2. Navigate to: *Site administration > Plugins > Install Plugins*
3. Install plugin from Moodle Plugin directory or via zip upload.

## Moodle Plugin Setup
Once the plugin has been installed in Moodle, the following minimal setup is required:

1. Log into your Moodle as an Administrator.
2. Navigate to: *Site administration > Plugins > Webservices > Manage protocols*
3. Enable the RESTful protocol by clicking the "eye icon" in the enable column for this protocol.

## Moodle Webservice Setup
Follow these instructions if you do not currently have any webservies enabled and/or unfamiliar with Moodle webservices.

There are several steps required to setup and enable webservices in Moodle, these are covered in the Moodle documentation that can be found at: https://docs.moodle.org/34/en/Using_web_services

It is recommended you read through these instructions first before attempting Moodle webservice Setup.

## Accepted Content Types
Data can be sent to Moodle webservices using the following encodings:

* application/json
* application/xml
* application/x-www-form-urlencoded

Use the 'Content-Type' HTTP header to notify Moodle which format is being used per request.

## Returned Content Types
Data can be received from Moodle webservices using the following encodings:

* application/json
* application/xml

Use the 'Accept' HTTP header to notify Moodle which format to return per request.

## Differences to Moodle Standard Webservice Interface
When using the RESTful plugin there are several differences to other Moodle webservice plugins, these are summarised below:

* Webservice function as URL (slash parameter)
** Instead of being passed as a query parameter webservice functions are passed in the URL, e.g. https://localhost/webservice/restful/server.php/core_course_get_courses this allows each webservice to appear as a unique URL endpoint.
* Webservice authorisation token as HTTP header
** Instead of being passed as a query parameter, authorisation tokens are passed using the 'Authorization' HTTP Header.
* Moodle response format as HTTP header
** Instead of being passed as a query parameter, the desired Moodle response format ispassed using the 'Accept' HTTP Header.

## Sample Webservice Calls
Below are several examples of how to structure requests using the cURL command line tool.

### JSON Request
The following example uses the core_course_get_courses webservice function to get the course with id 6. The request sent to Moodle and the response received back are both in JSON format.

To use the below example against an actual Moodle instance:
* Replace the {token} variable (including braces) with a valid Moodle authorisation token.
* Relace localhost in the URL in the example with the domain of the Moodle instance you want to use.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'{"options": {"ids":[6]}}' \
"https://localhost/webservice/restful/server.php/core_course_get_courses"
</code></pre>

### XML Request
The following example uses the core_course_get_courses webservice function to get the course with id 6. The request sent to Moodle and the response received back are both in XML format.

To use the below example against an actual Moodle instance:
* Replace the {token} variable (including braces) with a valid Moodle authorisation token.
* Relace localhost in the URL in the example with the domain of the Moodle instance you want to use.

<pre><code>
curl -X POST \
-H "Content-Type: application/xml" \
-H "Accept: application/xml" \
-H 'Authorization: {token}' \
-d'
```xml
<root>
   <options>
      <ids>
         <element>6</element>
      </ids>
   </options>
</root>
```
' \
"https://localhost/webservice/restful/server.php/core_course_get_courses"
</code></pre>

### REST / Form Request
The following example uses the core_course_get_courses webservice function to get the course with id 6. The request sent to Moodle is in REST format and the response received back is in JSON format.

NOTE: This plugin can only accept requests in REST format. Responses must be in JSON or XML format.

To use the below example against an actual Moodle instance:
* Replace the {token} variable (including braces) with a valid Moodle authorisation token.
* Relace localhost in the URL in the example with the domain of the Moodle instance you want to use.

<pre><code>
curl -X POST \
-H "Content-Type: application/x-www-form-urlencoded" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'options[ids][0]=6' \
"https://localhost/webservice/restful/server.php/core_course_get_courses"
</code></pre>

### Mixed Request and Response
This Moodle webservice plug-in allows for requests and responses to be different formats.

The following example uses the core_course_get_courses webservice function to get the course with id 6. The request sent to Moodle is in JSON format and the response received back is in XML format.

To use the below example against an actual Moodle instance:
* Replace the {token} variable (including braces) with a valid Moodle authorisation token.
* Relace localhost in the URL in the example with the domain of the Moodle instance you want to use.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/xml" \
-H 'Authorization: {token}' \
-d'{"options": {"ids":[6]}}' \
"https://localhost/webservice/restful/server.php/core_course_get_courses"
</code></pre>

The received response will look like:

<pre><code>
<RESPONSE>
<MULTIPLE>
<SINGLE>
<KEY name="id"><VALUE>6</VALUE>
</KEY>
<KEY name="shortname"><VALUE>search test</VALUE>
</KEY>
<KEY name="categoryid"><VALUE>1</VALUE>
</KEY>
<KEY name="categorysortorder"><VALUE>10003</VALUE>
</KEY>
...
</code></pre>

# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)


# Contributing and Support

Issues, and pull requests using github are welcome and encouraged! 

https://github.com/catalyst/moodle-webservice_restful/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us

