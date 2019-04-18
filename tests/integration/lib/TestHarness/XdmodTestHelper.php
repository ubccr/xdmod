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
        $this->config = json_decode(file_get_contents(__DIR__ . '/../../../ci/testing.json'), true);

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

        if (isset($this->cookie)) {
            curl_setopt($this->curl, CURLOPT_COOKIE, $this->cookie);
        }

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
            throw new \Exception("User role $userrole not defined in testing.json file");
        }
        $this->userrole = $userrole;
        $this->setauthvariables(null);
        $authresult = $this->post("rest/auth/login", null, $this->config['role'][$userrole]);
        $authtokens = $authresult[0]['results'];
        $this->setauthvariables($authtokens['token']);
    }

    public function authenticateDirect($username, $password)
    {
        $data = array(
            'username' => $username,
            'password' => $password
        );
        $this->setauthvariables(null);
        $authresult = $this->post("rest/auth/login", null, $data);
        $authtokens = $authresult[0]['results'];
        $this->setauthvariables($authtokens['token']);
    }

    /**
     * Retrieves the name and value of all input elements for an html form.
     * @throws \Exception if there is more than one form with a action attribute in the document
     */
    private function getHTMLFormData($html)
    {
        $dom = new \DOMDocument;

        $libxmlErrorSetting = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        // restore original error handling settings
        libxml_use_internal_errors($libxmlErrorSetting);

        $xpath = new \DOMXpath($dom);

        $form = $xpath->query("//form[@action]");
        if ($form->length != 1) {
            throw \Exception('Unexpected number of form elements in html');
        }
        $action = $form->item(0)->getAttribute('action');

        $elements = $xpath->query("//form[@action]//input");
        $formInputs = array();
        foreach ($elements as $element) {
            $formInputs[$element->getAttribute('name')] = $element->getAttribute('value');
        }

        return array($action, $formInputs);
    }

    /*
     * Authenticate a user via SSO. SSO authentication requires a saml-idp
     * server. This is setup in the integration_tests/scripts/samlSetup.sh
     */
    public function authenticateSSO($parameters, $includeDefault = true)
    {
        $result = $this->get('rest/auth/idpredirect', array('returnTo' => '/gui/general/login.php'));
        $nextlocation = $result[0];
        $result = $this->get($nextlocation, null, true);

        list($action, $authSettings) = $this->getHTMLFormData($result[0]);
        if ($includeDefault) {
            $finalSettings = array_merge($authSettings, $parameters);
        } else {
            $finalSettings = $parameters;
        }


        $providerInfo = parse_url($result[1]['url']);
        $url = $providerInfo['scheme'] . '://' . $providerInfo['host'] . ':' . $providerInfo['port'] . $action;

        $result = $this->post($url, null, $finalSettings, true);

        list($action, $credentials) = $this->getHTMLFormData($result[0]);

        $result = $this->post($action, null, $credentials, true);

        if ($result[1]['http_code'] !== 200) {
            throw new \Exception('SSO signin failure: HTTP code' . $result[1]['http_code']);
        }
        if (strpos($result[0], 'Logging you into XDMoD') === false) {
            throw new \Exception('SSO signin failure: ' . $result[0]);
        }
    }

    /**
     * Attempt to authenticate using the provided $userrole against XDMoD's
     * internal dashboard.
     *
     * @param string $userrole the role you wish to authenticate as with the
     *                         internal dashboard.
     * @throws \Exception if the specified $userrole is not present in testing.json
     */
    public function authenticateDashboard($userrole)
    {
        if (! isset($this->config['role'][$userrole])) {
            throw new \Exception("User role $userrole not defined in testing.json file");
        }
        $this->userrole = $userrole;
        $this->setauthvariables(null);
        $data = array(
            'xdmod_username' => $this->config['role'][$userrole]['username'],
            'xdmod_password' => $this->config['role'][$userrole]['password']
        );
        $authresult = $this->post("internal_dashboard/user_check.php", null, $data);
        $cookie = isset($authresult[2]['Set-Cookie']) ? $authresult[2]['Set-Cookie'] : null;
        $this->setauthvariables('', $cookie);
    }

    public function logout()
    {
        $logoutResult = $this->post("rest/auth/logout", null, null);
        $this->setauthvariables(null);
    }

    /**
     * Attempt to execute the internal dashboard's logout action for the current
     * session.
     */
    public function logoutDashboard()
    {
        $this->post(
            'internal_dashboard/controllers/controller.php',
            null,
            array(
                'operation' => 'logout'
            )
        );
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

    public function get($path, $params = null, $isurl = false)
    {
        if ($isurl) {
            $url = $path;
        } else {
            $url = $this->siteurl . $path;
        }

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

    public function post($path, $params, $data, $isurl = false)
    {
        if ($isurl) {
            $url = $path;
        } else {
            $url = $this->siteurl . $path;
        }

        if ($params !== null) {
            $url .= "?" . http_build_query($params);
        }
        if (isset($this->verbose)) {
            echo "$url\n";
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        $postData = '';
        if (isset($data)) {
            $postData = http_build_query($data);
        }
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getheaders());

        return $this->docurl();
    }

    public function patch($path, $params = null, $data = null)
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
        $patchData = '';
        if (isset($data)) {
            $patchData = http_build_query($data);
        }
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $patchData);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getheaders());
        $response = $this->docurl();

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, null);
        return $response;
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
