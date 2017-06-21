<?php

namespace TestHarness;

class XdmodTestHelper
{
    private $config;
    private $siteurl;
    private $headers;
    private $decodeTextAsJson;
    private $cookie;
    private $verbose;
    private $curl;
    private $cookiefile;
    private $userrole = 'public';

    public function __construct($config = array())
    {
        $this->config = json_decode(file_get_contents(__DIR__ . '/../../.secrets'), true);

        $this->siteurl = $this->config['url'];
        $this->headers = array();
        $this->decodeTextAsJson = false;

        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_USERAGENT, "XDMoD REST Test harness");
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);

        # Enable header information in the response data
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array(&$this, 'processResponseHeader'));

        # Disable ssl certificate checks (needed when using self-signed certificates).
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);

        $this->cookiefile = tempnam(sys_get_temp_dir(), "xdmodtestcookies.");
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiefile);

        if (isset($config['decodetextasjson'])) {
            $this->decodeTextAsJson = true;
        }
        if (isset($config['verbose'])) {
            $this->verbose = true;
        }
    }

    private function processResponseHeader($curl, $headerline)
    {
        $tokens = explode(':', $headerline);
        if (count($tokens) == 2) {
            $this->responseHeaders[$tokens[0]] = $tokens[1];
        }

        return strlen($headerline);
    }

    private function setauthvariables($token, $cookie = null)
    {
        if ($token === null) {
            unset($this->headers['Token']);
            unset($this->headers['Authorization']);
            $this->cookie = null;
        } else {
            $this->headers['Token'] = $token;
            $this->headers['Authorization'] = $token;
            $this->cookie = $cookie;
        }
    }

    private function getheaders()
    {
        $headers = array();
        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        return $headers;
    }

    /*
     * Set the named http header to a value. Once the value is set it will
     * be sent will all http requests.  If the header is set already then the
     * value will be overwritten.
     *
     * @param name the name of the header
     * @param value the value to set. If null then the header is unset.
     */
    public function addheader($name, $value)
    {
        if ($value !== null) {
            $this->headers[$name] = $value;
        } else {
            if (isset($this->headers[$name])) {
                unset($this->headers[$name]);
            }
        }
    }

    /**
     * @param name the name of the http header
     * @return the value of the named header or null if the header is not set.
     */
    public function getheader($name)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    public function authenticate($userrole)
    {
        if (! isset($this->config['role'][$userrole])) {
            throw new \Exception("User role $userrole not defined in .secrets file");
        }
        $this->userrole = $userrole;
        $this->setauthvariables(null);
        $authresult = $this->post("rest/auth/login", null, $this->config['role'][$userrole]);
        $authtokens = $authresult[0]['results'];
        $this->setauthvariables($authtokens['token']);
    }

    public function logout()
    {
        $logoutResult = $this->post("rest/auth/logout", null, null);
        $this->setauthvariables(null);
    }

    private function docurl()
    {
        $this->responseHeaders = array();

        $content = curl_exec($this->curl);
        if ($content === false) {
            throw new \Exception(curl_error($this->curl));
        }

        $curlinfo = curl_getinfo($this->curl);

        switch ($curlinfo['content_type']) {
            case "application/json":
                $content = json_decode($content, true);
                break;
            case "text/plain":
                if ($this->decodeTextAsJson) {
                    $content = json_decode($content, true);
                }
                break;
        }
        return array($content, $curlinfo, $this->responseHeaders);
    }

    public function delete($path, $params = null)
    {
        $url = $this->siteurl . $path;
        if ($params !== null) {
            $url .= "?" . http_build_query($params);
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getheaders());

        return $this->docurl();
    }

    public function get($path, $params = null)
    {
        $url = $this->siteurl . $path;

        if ($params !== null) {
            $url .= "?" . http_build_query($params);
        }
        if (isset($this->verbose)) {
            echo "$url\n";
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getheaders());

        return $this->docurl();
    }

    public function post($path, $params, $data)
    {
        $url = $this->siteurl . $path;

        if ($params !== null) {
            $url .= "?" . http_build_query($params);
        }
        if (isset($this->verbose)) {
            echo "$url\n";
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        if (isset($data)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getheaders());

        return $this->docurl();
    }
    public function getSiteurl(){
        return $this->siteurl;
    }
    public function getUserrole(){
        return $this->userrole;
    }
    public function __destruct() {
        curl_close($this->curl);
        unlink($this->cookiefile);
    }
}
