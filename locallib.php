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

    /**
     * Extract the HTTP headers out of the request.
     *
     * @param array $headers Optional array of headers, to assist with testing.
     * @return array $headers HTTP headers.
     */
    private function get_headers($headers=null) {
        if (!$headers){
            $headers = $_SERVER;
        }
        $returnheaders=array();
        foreach($headers as $key => $value) {
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
            // Raise an error if auth header not supplied
            $ex = new \moodle_exception('noauthheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
            die; // We are not recovering or going any further.
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
        if (!$getvars){
            $getvars = $_GET;
        }

        if (isset($getvars['file'])){
            $wsfunction = ltrim($getvars['file'], '/');
        } else {
            // Raise an error if auth header not supplied
            $ex = new \moodle_exception('nowsfunction', 'webservice_restful', '');
            $this->send_error($ex, 400);
            die(); // We are not recovering or going any further.
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
            // Raise an error if auth header not supplied
            $ex = new \moodle_exception('noacceptheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
            die; // We are not recovering or going any further.
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
            // Raise an error if auth header not supplied
            $ex = new \moodle_exception('notypeheader', 'webservice_restful', '');
            $this->send_error($ex, 400);
            die; // We are not recovering or going any further.
        }

        return $requestformat;
    }

    /**
     * Get the parameters to pass to the webservice function
     *
     * @return mixed $input The parameters to use with the webservice.
     */
    private function get_parameters($content='') {
        if (!$content) {
            $content = file_get_contents('php://input');
        }

        if ($this->requestformat == 'json') {
            $parameters = json_decode($content, TRUE); // Convert JSON into array.
        } else if ($this->requestformat == 'xml') {
            $parametersxml = simplexml_load_string($content);
            $parameters = json_decode(json_encode($parametersxml), TRUE); // Dirty XML to JSON to PHP array conversion.
        }  else {  // Data provided in as URL encoded.
            $parameters = $_POST;
        }

        return $parameters;
    }

    /**
     * This method parses the request sent to Moodle
     * and extracts and validates the supplied data.
     *
     * @return void
     */
    protected function parse_request() {

        // Retrieve and clean the POST/GET parameters from the parameters specific to the server.
        parent::set_web_service_call_settings();

        // Get the HTTP Headers.
        $headers = $this->get_headers();

        // Get the webservice token.
        $this->token = $this->get_wstoken($headers);

        // Get response format.
        $this->responseformat = $this->get_responseformat($headers);

        // Get request format.
        $this->requestformat = $this->get_requestformat($headers);

        // Get the webservice function.
        $this->functionname = $this->get_wsfunction();

        // Get the webservice function parameters.
        $this->parameters = $this->get_parameters();

    }

    /**
     * Send the result of function call to the WS client.
     *
     * @return void
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
        echo $this->generate_error($ex);
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
