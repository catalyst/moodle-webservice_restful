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

namespace webservice_restful;

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
class server_test extends \advanced_testcase {

    /**
     * Test get header method extracts HTTP headers.
     *
     * @covers ::get_headers()
     */
    public function test_get_headers(): void {
        $headers = [
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
            'REMOTE_ADDR' => '192.168.56.1',
        ];
        $expected = [
            'HTTP_CONTENT_LENGTH' => '17',
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_USER_AGENT' => 'curl/7.47.0',
            'HTTP_HOST' => 'moodle.local',
        ];

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_headers');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wstoken method extracts token.
     *
     * @covers ::get_wstoken()
     */
    public function test_get_wstoken(): void {
        $headers = [
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $expected = 'e71561c88ca7f0f0c94fee66ca07247b';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_wstoken');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wstoken method correctly errors.
     *
     * @covers ::get_wstoken()
     */
    public function test_get_wstoken_error(): void {
        $headers = [];

        // Capture the output instead of expecting a specific string
        ob_start();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_wstoken');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);

        $output = ob_get_clean();

        // Parse JSON and verify individual components
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded, 'Output should be valid JSON');
        $this->assertStringEndsWith('moodle_exception', $decoded['exception']);
        $this->assertEquals('noauthheader', $decoded['errorcode']);
        $this->assertEquals('No Authorization header found in request sent to Moodle', $decoded['message']);
    }

    /**
     * Test get wsfunction method extracts function.
     *
     * @covers ::get_wsfunction()
     */
    public function test_get_wsfunction(): void {
        $getvars = ['file' => '/core_course_get_courses'];
        $expected = 'core_course_get_courses';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_wsfunction');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $getvars); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get wsfunction method correctly errors.
     *
     * @covers ::get_wsfunction()
     */
    public function test_get_wsfunction_error(): void {
        $getvars = [];

        // Capture the output instead of expecting a specific string
        ob_start();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_wsfunction');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $getvars);

        $output = ob_get_clean();

        // Parse JSON and verify individual components
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded, 'Output should be valid JSON');
        $this->assertStringEndsWith('moodle_exception', $decoded['exception']);
        $this->assertEquals('nowsfunction', $decoded['errorcode']);
        $this->assertEquals('No webservice function found in URL sent to Moodle', $decoded['message']);
    }

    /**
     * Test get response format method extracts response format.
     *
     * @covers ::get_responseformat()
     */
    public function test_get_responseformat(): void {
        $headers = [
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];
        $expected = 'json';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_responseformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get response format method correctly errors.
     *
     * @covers ::get_responseformat()
     */
    public function test_get_responseformat_error(): void {
        $headers = [];

        // Capture the output instead of expecting a specific string
        ob_start();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_responseformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);

        $output = ob_get_clean();

        // Parse JSON and verify individual components
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded, 'Output should be valid JSON');
        $this->assertStringEndsWith('moodle_exception', $decoded['exception']);
        $this->assertEquals('noacceptheader', $decoded['errorcode']);
        $this->assertEquals('No Accept header found in request sent to Moodle', $decoded['message']);
    }

    /**
     * Test get request format method extracts request format.
     *
     * @covers ::get_requestformat()
     */
    public function test_get_requestformat(): void {
        $headers = [
            'HTTP_AUTHORIZATION' => 'e71561c88ca7f0f0c94fee66ca07247b',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];
        $expected = 'xml';

        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_requestformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $headers); // Get result of invoked method.

        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test get request format method correctly errors.
     *
     * @covers ::get_requestformat()
     */
    public function test_get_requestformat_error(): void {
        $headers = [];

        // Capture the output instead of expecting a specific string
        ob_start();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('webservice_restful_server', 'get_requestformat');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \webservice_restful_server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN), $headers);

        $output = ob_get_clean();

        // Parse JSON and verify individual components
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded, 'Output should be valid JSON');
        $this->assertStringEndsWith('moodle_exception', $decoded['exception']);
        $this->assertEquals('notypeheader', $decoded['errorcode']);
        $this->assertEquals('No Content Type header found in request sent to Moodle', $decoded['message']);
    }

    /**
     * Test send_headers method sets correct CORS headers with origin.
     *
     * @covers ::send_headers()
     */
    public function test_send_headers_with_origin(): void {
        global $CFG;

        // Skip test if headers_list function is not available (CLI environment)
        if (PHP_SAPI === 'cli') {
            $this->markTestSkipped('Cannot test headers in CLI environment');
        }

        // Mock the server class
        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // Set up $_SERVER with an origin
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';

        // Use reflection to access the protected method
        $method = new \ReflectionMethod('webservice_restful_server', 'send_headers');
        $method->setAccessible(true);

        // Use output buffering to capture headers
        ob_start();
        $method->invoke($stub, 200);
        ob_end_clean();

        // Get all headers that were set
        $headers = headers_list();

        // Check that the correct CORS headers were set
        $this->assertContains('Access-Control-Allow-Origin: https://example.com', $headers);
        $this->assertContains('Access-Control-Allow-Credentials: true', $headers);
        $this->assertContains('Access-Control-Allow-Methods: POST, GET, OPTIONS', $headers);
        $this->assertContains('Access-Control-Allow-Headers: Content-Type, Authorization', $headers);
        $this->assertContains('Access-Control-Max-Age: 86400', $headers);

        // Clean up
        unset($_SERVER['HTTP_ORIGIN']);
    }

    /**
     * Test send_headers method sets correct CORS headers without origin.
     *
     * @covers ::send_headers()
     */
    public function test_send_headers_without_origin(): void {
        global $CFG;

        // Skip test if headers_list function is not available (CLI environment)
        if (PHP_SAPI === 'cli') {
            $this->markTestSkipped('Cannot test headers in CLI environment');
        }

        // Mock the server class
        $builder = $this->getMockBuilder('webservice_restful_server');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // Ensure no origin is set
        unset($_SERVER['HTTP_ORIGIN']);

        // Use reflection to access the protected method
        $method = new \ReflectionMethod('webservice_restful_server', 'send_headers');
        $method->setAccessible(true);

        // Use output buffering to capture headers
        ob_start();
        $method->invoke($stub, 200);
        ob_end_clean();

        // Get all headers that were set
        $headers = headers_list();

        // Check that the correct CORS headers were set
        $this->assertContains('Access-Control-Allow-Origin: *', $headers);
        $this->assertNotContains('Access-Control-Allow-Credentials: true', $headers);
        $this->assertContains('Access-Control-Allow-Methods: POST, GET, OPTIONS', $headers);
        $this->assertContains('Access-Control-Allow-Headers: Content-Type, Authorization', $headers);
        $this->assertContains('Access-Control-Max-Age: 86400', $headers);
    }
}
