<?php

/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


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

            $datahtmlent = htmlentities($data);
            $child = $dom->createElement('param', $datahtmlent);
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

        if (isset($domnodelist->item(0)->nodeValue)) {
            $this->_cookie = $domnodelist->item(0)->nodeValue;
        } else {
            $this->_cookie = null;
        }

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

        if (!is_object($domnodelist->item(0))) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

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

        $header = array();
        $this->create_http_head_login_xml();

        // The first parameter is 1 because we want to include the response header
        // to extract the session cookie
        if (!empty($username)) {
            $header = array("$CFG->adobeconnect_admin_httpauth: " . $username);
        }

        $this->_xmlresponse = $this->send_request($return_header, $header, $stop);

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