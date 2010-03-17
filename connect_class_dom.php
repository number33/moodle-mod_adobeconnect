<?php // $Id$

require_once('connect_class.php');

class connect_class_dom extends connect_class {

    public function __construct($serverurl = '', $serverport = '', $username = '', $password = '', $cookie = '') {
        parent::__construct($serverurl, $serverport, $username, $password, $cookie);
    }

    public function create_request($params = array(), $sentrequest = true) {
        if (empty($params)) {
            return false;
        }


        $dom = new DOMDocument('1.0', 'UTF-8');

        $root = $dom->createElement('params');
        $dom->appendChild($root);


        foreach($params as $key => $data) {

            $child = $dom->createElement('param', $data);
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


}
?>
