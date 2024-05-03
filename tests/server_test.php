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
 * Restful server tests.
 *
 * @package    webservice_restful
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/webservice/restful/locallib.php');

/**
 * Restful server testcase.
 *
 * @package    webservice_restful
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_restful_server_testcase extends advanced_testcase {

    /**
     * Test get header method extracts HTTP headers.
     */
    public function test_get_headers() {
        $headers = array(
            'USER' => 'www-data',
            'HOME' => '/var/www',
            'HTTP_CONTENT_LENGTH' => '17',
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_USER_AGENT' => 'curl/7.47.0',
            'HTTP_HOST' => 'moodle.local',
            'REDIRECT_STATUS' => '200',
            'SERVER_NAME' => 'moodle.local',
            'SERVER_PORT' => '80',
            'SERVER_ADDR' => '192.168.56.103',
            'REMOTE_PORT' => '39402',
            'REMOTE_ADDR' => '192.168.56.1'
        );
        $expected = array(
            'HTTP_CONTENT_LENGTH' => '17',
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_USER_AGENT' => 'curl/7.47.0',
            'HTTP_HOST' => 'moodle.local',
        );

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_headers');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wstoken method extracts token.
     */
    public function test_get_wstoken() {
        $headers = array(
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',

        );
        $expected = 'e71561c88ca7f0f0c94fee66ca07247b';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_wstoken');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wstoken method correctly errors.
     *
     */
    public function test_get_wstoken_error() {
        $headers = array();
        $this->expectOutputString('{"exception":"moodle_exception",'
                                .'"errorcode":"noauthheader",'
                                .'"message":"No Authorization header found in request sent to Moodle"}');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_wstoken');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);
    }

    /**
     * Test get wsfunction method extracts function.
     */
    public function test_get_wsfunction() {
        $getvars = array('file' => '/core_course_get_courses');
        $expected = 'core_course_get_courses';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_wsfunction');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $getvars); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wsfunction method correctly errors.
     */
    public function test_get_wsfunction_error() {
        $getvars = array();
        $this->expectOutputString('{"exception":"moodle_exception",'
                                .'"errorcode":"nowsfunction",'
                                .'"message":"No webservice function found in URL sent to Moodle"}');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_wsfunction');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $getvars);
    }

    /**
     * Test get response format method extracts response format.
     */
    public function test_get_responseformat() {
        $headers = array(
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/xml',

        );
        $expected = 'json';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_responseformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get response format method correctly errors.
     */
    public function test_get_responseformat_error() {
        $headers = array();
        $this->expectOutputString('{"exception":"moodle_exception",'
                                .'"errorcode":"noacceptheader",'
                                .'"message":"No Accept header found in request sent to Moodle"}');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_responseformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);
    }

    /**
     * Test get request format method extracts request format.
     */
    public function test_get_requestformat() {
        $headers = array(
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/xml',

        );
        $expected = 'xml';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_requestformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get request format method correctly errors.
     */
    public function test_get_requestformat_error() {
        $headers = array();
        $this->expectOutputString('{"exception":"moodle_exception",'
            .'"errorcode":"notypeheader",'
            .'"message":"No Content Type header found in request sent to Moodle"}');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('webservice_restful_server', 'get_requestformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);
    }
}
