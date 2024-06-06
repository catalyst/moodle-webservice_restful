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
header('Content-Type: text/yaml');




$openapi = new stdClass();
$openapi->openapi = "3.0.0";
$openapi->servers = array( (object) array('url' => 'http:/moodle.localhost/webservice/restful/server.php'));
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
    )
); 
$openapi->security = array(
    array('MoodleAuthToken' => array())
);

$openapi->paths = (object) array();
$functions = $DB->get_records('external_functions', [], 'name');
foreach ($functions as $function) {
    $openapi->paths->{'/'.$function->name} = moodle_webservice_function_to_openapi_path($function->name);
}


function moodle_webservice_function_to_openapi_path($function) {
    $info = external_api::external_function_info($function);

    $path = new stdClass();
    $method = 'post';
    $path->$method = new stdClass();
    //$path->$method->tags = array($info->classname);
    $path->$method->summary = "$info->name";
    $path->$method->description = $info->description;
    if ($info->deprecated) {
        $path->$method->deprecated = true;
    }
    $path->$method->responses = moodle_webservice_returns_desc_to_openapi_responses($info->returns_desc);
    if (($schema = moodle_external_description_to_openapi_schema($info->parameters_desc)) !== null) {
        $path->$method->requestBody = new stdClass();
        $path->$method->requestBody->content = new stdClass();
        $path->$method->requestBody->content->{'application/json'} = new stdClass();
        $path->$method->requestBody->content->{'application/json'}->schema = $schema;    
    }
        
    return $path;


}

function moodle_webservice_returns_desc_to_openapi_responses($response) {
    $responses = new stdClass();
    
    $responses->default = new stdClass();
    $responses->default->description = "this plugin will return 4XX series status codes if calls are malformed, missing data or unauthorised.";

    $responses->{200} = new stdClass();
    $responses->{200}->description = "this plugin will return 200 even if the call fails.";
    if (($schema = moodle_external_description_to_openapi_schema($response)) !== null) {
        $responses->{200}->content = new stdClass();
        $responses->{200}->content->{'application/json'} = new stdClass();
        $responses->{200}->content->{'application/json'}->schema = $schema;
    }
    return $responses;
}

function moodle_external_description_to_openapi_schema(?external_description $value) {
    if ($value === null) {
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

    if ($schema === null) {
        return null;
    }

    if ($value->allownull) {
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
        if ($item_schema === null) {
            continue;
        }
        $schema->properties[$key] = $item_schema;
        if ($keyvalue->required) {
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
    if ($schema->items === null) {
        return null;
    }
    return $schema;
}

function moodle_external_value_to_openapi_schema(external_value $value) {
    $schema = new stdClass();
    $schema->description = $value->desc;
    if ($value->default !== null) {
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

