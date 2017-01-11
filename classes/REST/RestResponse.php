<?php
// ================================================================================
// @author Steve Gallo, Ryan Gentner
// @date 2011-January-24
// @version 1.5
//
//
// REST response message.  This class is responsible for constructing and
// formating a response to the REST API.  Valid response formats are indicated
// by the definition of a method with the response format (lowercase) followed
// by "Format" (e.g., jsonFormat, xmlFormat, etc.).  Each format method should
// have a corresponding method for returning (but not actually sending to the
// browser) a list of headers which should include the MIME header string (e.g.,
// jsonHeader).  The handlers can provide an optional list of headers, which is
// especially useful for the raw handler and file downloads.  See
// http://www.iana.org/assignments/media-types/application/
// ================================================================================

class RestResponse
{

   // Default format
    private $_output_format = null;  // Default format set to JSON (coded in RestParser)
    private $_httpCode = null;
    private $_headers = array();
    private $_results = array();

   // --------------------------------------------------------------------------------
   // Factory pattern.
   // --------------------------------------------------------------------------------

    public static function factory($response)
    {
         
        return new RestResponse($response);
    }// factory

   // --------------------------------------------------------------------------------
   // @see factory()
   // --------------------------------------------------------------------------------

    private function __construct($response)
    {
 
        if (isset($response['headers']) && is_array($response['headers'])) {
            // If the action has specified headers, the action itself will dictate how the response
            // is to be rendered.
         
            $this->_output_format = 'raw';
            $this->_headers = $response['headers'];
         
            // The headers are used for internal purposes, so they need not be presented along
            // with the rest of the response.
      
            unset($response['headers']);
        }

        if (isset($response['httpCode'])) {
            $this->_httpCode = $response['httpCode'];
            unset($response['httpCode']);
        }
      
        // Ensure that the response contains a 'success' element
      
        if (!isset($response['success'])) {
            $response['success'] = true;
        }
      
        $this->_results = $response;
    }//__construct
   
  // --------------------------------------------------------------------------------
  // @returns  A description of the "json" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

    public function jsonHelp()
    {
        return "Display the entire response object encoded as a JSON object.  The " .
           "response will be returned as an object where the results is an array arrays.";
    }  // jsonHelp()
  
  // --------------------------------------------------------------------------------
  // Format the response for JSON
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Array of result objects
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

    public function jsonFormat()
    {
         return \xd_charting\encodeJSON($this->_results);
    }//jsonFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the content-type header for JSON
  // --------------------------------------------------------------------------------

    public function jsonHeader()
    {
        //return array("content-type" => "application/json");
        return array("content-type" => "text/plain");
    }  // jsonHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "jsonstore" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

    public function jsonstoreHelp()
    {
        return "Display the entire response object encoded as a JSON object formatted for " .
        "use by the ExtJS JsonStore.  This differs from the 'json' format in that the " .
        "results are returned as an array of objects rather than an array of arrays.";
    }  // jsonstoreHelp()

  // --------------------------------------------------------------------------------
  // Format the response for an ExtJS JsonStore.  The ExtJS JsonStore expects
  // the results to be an array of objects.
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Array of result objects
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

    public function jsonstoreFormat()
    {
  
        $responseResults = array();
    
        if (isset($this->_results['results'])) {
            $responseResults = (is_array($this->_results['results'])) ? $this->_results['results'] : array($this->_results['results']);
        }
 
        // The ExtJS JsonStore expects the results to be an array of objects.
        // This array of objects must reside in $this->_results['results']
        
        $results = array();
    
        foreach ($responseResults as $id => $result) {
            $results[] = (object) $result;
        }

        $retval = array(
                    'success' => $this->_results['success'],
                    'num'     => count($results),
                    'results' => $results);
    
        return \xd_charting\encodeJSON($retval);
    }  // jsonstoreFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the content-type header for JSON
  // --------------------------------------------------------------------------------

    public function jsonstoreHeader()
    {
        //return array("content-type" => "application/json");
        return array("content-type" => "text/plain");
    }  // jsonstoreHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "text" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

    public function textHelp()
    {
        return "Display the entire response as text surrounded by &lt;pre&gt;&lt;/pre&gt; tags.  " .
        "Useful for debugging via a browser.";
    }  // textHelp()

  // --------------------------------------------------------------------------------
  // Format the response for text
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Results list
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

    public function textFormat()
    {
  
        return "<pre>" . print_r($this->_results, 1) . "</pre>";
    }// textFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the content-type header for text
  // --------------------------------------------------------------------------------

    public function textHeader()
    {
        //return array("content-type" => "text/plain");
        return array();
    }  // textHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "raw" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

    public function rawHelp()
    {
  
        return "Display the first item in the result array along with any headers that " .
           "have been set by the API handler.  This is useful when an handler needs to " .
           "return unformatted data such as a file download.";
    }  // rawHelp()

  // --------------------------------------------------------------------------------
  // Format a raw response, typically for download or binary data.  This
  // response type must be supported by the handler (i.e., it must set the
  // correct content type and other headers)
  //
  // @returns The raw result.
  // --------------------------------------------------------------------------------

    public function rawFormat()
    {
        return $this->_results['results'];
    }  // rawFormat()

  // --------------------------------------------------------------------------------
  // @returns The list of headers for a raw response type.
  // --------------------------------------------------------------------------------

    public function rawHeader()
    {
        return $this->_headers;
    }// rawHeader()
  
  // --------------------------------------------------------------------------------
  // @returns This object instance (with the output format set accordingly)
  // --------------------------------------------------------------------------------
  
    public function __call($format, $arguments)
    {
         
        // If any action has provided headers, then the output format should not be explicitly
        // set.
            
        if ($this->_output_format == 'raw') {
            return $this;
        }
            
        if (!method_exists($this, $format.'Format')) {
            $msg = "The format you are requesting ($format) is not supported";
            throw new \Exception($msg);
        }
      
        $this->_output_format = $format;
      
        return $this;
    }//__call
   
  // --------------------------------------------------------------------------------
  // @returns The response in the requested format to the user (caller)
  // --------------------------------------------------------------------------------
     
    public function render()
    {
   
        if (!isset($this->_output_format)) {
            $msg = "An output format must be specified prior to rendering the response.";
            throw new \Exception($msg);
        }
        $headers = $this->{$this->_output_format.'Header'}();
                
        foreach ($headers as $k => $v) {
            header("$k: $v");
        }

        if ($this->_httpCode !== null) {
            header("{$_SERVER['SERVER_PROTOCOL']} {$this->_httpCode} ".HttpCodeMessages::$messages[$this->_httpCode]);
        }
        // else do not override the HTTP code
            
        return $this->{$this->_output_format.'Format'}();
    }//render
}  // class RestResponse
