<?php
define('NO_DEBUG_DISPLAY', true);
define('WS_SERVER', true);

require('../../config.php');
require_once("$CFG->dirroot/webservice/restful/locallib.php");

if (!webservice_protocol_is_enabled('restful')) {
    header("HTTP/1.0 403 Forbidden");
    debugging('The server died because the web services or the REST protocol are not enable',
        DEBUG_DEVELOPER);
    die;
}

// echo yaml header
header('Content-Type: application/json');
header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
header('Pragma: no-cache');
header('Accept-Ranges: none');
// Allow cross-origin requests only for Web Services.
// This allow to receive requests done by Web Workers or webapps in different domains.
header('Access-Control-Allow-Origin: *');

$openapi = new stdClass();
$openapi->openapi = "3.0.1";
$openapi->servers = array( (object) array('url' => (string)new moodle_url('/webservice/restful/server.php')));
$openapi->info = (object) array(
	'title' => 'Moodle Web Services',
	'description' => 'Moodle Web Services API',
	'version' => '1.0.0',
	'contact' => (object) array(
		"name"=> "Epitech DDOS",
		"url" => "https://moodle.org/support"
    )
);
$openapi->components = (object) array(
    'schemas' => (object) array(),
    'securitySchemes' => (object) array(
        'MoodleAuthToken' => (object) array(
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'Authorization'
        )
    ),
    'responses' => (object) array(
        '4XXError' => (object) array(
            'description' => '4XX series status codes indicate a problem with the request. The request is missing data, malformed or unauthorised.',
            'content' =>  array(
                'application/json' => (object) array(
                    'schema' => (object) array(
                        'type' => 'object',
                        'properties' => (object) array(
                            'exception' => (object) array(
                                'type' => 'string',
                                'description' => 'The exception message'
                            ),
                            'errorcode' => (object) array(
                                'type' => 'string',
                                'description' => 'The error code'
                            ),
                            'message' => (object) array(
                                'type' => 'string',
                                'description' => 'The error message'
                            ),
                            'debuginfo' => (object) array(
                                'type' => 'string',
                                'description' => 'The debug information'
                            )

                        )
                    )
                )
            )
        )
    )
);

$openapi->security = array(
    array('MoodleAuthToken' => array())
);

$openapi->paths = (object) array();
$functions = $DB->get_records('external_functions', [], 'name');
foreach ($functions as $function) {
    $openapi->paths->{'/'.$function->name} = moodle_webservice_function_to_openapi_path($function->name, $openapi);
}


function moodle_webservice_function_to_openapi_path($function, $openapi) {
    $info = external_api::external_function_info($function);

    $path = new stdClass();
    $method = 'post';
    $path->$method = new stdClass();
    $path->$method->summary = "$info->name";
    $path->$method->operationId = $info->name;
    $path->$method->description = $info->description;
    $path->$method->tags = array(
        $info->component
    );
    if (isset($info->deprecated) && ($info->deprecated === true)) {
        $path->$method->deprecated = true;
    }
    $path->$method->responses = moodle_webservice_returns_desc_to_openapi_responses($info->returns_desc, $info->name."_response", $openapi);
    if (($schema = moodle_external_description_to_openapi_schema($info->parameters_desc)) !== null) {
        $name = $info->name.'_request';
        $openapi->components->schemas->$name = $schema;
        $path->$method->requestBody = new stdClass();
        $path->$method->requestBody->content = new stdClass();
        $path->$method->requestBody->content->{'application/json'} = new stdClass();
        $path->$method->requestBody->content->{'application/json'}->schema = (object) array('$ref' => "#/components/schemas/$name");
    }
    return $path;
}

function moodle_webservice_returns_desc_to_openapi_responses($response, $name, $openapi) {
    $responses = new stdClass();
    
    $responses->default = (object) array('$ref' => "#/components/responses/4XXError");
    $responses->{200} = new stdClass();
    $responses->{200}->description = "this plugin will return 200 even if the call fails.";
    if (($schema = moodle_external_description_to_openapi_schema($response)) !== null) {
        $openapi->components->schemas->$name = $schema;
        $responses->{200}->content = new stdClass();
        $responses->{200}->content->{'application/json'} = new stdClass();
        $responses->{200}->content->{'application/json'}->schema = (object) array('$ref' => "#/components/schemas/$name");
    }
    return $responses;
}

function moodle_external_description_to_openapi_schema(?external_description $value) {
    if (is_null($value)) {
        return null;
    }
    $schema = null;
    if ($value instanceof external_single_structure) {
        $schema = moodle_external_single_structure_to_openapi_schema($value);
    } else if ($value instanceof external_multiple_structure) {
         $schema = moodle_external_multiple_structure_to_openapi_schema($value);
     } else if ($value instanceof external_value) {
         $schema = moodle_external_value_to_openapi_schema($value);
     } else {
        return null;
    }

    if (is_null($schema)) {
        return null;
    }

    if (isset($value->allownull) && ($value->allownull === true)) {
        $schema->nullable = true;
    }

    return $schema;
}

function moodle_external_single_structure_to_openapi_schema(external_single_structure $value) {
    $schema = new stdClass();
    $schema->description = $value->desc;
    $schema->type = "object";
    $schema->required = [];
    $schema->properties = [];

    foreach ($value->keys as $key => $keyvalue) {
        $item_schema = moodle_external_description_to_openapi_schema($keyvalue);
        if (is_null($item_schema)) {
            continue;
        }
        $schema->properties[$key] = $item_schema;
        if (isset($keyvalue->required)) {
            $schema->required[] = "$key";
        }
    }

    if (empty($schema->properties)) {
        return null;
    }
    if (empty($schema->required)) {
        unset($schema->required);
    }
    return $schema;
}

function moodle_external_multiple_structure_to_openapi_schema(external_multiple_structure $value) {
    $schema = new stdClass();
    $schema->description = $value->desc;
    $schema->type = "array";
    $schema->items = new stdClass();
    $schema->items = moodle_external_description_to_openapi_schema($value->content);
    if (is_null($schema->items)) {
        return null;
    }
    return $schema;
}

function moodle_external_value_to_openapi_schema(external_value $value) {
    $schema = new stdClass();
    $schema->description = $value->desc;
    if (isset($value->default)) {
        $schema->default = $value->default;
    }
    switch ($value->type) {
        case 'int':
            $schema->type = "integer";
            break;
        case 'string':
            $schema->type = "string";
            break;
        case 'bool':
            $schema->type = "boolean";
            break;
        case 'float':
            $schema->type = "number";
            break;
        default:
            $schema->type = "string";
            break;
    }
    return $schema;
}

echo json_encode($openapi, JSON_PRETTY_PRINT);

