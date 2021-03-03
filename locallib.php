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
 * RESTful web service implementation classes and methods.
 *
 * @package    webservice_restful
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/webservice/lib.php");

/**
 * REST service server implementation.
 *
 * @package    webservice_restful
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_restful_server extends webservice_base_server {

    /** @var string return method ('xml' or 'json') */
    protected $responseformat;

    /** @var string request method ('xml', 'json', or 'urlencode') */
    protected $requestformat;

    /**
     * Contructor
     *
     * @param string $authmethod authentication method of the web service (WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN, ...)
     */
    public function __construct($authmethod) {
        parent::__construct($authmethod);
        $this->wsname = 'restful';
        $this->responseformat = 'json'; // Default to json.
        $this->requestformat = 'json'; // Default to json.
    }

    /**
     * Get headers from Apache websever.
     *
     * @return array $returnheaders The headers from Apache.
     */
    private function get_apache_headers() {
        $capitalizearray = array(
            'Content-Type',
            'Accept',
            'Authorization',
            'Content-Length',
            'User-Agent',
            'Host'
        );
        $headers = apache_request_headers();
        $returnheaders = array();

        foreach ($headers as $key => $value) {
            if (in_array($key, $capitalizearray)) {
                $header = 'HTTP_' . strtoupper($key);
                $header = str_replace('-', '_', $header);
                $returnheaders[$header] = $value;
            }
        }

        return $returnheaders;
    }

    /**
     * Extract the HTTP headers out of the request.
     *
     * @param array $headers Optional array of headers, to assist with testing.
     * @return array $headers HTTP headers.
     */
    private function get_headers($headers=null) {
        $returnheaders = array();

        if (!$headers) {
            if (function_exists('apache_request_headers')) {  // Apache websever.
                $headers = $this->get_apache_headers();
            } else {  // Nginx webserver.
                $headers = $_SERVER;
            }
        }

        foreach ($headers as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $returnheaders[$key] = $value;
            }
        }

        return $returnheaders;
    }

    /**
     * Get the webservice authorization token from the request.
     * Throws error and notifies caller on failure.
     *
     * @param array $headers The extracted HTTP headers.
     * @return string $wstoken The extracted webservice authorization token.
     */
    private function get_wstoken($headers) {
        $wstoken = '';

        if (isset($headers['HTTP_AUTHORIZATION'])) {
            $wstoken = $headers['HTTP_AUTHORIZATION'];
        } else {
            // Raise an error if auth header not supplied.
            $ex = new \moodle_exception('noauthheader', 'webservice_restful', '');
            $this->send_error($ex, 401);
        }

        return $wstoken;
    }

    /**
     * Extract the web service funtion to use from the request URL.
     * Throws error and notifies caller on failure.
     *
     * @param array $getvars Optional get variables, used for testing.
     * @return string $wsfunction The webservice function to call.
     */
    private function get_wsfunction($getvars=null) {
        $wsfunction = '';

        // Testing has found that there is varying methods across webservers,
        // so we try a few ways.

        if ($getvars) { // Check to see if we are passing hte function explictly.
            $wsfunction = ltrim($getvars['file'], '/');
        } else if (isset($_GET['file'])) { // Try get variables.
            $wsfunction = ltrim($_GET['file'], '/');
        } else if (isset($_SERVER['PATH_INFO'])) { // Try path info from server super global.
            $wsfunction = ltrim($_SERVER['PATH_INFO'], '/');
        } else if (isset($_SERVER['REQUEST_URI'])) { // Try request URI from server super global.
            $wsfunction = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);
        }

        if ($wsfunction == '') {
            // Raise an error if function not supplied.
            $ex = new \moodle_exception('nowsfunction', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $wsfunction;
    }

    /**
     * Get the format to use for the client response.
     * Throws error and notifies caller on failure.
     *
     * @param array $headers The HTTP headers.
     * @return string $responseformat The format of the client response.
     */
    private function get_responseformat($headers) {
        $responseformat = '';

        if (isset($headers['HTTP_ACCEPT'])) {
            $responseformat = ltrim($headers['HTTP_ACCEPT'], 'application/');
        } else {
            // Raise an error if accept header not supplied.
            $ex = new \moodle_exception('noacceptheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $responseformat;
    }

    /**
     * Get the format of the client request.
     * Throws error and notifies caller on failure.
     *
     * @param array $headers The HTTP headers.
     * @return string $requestformat The format of the client request.
     */
    private function get_requestformat($headers) {
        $requestformat = '';

        if (isset($headers['HTTP_CONTENT_TYPE'])) {
            $requestformat = ltrim($headers['HTTP_CONTENT_TYPE'], 'application/');
        } else {
            // Raise an error if content header not supplied.
            $ex = new \moodle_exception('notypeheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
        }

        return $requestformat;
    }

    /**
     * Get the parameters to pass to the webservice function
     *
     * @param array $content the content to parse.
     * @return mixed $input The parameters to use with the webservice.
     */
    private function get_parameters($content='') {
        if (!$content) {
            $content = file_get_contents('php://input');
        }

        if ($this->requestformat == 'json') {
            $parameters = json_decode($content, true); // Convert JSON into array.
        } else if ($this->requestformat == 'xml') {
            $parametersxml = simplexml_load_string($content);
            $parameters = json_decode(json_encode($parametersxml), true); // Dirty XML to JSON to PHP array conversion.
        } else {  // Data provided in as URL encoded.
            $parameters = $_POST;
        }

        return $parameters;
    }

    /**
     * This method parses the request sent to Moodle
     * and extracts and validates the supplied data.
     *
     * @return bool
     */
    protected function parse_request() {

        // Retrieve and clean the POST/GET parameters from the parameters specific to the server.
        parent::set_web_service_call_settings();

        // Get the HTTP Headers.
        $headers = $this->get_headers();

        // Get the webservice token or return false.
        if (!($this->token = $this->get_wstoken($headers))) {
            return false;
        }

        // Get response format or return false.
        if (!($this->responseformat = $this->get_responseformat($headers))) {
            return false;
        }

        // Get request format or return false.
        if (!($this->requestformat = $this->get_requestformat($headers))) {
            return false;
        }

        // Get the webservice function or return false.
        if (!($this->functionname = $this->get_wsfunction())) {
            return false;
        }

        // Get the webservice function parameters or return false.
        if (empty($this->get_parameters())) {
            $this->parameters = array();
        } else if (!($this->parameters = $this->get_parameters())) {
            return false;
        }

        return true;
    }

    /**
     * Process request from client.
     *
     * @uses die
     */
    public function run() {
        global $CFG, $SESSION;

        // We will probably need a lot of memory in some functions.
        raise_memory_limit(MEMORY_EXTRA);

        // Set some longer timeout, this script is not sending any output,
        // this means we need to manually extend the timeout operations
        // that need longer time to finish.
        external_api::set_timeout();

        // Set up exception handler first, we want to sent them back in correct format that
        // the other system understands.
        // We do not need to call the original default handler because this ws handler does everything.
        set_exception_handler(array($this, 'exception_handler'));

        // Init all properties from the request data.
        if (!$this->parse_request()) {
            die;
        };

        // Authenticate user, this has to be done after the request parsing
        // this also sets up $USER and $SESSION.
        $this->authenticate_user();

        // Find all needed function info and make sure user may actually execute the function.
        $this->load_function_info();

        // Log the web service request.
        $params = array(
            'other' => array(
                'function' => $this->functionname
            )
        );
        $event = \core\event\webservice_function_called::create($params);
        $event->set_legacy_logdata(array(SITEID, 'webservice', $this->functionname, '' , getremoteaddr() , 0, $this->userid));
        $event->trigger();

        // Do additional setup stuff.
        $settings = external_settings::get_instance();
        if (method_exists($settings , 'get_lang')) {

            $sessionlang = $settings->get_lang();
            if (!empty($sessionlang)) {
                $SESSION->lang = $sessionlang;
            }

            setup_lang_from_browser();

            if (empty($CFG->lang)) {
                if (empty($SESSION->lang)) {
                    $CFG->lang = 'en';
                } else {
                    $CFG->lang = $SESSION->lang;
                }
            }
        }
        // Finally, execute the function - any errors are catched by the default exception handler.
        $this->execute();

        // Send the results back in correct format.
        $this->send_response();

        // Session cleanup.
        $this->session_cleanup();

        die;
    }

    /**
     * Send the result of function call to the WS client.
     *
     * @return void
     */
    protected function send_response() {

        // Check that the returned values are valid.
        try {
            if ($this->function->returns_desc != null) {
                $validatedvalues = external_api::clean_returnvalue($this->function->returns_desc, $this->returns);
            } else {
                $validatedvalues = null;
            }
        } catch (Exception $ex) {
            $exception = $ex;
        }

        if (!empty($exception)) {
            $response = $this->generate_error($exception);
        } else {
            // We can now convert the response to the requested REST format.
            if ($this->responseformat == 'json') {
                $response = json_encode($validatedvalues);
            } else {
                $response = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
                $response .= '<RESPONSE>'."\n";
                $response .= self::xmlize_result($validatedvalues, $this->function->returns_desc);
                $response .= '</RESPONSE>'."\n";
            }
        }

        $this->send_headers();
        echo $response;
    }

    /**
     * Send the error information to the WS client
     * formatted as XML document.
     * Note: the exception is never passed as null,
     *       it only matches the abstract function declaration.
     *
     * @param exception $ex the exception that we are sending.
     * @param integer $code The HTTP response code to return.
     */
    protected function send_error($ex=null, $code=400) {
        // Sniffing for unit tests running alwasys feels like a hack.
        // We need to do this otherwise it will conflict with the headers
        // sent by PHPUNIT.
        if (!PHPUNIT_TEST) {
            http_response_code($code);
            $this->send_headers($code);
        }
        echo $this->generate_error($ex);
    }

    /**
     * Build the error information matching the REST returned value format (JSON or XML)
     * @param exception $ex the exception we are converting in the server rest format
     * @return string the error in the requested REST format
     */
    protected function generate_error($ex) {
        if ($this->responseformat != 'xml') {
            $errorobject = new stdClass;
            $errorobject->exception = get_class($ex);
            $errorobject->errorcode = $ex->errorcode;
            $errorobject->message = $ex->getMessage();
            if (debugging() and isset($ex->debuginfo)) {
                $errorobject->debuginfo = $ex->debuginfo;
            }
            $error = json_encode($errorobject);
        } else {
            $error = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
            $error .= '<EXCEPTION class="'.get_class($ex).'">'."\n";
            $error .= '<ERRORCODE>' . htmlspecialchars($ex->errorcode, ENT_COMPAT, 'UTF-8')
                    . '</ERRORCODE>' . "\n";
            $error .= '<MESSAGE>'.htmlspecialchars($ex->getMessage(), ENT_COMPAT, 'UTF-8').'</MESSAGE>'."\n";
            if (debugging() and isset($ex->debuginfo)) {
                $error .= '<DEBUGINFO>'.htmlspecialchars($ex->debuginfo, ENT_COMPAT, 'UTF-8').'</DEBUGINFO>'."\n";
            }
            $error .= '</EXCEPTION>'."\n";
        }
        return $error;
    }

    /**
     * Internal implementation - sending of page headers.
     *
     * @param integer $code The HTTP response code to return.
     */
    protected function send_headers($code=200) {
        if ($this->responseformat == 'json') {
            header('Content-type: application/json');
        } else {
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: inline; filename="response.xml"');
        }
        header('X-PHP-Response-Code: '.$code, true, $code);
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('Pragma: no-cache');
        header('Accept-Ranges: none');
        // Allow cross-origin requests only for Web Services.
        // This allow to receive requests done by Web Workers or webapps in different domains.
        header('Access-Control-Allow-Origin: *');
    }

    /**
     * Internal implementation - recursive function producing XML markup.
     *
     * @param mixed $returns the returned values
     * @param external_description $desc
     * @return string
     */
    protected static function xmlize_result($returns, $desc) {
        if ($desc === null) {
            return '';

        } else if ($desc instanceof external_value) {
            if (is_bool($returns)) {
                // We want 1/0 instead of true/false here.
                $returns = (int)$returns;
            }
            if (is_null($returns)) {
                return '<VALUE null="null"/>'."\n";
            } else {
                return '<VALUE>'.htmlspecialchars($returns, ENT_COMPAT, 'UTF-8').'</VALUE>'."\n";
            }

        } else if ($desc instanceof external_multiple_structure) {
            $mult = '<MULTIPLE>'."\n";
            if (!empty($returns)) {
                foreach ($returns as $val) {
                    $mult .= self::xmlize_result($val, $desc->content);
                }
            }
            $mult .= '</MULTIPLE>'."\n";
            return $mult;

        } else if ($desc instanceof external_single_structure) {
            $single = '<SINGLE>'."\n";
            foreach ($desc->keys as $key => $subdesc) {
                $value = isset($returns[$key]) ? $returns[$key] : null;
                $single .= '<KEY name="'.$key.'">'.self::xmlize_result($value, $subdesc).'</KEY>'."\n";
            }
            $single .= '</SINGLE>'."\n";
            return $single;
        }
    }
}
