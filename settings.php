<?php

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $DB;


if ($ADMIN->fulltree) {


        $name = 'restful_token';
        $title = get_string('token', 'webservice_restful');
        $description = get_string('tokendescription', 'webservice_restful');
        $setting = new admin_setting_configtext($name, $title, $description, null);
        $settings->add($setting);  
        $services = $DB->get_records('external_services_functions');
        $options = array('none'=>'none');
        foreach ($services as $service) {
            $options[$service->functionname] = $service->functionname;
        }
        $options=array_unique($options);      
        $name = 'restful_publicapis';
        $title = get_string('apis', 'webservice_restful');
        $description = get_string('apisdescription', 'webservice_restful');
        $setting = new admin_setting_configmultiselect($name, $title, $description, null, $options);
        $settings->add($setting);
    }


