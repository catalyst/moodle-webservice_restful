<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin settings.
 *
 * @package    webservice_restful
 * @copyright   (c) 2024, Enovation Solutions
 * @author     Lai Wei <lai.wei@enovation.ie>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Define the settings page.
    $settings = new admin_settingpage('webservice_restful', get_string('pluginname', 'webservice_restful'));

    // Support bearer token authentication.
    $settings->add(new admin_setting_configcheckbox('webservice_restful/supportbearertokenauth',
        get_string('supportbearertokenauth', 'webservice_restful'),
        get_string('supportbearertokenauthdesc', 'webservice_restful'), 1));
}
