# moodle-webservice_restful
A REStful webservice plugin for Moodle LMS

This plugin allows Moodle's webservice interface to operate in a more RESTFul way.<br/>
Instead of each webservice call having a URL query parameter define what webservice function to use, webservice functions are made to discrete URLS.

This makes it easier to integrate Moodle with modern interfaces that expect a RESTful interface.

## Why make this Plugin?
TODO

## Supported Moodle Versions
This plugin currently supports Moodle:

* 3.1
* 3.2
* 3.3
* 3.4

## Moodle Plugin Installation
The following sections outline hoe to install the Moodle plugin.

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
TODO

## Moodle Webservice Setup
Follow these instructions if you do not currently have any webservies enabled and/or unfamiliar with Moodle webservices.

TODO

## Accepted Content Types
TODO

## Returned Content Types
TODO

## Differences to Moodle Standard Webservice Interface
TODO

## Sample Webservice Calls
Below are several examples of how to structure requests using the cURL command line tool.

### JSON Request
TODO

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
"https://localhost/webservice/restful/server.php/core_course_get_courses"
</code></pre>

### XML Request
TODO

### REST / Form Request
TODO

### JSON Response
TODO

### XML Response
TODO

### Mixed Request and Response
TODO

# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)


# Contributing and Support

Issues, and pull requests using github are welcome and encouraged! 

https://github.com/catalyst/moodle-search_elastic/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us