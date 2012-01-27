<?php // $Id: connect_class_dom.php,v 1.1.2.5 2011/05/03 22:42:07 adelamarre Exp $

require_once('connect_class.php');

class connect_class_dom extends connect_class {

    public function __construct($serverurl = '', $serverport = '',
                                $username = '', $password = '',
                                $cookie = '', $https) {
        parent::__construct($serverurl, $serverport, $username, $password, $cookie, $https);
    }

    public function create_request($params = array(), $sentrequest = true) {
        if (empty($params)) {
            return false;
        }


        $dom = new DOMDocument('1.0', 'UTF-8');

        $root = $dom->createElement('params');
        $dom->appendChild($root);


        foreach($params as $key => $data) {
            
            //htmlentities() breaks support for foreign characters so htmlspecialchars() used
            //stripslashes() used because meetings with quotes were being backslashed on the AC server
            $htmlentry = stripslashes(htmlspecialchars($data));
            $child = $dom->createElement('param', $htmlentry);
            $root->appendChild($child);

            $attribute = $dom->createAttribute('name');
            $child->appendChild($attribute);

            $text = $dom->createTextNode($key);
            $attribute->appendChild($text);

        }

        $this->_xmlrequest = $dom->saveXML();

        if ($sentrequest) {
            $this->_xmlresponse = $this->send_request();
        }
    }

    /**
     * Parses through xml and looks for the 'cookie' parameter
     * @param string $xml the xml to parse through
     * @return string $sessoin returns the session id
     */
    public function read_cookie_xml($xml = '') {
        global $USER, $COURSE, $CFG;

        if (empty($xml)) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

        $dom = new DomDocument();
        $dom->loadXML($xml);
        $domnodelist = $dom->getElementsByTagName('cookie');

        $this->_cookie = $domnodelist->item(0)->nodeValue;

    }

    public function call_success() {
        global $USER, $COURSE, $CFG;

        if (empty($this->_xmlresponse)) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

        $dom = new DomDocument();
        $dom->loadXML($this->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('status');

        if ($domnodelist->item(0)->hasAttributes()) {

            $domnode = $domnodelist->item(0)->attributes->getNamedItem('code');

            if (!is_null($domnode)) {
                if (0 == strcmp('ok', $domnode->nodeValue)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * Sends the HTTP header login request and returns the response xml
     * @param string username username to use for header x-user-id
     */
    public function request_http_header_login($return_header = 0, $username = '', $stop = false) {
        global $CFG;

        $hearder = array();
        $this->create_http_head_login_xml();

        // The first parameter is 1 because we want to include the response header
        // to extract the session cookie
        if (!empty($username)) {
            $hearder = array("$CFG->adobeconnect_admin_httpauth: " . $username);
        }

        $this->_xmlresponse = $this->send_request($return_header, $hearder, $stop);

        $this->set_session_cookie($this->_xmlresponse);

        return $this->_xmlresponse;
    }

   private function create_http_head_login_xml() {
        $params = array('action' => 'login',
                        'external-auth' => 'use',
                        );

        $this->create_request($params, false);
    }
}
?>
