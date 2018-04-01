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
     * @param string $responseformat Format of the return values: 'xml' or 'json'
     */
    public function __construct($authmethod) {
        parent::__construct($authmethod);
        $this->wsname = 'restful';
        $this->responseformat = 'json'; // Default to json.
        $this->requestformat = 'json'; // Default to json.
    }

    private function get_headers($headers=null) {
        if (!$headers){
            $headers=array();
            foreach($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    $headers[$key] = $value;
                }
            }
        }
        return $headers;
    }

    private function get_wstoken($headers=array()) {
        $wstoken = '';

        if (isset($headers['Authorization'])) {
            $wstoken = $headers['Authorization'];
        } else {
            // Raise an error if auth header not supplied
            $ex = new \moodle_exception('noauthheader', 'webservice_restful', '');
            $this->send_error($ex, 400);

        }

        return $wstoken;
    }

    private function get_wsfunction($getvars=null) {

        // Get GET and POST parameters.
        $methodvariables = array_merge($_GET, $_POST);
        error_log(print_r($_GET), true);

        if (!$getvars) {
            $getvars = array();
            if (isset($_GET['file'])){
                $getvars['wsfunction'] = $_GET['file'];
            } else {
                // Raise an error if auth header not supplied
                $ex = new \moodle_exception('nowsfunction', 'webservice_restful', '');
                $this->send_error($ex, 400);
            }
        }

        return $wsfunction = $getvars['wsfunction'];
    }

    private function get_responseformat($headers=array()) {

    }

    private function get_requestformat($headers=array()) {

    }

    /**
     * This method parses the $_POST and $_GET superglobals and looks for
     * the following information:
     *  1/ user authentication - username+password or token (wsusername, wspassword and wstoken parameters)
     *  2/ function name (wsfunction parameter)
     *  3/ function parameters (all other parameters except those above)
     *  4/ text format parameters
     *  5/ return rest format xml/json
     */
    protected function parse_request() {

        // Retrieve and clean the POST/GET parameters from the parameters specific to the server.
        parent::set_web_service_call_settings();

        // Get the HTTP Headers.
        $headers = $this->get_headers();

        // Get the webservice token.
        $this->token = $this->get_wstoken($headers);

        // Get the webservice function.
        $this->functionname = $this->get_wsfunction();

        // Get response format.
        $this->responseformat = $this->get_responseformat();

        // Get request format.
        $this->requestformat = $this->get_requestformat();

        $this->parameters = $methodvariables;

    }

    /**
     * Send the result of function call to the WS client
     * formatted as XML document.
     */
    protected function send_response() {

        //Check that the returned values are valid
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
            $response =  $this->generate_error($exception);
        } else {
            //We can now convert the response to the requested REST format
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
     * @param exception $ex the exception that we are sending
     */
    protected function send_error($ex=null, $code=200) {
        http_response_code($code);
        $this->send_headers();
        echo $this->generate_error($ex, );
    }

    /**
     * Build the error information matching the REST returned value format (JSON or XML)
     * @param exception $ex the exception we are converting in the server rest format
     * @return string the error in the requested REST format
     */
    protected function generate_error($ex) {
        if ($this->responseformat == 'json') {
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
     */
    protected function send_headers() {
        if ($this->responseformat == 'json') {
            header('Content-type: application/json');
        } else {
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: inline; filename="response.xml"');
        }
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
                // we want 1/0 instead of true/false here
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
            foreach ($desc->keys as $key=>$subdesc) {
                $value = isset($returns[$key]) ? $returns[$key] : null;
                $single .= '<KEY name="'.$key.'">'.self::xmlize_result($value, $subdesc).'</KEY>'."\n";
            }
            $single .= '</SINGLE>'."\n";
            return $single;
        }
    }
}


/**
 * RESTful test client class
 *
 * @package    webservice_restful
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_restful_test_client implements webservice_test_client_interface {
    /**
     * Execute test client WS request
     * @param string $serverurl server url (including token parameter or username/password parameters)
     * @param string $function function name
     * @param array $params parameters of the called function
     * @return mixed
     */
    public function simpletest($serverurl, $function, $params) {
        return download_file_content($serverurl.'&wsfunction='.$function, null, $params);
    }
}
