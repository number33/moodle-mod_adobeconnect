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
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('connect_class.php');
require_once('connect_class_dom.php');

define('ADOBE_VIEW_ROLE', 'view');
define('ADOBE_HOST_ROLE', 'host');
define('ADOBE_MINIADMIN_ROLE', 'mini-host');
define('ADOBE_REMOVE_ROLE', 'remove');

define('ADOBE_PARTICIPANT', 1);
define('ADOBE_PRESENTER', 2);
define('ADOBE_REMOVE', 3);
define('ADOBE_HOST', 4);

define('ADOBE_TEMPLATE_POSTFIX', '- Template');
define('ADOBE_MEETING_POSTFIX', '- Meeting');

// Means the Acrobat Connect meeting is public, and anyone who has the URL for the meeting can enter the room.
define('ADOBE_MEETPERM_PUBLIC', 0);
// Means the meeting is protected, and only registered users and accepted guests can enter the room.
define('ADOBE_MEETPERM_PROTECTED', 1);
// Means the meeting is private, and only registered users and participants can enter the room.
define('ADOBE_MEETPERM_PRIVATE', 2);

define('ADOBE_TMZ_LENGTH', 6);

define('ADOBE_VERSION_9', '9.0.0');

function adobe_connection_test($host, $port, $username, $password, $httpheader,
                               $emaillogin, $https, $subfolder) {

    if (empty($host) or
        empty($port) or (0 == $port) or
        empty($username) or
        empty($password) or
        empty($httpheader)) {

        echo "</p>One of the required parameters is blank or incorrect: <br />".
             "Host: $host<br /> Port: $port<br /> Username: $username<br /> Password: $password".
             "<br /> HTTP Header: $httpheader</p>";

        die();
    }

    $messages = array();

    $aconnectdom = new connect_class_dom($host,
                                         $port,
                                         $username,
                                         $password,
                                         '',
                                         $https);

    $params = array(
        'action' => 'common-info'
    );

    // Send common-info call to obtain the session key.
    echo '<p>Sending common-info call:</p>';
    $aconnectdom->create_request($params);

    if (!empty($aconnectdom->_xmlresponse)) {

        // Get the session key from the XML response.
        $aconnectdom->read_cookie_xml($aconnectdom->_xmlresponse);

        $cookie = $aconnectdom->get_cookie();
        if (empty($cookie)) {

            echo '<p>unable to obtain session key from common-info call</p>';
            echo '<p>xmlrequest:</p>';
            $doc = new DOMDocument();

            if ($doc->loadXML($aconnectdom->_xmlrequest)) {
                echo '<p>' . htmlspecialchars($doc->saveXML()) . '</p>';
            } else {
                echo '<p>unable to display the XML request</p>';
            }

            echo '<p>xmlresponse:</p>';
            $doc = new DOMDocument();

            if ($doc->loadXML($aconnectdom->_xmlresponse)) {
                echo '<p>' . htmlspecialchars($doc->saveHTML()) . '</p>';
            } else {
                echo '<p>unable to display the XML response</p>';
            }

        } else {

            // Print success.
            echo '<p style="color:#006633">successfully obtained the session key: ' . $aconnectdom->get_cookie() . '</p>';

            // Test logging in as the administrator.
            $params = array(
                  'action' => 'login',
                  'login' => $aconnectdom->get_username(),
                  'password' => $aconnectdom->get_password(),
            );

            $aconnectdom->create_request($params);

            if ($aconnectdom->call_success()) {
                echo '<p style="color:#006633">successfully logged in as admin user</p>';

                // Test retrevial of folders.
                echo '<p>Testing retrevial of shared content, recording and meeting folders:</p>';
                $folderscoid = aconnect_get_folder($aconnectdom, 'content');

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained shared content folder scoid: '. $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining shared content folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';

                }

                $folderscoid = aconnect_get_folder($aconnectdom, 'forced-archives');

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained forced-archives (meeting recordings) folder scoid: '
                            . $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining forced-archives (meeting recordings) folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';

                }

                $folderscoid = aconnect_get_folder($aconnectdom, 'meetings', $subfolder);

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained meetings folder scoid: '. $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining meetings folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';

                }

                // Test creating a meeting.
                $folderscoid = aconnect_get_folder($aconnectdom, 'meetings', $subfolder);

                $meeting = new stdClass();
                $meeting->name = 'testmeetingtest';
                $time = time();
                $meeting->starttime = $time;
                $time = $time + (60 * 60);
                $meeting->endtime = $time;

                if (($meetingscoid = aconnect_create_meeting($aconnectdom, $meeting, $folderscoid))) {
                    echo '<p style="color:#006633">successfully created meeting <b>testmeetingtest</b> scoid: '
                            . $meetingscoid . '</p>';
                } else {

                    echo '<p>error creating meeting <b>testmeetingtest</b> folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';
                }

                // Test creating a user.
                $user = new stdClass();
                $user->username = 'testusertest';
                $user->firstname = 'testusertest';
                $user->lastname = 'testusertest';
                $user->email = 'testusertest@test.com';

                if (!empty($emaillogin)) {
                    $user->username = $user->email;
                }

                $skipdeletetest = false;

                if (!($usrprincipal = aconnect_user_exists($aconnectdom, $user))) {
                      $usrprincipal = aconnect_create_user($aconnectdom, $user);
                    if ($usrprincipal) {
                        echo '<p style="color:#006633">successfully created user <b>testusertest</b> principal-id: '
                                . $usrprincipal . '</p>';
                    } else {
                        echo '<p>error creating user  <b>testusertest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';

                        aconnect_logout($aconnectdom);
                        die();
                    }
                } else {

                    echo '<p>user <b>testusertest</b> already exists skipping delete user test</p>';
                    $skipdeletetest = true;
                }

                // Test assigning a user a role to the meeting.
                if (aconnect_check_user_perm($aconnectdom, $usrprincipal, $meetingscoid, ADOBE_PRESENTER, true)) {
                    echo '<p style="color:#006633">successfully assigned user <b>testusertest</b>'.
                         ' presenter role in meeting <b>testmeetingtest</b>: '. $usrprincipal . '</p>';
                } else {
                        echo '<p>error assigning user <b>testusertest</b> presenter role in meeting <b>testmeetingtest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';
                }

                // Test removing role from meeting.
                if (aconnect_check_user_perm($aconnectdom, $usrprincipal, $meetingscoid, ADOBE_REMOVE_ROLE, true)) {
                    echo '<p style="color:#006633">successfully removed presenter role for user <b>testusertest</b>'.
                         ' in meeting <b>testmeetingtest</b>: '. $usrprincipal . '</p>';
                } else {
                        echo '<p>error remove presenter role for user <b>testusertest</b> in meeting <b>testmeetingtest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';
                }

                // Test removing user from server.
                if (!$skipdeletetest) {
                    if (aconnect_delete_user($aconnectdom, $usrprincipal)) {
                        echo '<p style="color:#006633">successfully removed user <b>testusertest</b> principal-id: '
                                . $usrprincipal . '</p>';
                    } else {
                        echo '<p>error removing user <b>testusertest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';
                    }
                }

                // Test removing meeting from server.
                if ($meetingscoid) {
                    if (aconnect_remove_meeting($aconnectdom, $meetingscoid)) {
                        echo '<p style="color:#006633">successfully removed meeting <b>testmeetingtest</b> scoid: '
                                . $meetingscoid . '</p>';
                    } else {
                        echo '<p>error removing meeting <b>testmeetingtest</b> folder</p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectdom->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectdom->_xmlresponse). '</p>';
                    }
                }

            } else {
                echo '<p style="color:#680000">logging in as '. $username
                        . ' was not successful, check to see if the username and password are correct </p>';
            }

        }

    } else {
        echo '<p style="color:#680000">common-info API call returned an empty document. '
                . 'Please check your settings and try again </p>';
    }

    aconnect_logout($aconnectdom);

}

/**
 * Returns the folder sco-id
 * @param object an adobe connection_class object
 * @param string $folder name of the folder to get
 * (ex. forced-archives = recording folder | meetings = meetings folder
 * | content = shared content folder)
 * @param string $subfolder name of the subfolder to get
 * @return mixed adobe connect folder sco-id || false if there was an error
 *
 */
function aconnect_get_folder($aconnect, $folder = '', $subfolder = '') {
    $folderscoid = false;
    $params = array('action' => 'sco-shortcuts');

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $folderscoid = aconnect_get_folder_sco_id($aconnect->_xmlresponse, $folder);
    }

    if ($folderscoid and !empty($subfolder)) {
        $params = array(
            'action' => 'sco-expanded-contents',
            'sco-id' => $folderscoid,
            'filter-name' => $subfolder
        );
        $aconnect->create_request($params);
        if ($aconnect->call_success()) {
            $folderscoid = aconnect_get_sub_folder_sco_id($aconnect->_xmlresponse, $subfolder);
        }
    }

    return $folderscoid;
}

/**
 * Parse the xml response to return the folder sco-id
 * @param string $xml Get folder details xml response 
 * @param string $folder Name of the folder to get
 * @return mixed sco-id or false
 *
 */
function aconnect_get_folder_sco_id($xml, $folder) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('sco');

    if (!empty($domnodelist->length)) {

        for ($i = 0; $i < $domnodelist->length; $i++) {

            $domnode = $domnodelist->item($i)->attributes->getNamedItem('type');

            if (!is_null($domnode)) {

                if (0 == strcmp($folder, $domnode->nodeValue)) {
                    $domnode = $domnodelist->item($i)->attributes->getNamedItem('sco-id');

                    if (!is_null($domnode)) {
                        $scoid = (int) $domnode->nodeValue;

                    }
                }
            }
        }
    }

    return $scoid;

}

/**
 * Parse the xml response to return the sub folder sco-id
 * @param string $xml Get subfolder details xml response 
 * @param string $subfolder Name of the subfolder to get
 * @return mixed sco-id or false
 *
 */
function aconnect_get_sub_folder_sco_id($xml, $subfolder) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('sco');

    if (!empty($domnodelist->length)) {
        for ($i = 0; $i < $domnodelist->length; $i++) {
            $domnode = $domnodelist->item($i)->attributes->getNamedItem('type');
            if (!is_null($domnode) && $domnode->nodeValue == 'folder') {
                $domnode = $domnodelist->item($i)->attributes->getNamedItem('sco-id');
                $foldernamenode = $domnodelist->item($i)->firstChild;
                if (!is_null($domnode) && !is_null($foldernamenode) && $foldernamenode->nodeName == 'name'
                        && $foldernamenode->nodeValue == $subfolder) {
                    $scoid = (int) $domnode->nodeValue;

                }
            }
        }
    }

    return $scoid;
}

/**
 * Log in as the admin user.  This should only be used to conduct API calls.
 */
function aconnect_login() {
    global $CFG, $USER, $COURSE;

    if (!isset($CFG->adobeconnect_host) or
        !isset($CFG->adobeconnect_admin_login) or
        !isset($CFG->adobeconnect_admin_password)) {

        if (is_siteadmin($USER->id)) {
            notice(get_string('adminnotsetupproperty', 'adobeconnect'),
                   $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
        } else {
            notice(get_string('notsetupproperty', 'adobeconnect'),
                   '', $COURSE);
        }
    }

    if (isset($CFG->adobeconnect_port) and
        !empty($CFG->adobeconnect_port) and
        ((80 != $CFG->adobeconnect_port) and (0 != $CFG->adobeconnect_port))) {
        $port = $CFG->adobeconnect_port;
    } else {
        $port = 80;
    }

    $https = false;

    if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
        $https = true;
    }

    $aconnect = new connect_class_dom($CFG->adobeconnect_host,
                                      $CFG->adobeconnect_port,
                                      $CFG->adobeconnect_admin_login,
                                      $CFG->adobeconnect_admin_password,
                                      '',
                                      $https);

    $params = array(
        'action' => 'common-info'
    );

    $aconnect->create_request($params);

    $aconnect->read_cookie_xml($aconnect->_xmlresponse);

    $params = array(
          'action' => 'login',
          'login' => $aconnect->get_username(),
          'password' => $aconnect->get_password(),
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $aconnect->set_connection(1);
    } else {
        $aconnect->set_connection(0);
    }

    return $aconnect;
}


/**
 * Logout
 * @param object $aconnect - connection object
 * @return true on success else false
 */
function aconnect_logout(&$aconnect) {
    if (!$aconnect->get_connection()) {
        return true;
    }

    $params = array('action' => 'logout');
    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $aconnect->set_connection(0);
        return true;
    } else {
        $aconnect->set_connection(1);
        return false;
    }
}

/**
 * Calls all operations needed to retrieve and return all
 * templates defined in the shared templates folder and meetings
 * @param object $aconnect connection object
 * @return array $templates an array of templates
 */
function aconnect_get_templates_meetings($aconnect) {
    $templates = array();
    $meetings = array();
    $meetfldscoid = false;
    $tempfldscoid = false;

    $params = array(
        'action' => 'sco-shortcuts',
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        // Get shared templates folder sco-id.
        $tempfldscoid = aconnect_get_shared_templates($aconnect->_xmlresponse);
    }

    if (false !== $tempfldscoid) {
        $params = array(
            'action' => 'sco-expanded-contents',
            'sco-id' => $tempfldscoid,
        );

        $aconnect->create_request($params);

        if ($aconnect->call_success()) {
            $templates = aconnect_return_all_templates($aconnect->_xmlresponse);
        }
    }

    return $templates + $meetings;
}

/**
 * Parse XML looking for shared-meeting-templates attribute
 * and returning the sco-id of the folder
 * @param string $xml returned XML from a sco-shortcuts call
 * @return mixed sco-id if found or false if not found or error
 */
function aconnect_get_shared_templates($xml) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('shortcuts');

    if (!empty($domnodelist->length)) {
        $innerlist = $domnodelist->item(0)->getElementsByTagName('sco');

        if (!empty($innerlist->length)) {
            for ($x = 0; $x < $innerlist->length; $x++) {
                if ($innerlist->item($x)->hasAttributes()) {
                    $domnode = $innerlist->item($x)->attributes->getNamedItem('type');
                    if (!is_null($domnode)) {
                        if (0 == strcmp('shared-meeting-templates', $domnode->nodeValue)) {
                            $domnode = $innerlist->item($x)->attributes->getNamedItem('sco-id');

                            if (!is_null($domnode)) {
                                $scoid = (int) $domnode->nodeValue;
                            }
                        }
                    }
                }
            }
        }
    }

    return $scoid;
}

function aconnect_return_all_meetings($xml) {
    $meetings = array();
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $meetings;
    }

    foreach ($xml->{'expanded-scos'}[0]->sco as $key => $sco) {
        if (0 == strcmp('meeting', $sco['type'])) {
            $mkey = (int) $sco['sco-id'];
            $meetings[$mkey] = (string) current($sco->name) .' '. ADOBE_MEETING_POSTFIX;
        }
    }

    return $meetings;
}

/**
 * Parses XML for meeting templates and returns an array
 * with sco-id as the key and template name as the value
 * @param strimg $xml XML returned from a sco-expanded-contents call
 * @return array of templates sco-id -> key, name -> value
 */
function aconnect_return_all_templates($xml) {
    $templates = array();

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('expanded-scos');

    if (!empty($domnodelist->length)) {

        $innerlist = $domnodelist->item(0)->getElementsByTagName('sco');

        if (!empty($innerlist->length)) {

            for ($i = 0; $i < $innerlist->length; $i++) {

                if ($innerlist->item($i)->hasAttributes()) {
                    $domnode = $innerlist->item($i)->attributes->getNamedItem('type');

                    if (!is_null($domnode) and 0 == strcmp('meeting', $domnode->nodeValue)) {
                        $domnode = $innerlist->item($i)->attributes->getNamedItem('sco-id');

                        if (!is_null($domnode)) {
                            $tkey = (int) $domnode->nodeValue;
                            $namelistnode = $innerlist->item($i)->getElementsByTagName('name');

                            if (!is_null($namelistnode)) {
                                $name = $namelistnode->item(0)->nodeValue;
                                $templates[$tkey] = (string) $name .' ' . ADOBE_TEMPLATE_POSTFIX;
                            }
                        }
                    }
                }
            }
        }
    }

    return $templates;
}

/**
 * Returns information about all recordings that belong to a specific
 * meeting sco-id
 *
 * @param obj $aconnect a connect_class object
 * @param int $folderscoid the recordings folder sco-id
 * @param int $sourcescoid the meeting sco-id
 *
 * @return mixed array an array of object with the recording sco-id
 * as the key and the recording properties as properties
 */
function aconnect_get_recordings($aconnect, $folderscoid, $sourcescoid) {
    $params = array('action' => 'sco-contents',
                    'sco-id' => $folderscoid,
                    'sort-name' => 'asc',
                    );

    // Check if meeting scoid and folder scoid are the same
    // If hey are the same then that means that forced recordings is not
    // enabled filter-source-sco-id should not be included.  If the
    // meeting scoid and folder scoid are not equal then forced recordings
    // are enabled and we can use filter by filter-source-sco-id
    // Thanks to A. gtdino.
    if ($sourcescoid != $folderscoid) {
        $params['filter-source-sco-id'] = $sourcescoid;
    }

    $aconnect->create_request($params);

    $recordings = array();

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('scos');

        if (!empty($domnodelist->length)) {

            $innernodelist = $domnodelist->item(0)->getElementsByTagName('sco');

            if (!empty($innernodelist->length)) {
                $aconnectversion = aconnect_get_version($aconnect);
                for ($x = 0; $x < $innernodelist->length; $x++) {

                    if ($innernodelist->item($x)->hasAttributes()) {

                        $domnode = $innernodelist->item($x)->attributes->getNamedItem('sco-id');

                        if (isset($domnode)) {
                            $meetingdetail = $innernodelist->item($x);

                            // Check if the SCO item is a recording or uploaded document.  We only want to display recordings.
                            $versionbeforeversion9 = version_compare($aconnectversion, ADOBE_VERSION_9, '<');
                            if ($versionbeforeversion9) {
                                $durationnode = $meetingdetail->getElementsByTagName('duration')->item(0);
                            } else {
                                $durationnode = $innernodelist->item($x)->attributes->getNamedItem('duration');
                            }
                            $hasduration = (isset($durationnode) && !empty($durationnode->nodeValue));

                            if ($hasduration) {
                                $recordingid = (int) $domnode->nodeValue;
                                $recordings[$recordingid] = new stdClass();

                                $value = (!is_null($meetingdetail->getElementsByTagName('name'))) ?
                                         $meetingdetail->getElementsByTagName('name')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->name = (string) $value;

                                $value = (!is_null($meetingdetail->getElementsByTagName('url-path'))) ?
                                         $meetingdetail->getElementsByTagName('url-path')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->url = (string) $value;

                                $value = (!is_null($meetingdetail->getElementsByTagName('date-begin'))) ?
                                         $meetingdetail->getElementsByTagName('date-begin')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->startdate = (string) $value;

                                $value = (!is_null($meetingdetail->getElementsByTagName('date-end'))) ?
                                         $meetingdetail->getElementsByTagName('date-end')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->enddate = (string) $value;

                                $value = (!is_null($meetingdetail->getElementsByTagName('date-created'))) ?
                                         $meetingdetail->getElementsByTagName('date-created')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->createdate = (string) $value;

                                $value = (!is_null($meetingdetail->getElementsByTagName('date-modified'))) ?
                                         $meetingdetail->getElementsByTagName('date-modified')->item(0)->nodeValue : '';
                                $recordings[$recordingid]->modified = (string) $value;

                                $recordings[$recordingid]->duration = (string) $durationnode->nodeValue;

                                $recordings[$recordingid]->sourcesco = (int) $sourcescoid;
                            }
                        }
                    }
                }
            }

            return $recordings;
        } else {
            return false;
        }
    } else {
        return false;
    }

}

/**
 * Parses XML and returns the meeting sco-id
 * @param string XML obtained from a sco-update call
 */
function aconnect_get_meeting_scoid($xml) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('sco');

    if (!empty($domnodelist->length)) {
        if ($domnodelist->item(0)->hasAttributes()) {
            $domnode = $domnodelist->item(0)->attributes->getNamedItem('sco-id');

            if (!is_null($domnode)) {
                $scoid = (int) $domnode->nodeValue;
            }
        }
    }

    return $scoid;
}

/**
 * Update meeting
 * @param obj $aconnect connect_class object
 * @param obj $meetingobj an adobeconnect module object
 * @param int $meetingfdl adobe connect meeting folder sco-id
 * @return bool true if call was successful else false
 */
function aconnect_update_meeting($aconnect, $meetingobj, $meetingfdl) {
    $params = array('action' => 'sco-update',
                    'sco-id' => $meetingobj->scoid,
                    'name' => $meetingobj->name,
                    'folder-id' => $meetingfdl,
                    'date-begin' => $meetingobj->starttime,
                    'date-end' => $meetingobj->endtime,
                    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }

}

/**
 * Update a meeting's access permissions
 * @param obj $aconnect connect_class object
 * @param int $meetingscoid meeting sco-id
 * @param int $perm meeting permission id
 * @return bool true if call was successful else false
 */
function aconnect_update_meeting_perm($aconnect, $meetingscoid, $perm) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid,
        'principal-id' => 'public-access',
     );

    switch ($perm) {
        case ADOBE_MEETPERM_PUBLIC:
            $params['permission-id'] = 'view-hidden';
            break;
        case ADOBE_MEETPERM_PROTECTED:
            $params['permission-id'] = 'remove';
            break;
        case ADOBE_MEETPERM_PRIVATE:
        default:
            $params['permission-id'] = 'denied';
            break;
    }

     $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }
}

/** CONTRIB-1976, CONTRIB-1992
 * This function adds a fraction of a second to the ISO 8601 date
 * @param int $time unix timestamp
 * @return mixed a string (ISO 8601) containing the decimal fraction of a second
 * or false if it was not able to determine where to put it
 */
function aconnect_format_date_seconds($time) {

    $newdate = false;
    $date = date("c", $time);

    $pos = strrpos($date, '-');
    $length = strlen($date);

    $diff = $length - $pos;

    if ((0 < $diff) and (ADOBE_TMZ_LENGTH == $diff)) {
        $firstpart = substr($date, 0, $pos);
        $lastpart = substr($date, $pos);
        $newdate = $firstpart . '.000' . $lastpart;

        return $newdate;
    }

    $pos = strrpos($date, '+');
    $length = strlen($date);

    $diff = $length - $pos;

    if ((0 < $diff) and (ADOBE_TMZ_LENGTH == $diff)) {
        $firstpart = substr($date, 0, $pos);
        $lastpart = substr($date, $pos);
        $newdate = $firstpart . '.000' . $lastpart;

        return $newdate;

    }

    return false;
}

/**
 * Creates a meeting
 * @param obj $aconnect connect_class object
 * @param obj $meetingobj an adobeconnect module object
 * @param int $meetingfdl adobe connect meeting folder sco-id
 * @return mixed meeting sco-id on success || false on error
 */
function aconnect_create_meeting($aconnect, $meetingobj, $meetingfdl) {
    $starttime = aconnect_format_date_seconds($meetingobj->starttime);
    $endtime = aconnect_format_date_seconds($meetingobj->endtime);

    if (empty($starttime) or empty($endtime)) {
        $message = 'Failure (aconnect_find_timezone) in finding the +/- sign in the date timezone'.
                    "\n".date("c", $meetingobj->starttime)."\n".date("c", $meetingobj->endtime);
        debugging($message, DEBUG_DEVELOPER);
        return false;
    }

    $params = array(
        'action' => 'sco-update',
        'type' => 'meeting',
        'name' => $meetingobj->name,
        'folder-id' => $meetingfdl,
        'date-begin' => $starttime,
        'date-end' => $endtime
    );

    if (!empty($meetingobj->meeturl)) {
        $params['url-path'] = $meetingobj->meeturl;
    }

    if (!empty($meetingobj->templatescoid)) {
        $params['source-sco-id'] = $meetingobj->templatescoid;
    }

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return aconnect_get_meeting_scoid($aconnect->_xmlresponse);
    } else {
        return false;
    }
}

/**
 * Finds a matching meeting sco-id
 * @param object $aconnect a connect_class object
 * @param int $meetfldscoid Meeting folder sco-id
 * @param array $filter array key is the filter and array value is the value
 * (ex. array('filter-name' => 'meeting101'))
 * @return mixed array of objects with sco-id as key and meeting name and url as object
 * properties as value || false if not found or error occured
 */
function aconnect_meeting_exists($aconnect, $meetfldscoid, $filter = array()) {
    $matches = array();

    $params = array(
        'action' => 'sco-contents',
        'sco-id' => $meetfldscoid,
        'filter-type' => 'meeting',
    );

    if (empty($filter)) {
        return false;
    }

    $params = array_merge($params, $filter);
    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('scos');

        if (!empty($domnodelist->length)) {

            $innernodelist = $domnodelist->item(0)->getElementsByTagName('sco');

            if (!empty($innernodelist->length)) {

                for ($i = 0; $i < $innernodelist->length; $i++) {

                    if ($innernodelist->item($i)->hasAttributes()) {

                        $domnode = $innernodelist->item($i)->attributes->getNamedItem('sco-id');

                        if (!is_null($domnode)) {

                            $key = (int) $domnode->nodeValue;

                            $meetingdetail = $innernodelist->item($i);

                            $value = (!is_null($meetingdetail->getElementsByTagName('name'))) ?
                                     $meetingdetail->getElementsByTagName('name')->item(0)->nodeValue : '';

                            if (!isset($matches[$key])) {
                                $matches[$key] = new stdClass();
                            }

                            $matches[$key]->name = (string) $value;

                            $value = (!is_null($meetingdetail->getElementsByTagName('url-path'))) ?
                                     $meetingdetail->getElementsByTagName('url-path')->item(0)->nodeValue : '';

                            $matches[$key]->url = (string) $value;

                            $matches[$key]->scoid = (int) $key;

                            $value = (!is_null($meetingdetail->getElementsByTagName('date-begin'))) ?
                                     $meetingdetail->getElementsByTagName('date-begin')->item(0)->nodeValue : '';

                            $matches[$key]->starttime = (string) $value;

                            $value = (!is_null($meetingdetail->getElementsByTagName('date-end'))) ?
                                     $meetingdetail->getElementsByTagName('date-end')->item(0)->nodeValue : '';

                            $matches[$key]->endtime = (string) $value;

                        }

                    }
                }
            }
        } else {
            return false;
        }

    } else {
        return false;
    }

    return $matches;
}

/**
 * Parse XML and returns the user's principal-id
 * @param string $xml XML returned from call to principal-list
 * @param mixed user's principal-id or false
 */
function aconnect_get_user_principal_id($xml) {
    $usrprincipalid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('principal-list');

    if (!empty($domnodelist->length)) {
        $domnodelist = $domnodelist->item(0)->getElementsByTagName('principal');

        if (!empty($domnodelist->length)) {
            if ($domnodelist->item(0)->hasAttributes()) {
                $domnode = $domnodelist->item(0)->attributes->getNamedItem('principal-id');

                if (!is_null($domnode) && !empty($domnode->nodeValue)) {
                    $usrprincipalid = (int) $domnode->nodeValue;
                }
            }
        }
    }

    return $usrprincipalid;
}

/**
 * Check to see if a user exists on the Adobe connect server
 * searching by username
 * @param object $aconnect a connection_class object
 * @param object $userdata an object with username as a property
 * @return mixed user's principal-id of match is found || false if not
 * found or error occured
 */
function aconnect_user_exists($aconnect, $usrdata) {
    $params = array(
        'action' => 'principal-list',
        'filter-login' => $usrdata->username,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return aconnect_get_user_principal_id($aconnect->_xmlresponse);
    } else {
        return false;
    }
}

function aconnect_delete_user($aconnect, $principalid = 0) {

    if (empty($principalid)) {
        return false;
    }

    $params = array(
        'action' => 'principals-delete',
        'principal-id' => $principalid,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }

}

/**
 * Creates a new user on the Adobe Connect server.
 * Parses XML from a principal-update call and returns
 * the principal-id of the new user.
 *
 * @param object $aconnet a connect_class object
 * @param object $usrdata an object with firstname,lastname,
 * username and email properties.
 * @return mixed principal-id of the new user or existing user or false
 */
function aconnect_create_user($aconnect, $usrdata) {
    $principalid = false;

    if (!$principalid = aconnect_user_exists($aconnect, $usrdata)) {
        $params = array(
            'action' => 'principal-update',
            'first-name' => $usrdata->firstname,
            'last-name' => $usrdata->lastname,
            'login' => $usrdata->username,
            'password' => strtoupper(md5($usrdata->username . time())),
            'extlogin' => $usrdata->username,
            'type' => 'user',
            'send-email' => 'false',
            'has-children' => 0,
            'email' => $usrdata->email,
        );

        $aconnect->create_request($params);

        if ($aconnect->call_success()) {
            $dom = new DomDocument();
            $dom->loadXML($aconnect->_xmlresponse);

            $domnodelist = $dom->getElementsByTagName('principal');

            if (!empty($domnodelist->length)) {
                if ($domnodelist->item(0)->hasAttributes()) {
                    $domnode = $domnodelist->item(0)->attributes->getNamedItem('principal-id');

                    if (!is_null($domnode) && !empty($domnode->nodeValue)) {
                        $principalid = (int) $domnode->nodeValue;
                    }
                }
            }
        } else {
            debugging("Error creating user");
        }
    }

    return $principalid;
}

/**
 * Assign user role in Adobe Connnect based on the permission in Moodle.
 *
 * @param object $aconnect a connect_class object
 * @param int $userprincipalid Adobe Connect principal-id of the user
 * @param int $userid a moodle user id
 * @param int $contextid the context id of the activity
 * @param int $meetingscoid an Adobe Connect meeting sco-id
 * @param bool $ispublicmeeting is the meeting public or not
 * @return bool $userrole if the user has a role or not
 */
function aconnect_assign_user_from_moodle_role($aconnect, $userprincipalid, $userid, $contextid,
                                               $meetingscoid, $ispublicmeeting) {
    $userrole = false;

    // Check the user's capabilities and assign them the Adobe Role.
    if (has_capability('mod/adobeconnect:meetinghost', $contextid, $userid, false)) {
        $userrole = ADOBE_HOST;
    } else if (has_capability('mod/adobeconnect:meetingpresenter', $contextid, $userid, false)) {
        $userrole = ADOBE_PRESENTER;
    } else if (has_capability('mod/adobeconnect:meetingparticipant', $contextid, $userid, false)
               || $ispublicmeeting) {
        $userrole = ADOBE_PARTICIPANT;
    }

    if ($userrole) {
        if (!aconnect_check_user_perm($aconnect, $userprincipalid, $meetingscoid, $userrole, true)) {
            debugging('error assign user adobe role: ' . $userrole);
        }
    }

    return $userrole;
}

function aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $type) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, // The sco-id of meeting || principal id of user 11209.
        'permission-id' => $type, // Host, mini-host, view.
        'principal-id' => $usrprincipal, // Principal id of user you are looking at.
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
          return true;
    } else {
          return false;
    }
}

function aconnect_remove_user_perm($aconnect, $usrprincipal, $meetingscoid) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, // The sco-id of meeting || principal id of user 11209.
        'permission-id' => ADOBE_REMOVE_ROLE, // Host, mini-host, view.
        'principal-id' => $usrprincipal, // Principal id of user you are looking at.
    );

    $aconnect->create_request($params);
}

/**
 * Check if a user has a permission
 * @param object $aconnect a connect_class object
 * @param int $usrprincipal user principal-id
 * @param int $meetingscoid meeting sco-id
 * @param int $roletype can be ADOBE_PRESENTER, ADOBE_PARTICIPANT or ADOBE_REMOVE
 * @param bool $assign set to true if you want to assign the user the role type
 * set to false to just check the user's permission.  $assign parameter is ignored
 * if $roletype is ADOBE_REMOVE
 * @return TODO
 *
 */
function aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, $roletype, $assign = false) {
    $perm_type = '';
    $hasperm = false;

    switch ($roletype) {
        case ADOBE_PRESENTER:
            $perm_type = ADOBE_MINIADMIN_ROLE;
            break;
        case ADOBE_PARTICIPANT:
            $perm_type = ADOBE_VIEW_ROLE;
            break;
        case ADOBE_HOST:
            $perm_type = ADOBE_HOST_ROLE;
            break;
        case ADOBE_REMOVE:
            $perm_type = ADOBE_REMOVE_ROLE;
            break;
        default:
            break;
    }

    $params = array(
        'action' => 'permissions-info',
        'acl-id' => $meetingscoid, // The sco-id of meeting || principal id of user 11209.
        'filter-principal-id' => $usrprincipal, // Principal id of user you are looking at.
    );

    if (ADOBE_REMOVE_ROLE != $perm_type) {
        $params['filter-permission-id'] = $perm_type;
    }

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('permissions');

        if (!empty($domnodelist->length)) {
            $domnodelist = $domnodelist->item(0)->getElementsByTagName('principal');

            if (!empty($domnodelist->length)) {
                $hasperm = true;
            }
        }

        if (ADOBE_REMOVE_ROLE != $perm_type and $assign and !$hasperm) {
            // TODO: check return values of the two functions below
            // Assign permission to user.
            return aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $perm_type);
        } else if (ADOBE_REMOVE_ROLE == $perm_type) {
            // Remove user's permission.
            return aconnect_remove_user_perm($aconnect, $usrprincipal, $meetingscoid);
        } else {
            return $hasperm;
        }
    }
}

/**
 * Remove a meeting
 * @param obj $aconnect adobe connection object
 * @param int $scoid sco-id of the meeting
 * @return bool true of success false on failure
 */
function aconnect_remove_meeting($aconnect, $scoid) {
    $params = array(
        'action' => 'sco-delete',
        'sco-id' => $scoid,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }
}

/**
 * Move SCOs to the shared content folder
 * @param obj $aconnect a connect_class object
 * @param array sco-ids as array keys
 * @return bool false if error or nothing to move true if a move occured
 */
function aconnect_move_to_shared($aconnect, $scolist) {
    // Get shared folder sco-id.
    $shscoid = aconnect_get_folder($aconnect, 'content');

    // Iterate through list of sco and move them all to the shared folder.
    if (!empty($shscoid)) {

        foreach ($scolist as $scoid => $data) {
            $params = array(
                'action' => 'sco-move',
                'folder-id' => $shscoid,
                'sco-id' => $scoid,
            );

            $aconnect->create_request($params);

        }

        return true;
    } else {
        return false;
    }
}

/**
 * Gets a list of roles that this user can assign in this context
 *
 * @param object $context the context.
 * @param int $rolenamedisplay the type of role name to display. One of the
 *      ROLENAME_X constants. Default ROLENAME_ALIAS.
 * @param bool $withusercounts if true, count the number of users with each role.
 * @param integer|object $user A user id or object. By default (null) checks the permissions of the current user.
 * @return array if $withusercounts is false, then an array $roleid => $rolename.
 *      if $withusercounts is true, returns a list of three arrays,
 *      $rolenames, $rolecounts, and $nameswithcounts.
 */
function adobeconnect_get_assignable_roles($context, $rolenamedisplay = ROLENAME_ALIAS, $withusercounts = false, $user = null) {
    global $USER, $DB;

    // Make sure there is a real user specified.
    if ($user === null) {
        $userid = !empty($USER->id) ? $USER->id : 0;
    } else {
        $userid = !empty($user->id) ? $user->id : $user;
    }

    if (!has_capability('moodle/role:assign', $context, $userid)) {
        if ($withusercounts) {
            return array(array(), array(), array());
        } else {
            return array();
        }
    }

    $parents = get_parent_contexts($context, true);
    $contexts = implode(',' , $parents);

    $params = array();
    $extrafields = '';
    if ($rolenamedisplay == ROLENAME_ORIGINALANDSHORT or $rolenamedisplay == ROLENAME_SHORT) {
        $extrafields .= ', r.shortname';
    }

    if ($withusercounts) {
        $extrafields = ', (SELECT count(u.id)
                             FROM {role_assignments} cra JOIN {user} u ON cra.userid = u.id
                            WHERE cra.roleid = r.id AND cra.contextid = :conid AND u.deleted = 0
                          ) AS usercount';
        $params['conid'] = $context->id;
    }

    if (is_siteadmin($userid)) {
        // Show all roles allowed in this context to admins.
        $assignrestriction = "";
    } else {
        $assignrestriction = "JOIN (SELECT DISTINCT raa.allowassign AS id
                                      FROM {role_allow_assign} raa
                                      JOIN {role_assignments} ra ON ra.roleid = raa.roleid
                                     WHERE ra.userid = :userid AND ra.contextid IN ($contexts)
                                   ) ar ON ar.id = r.id";
        $params['userid'] = $userid;
    }
    $params['contextlevel'] = $context->contextlevel;
    $sql = "SELECT r.id, r.name $extrafields
              FROM {role} r
              $assignrestriction
              JOIN {role_context_levels} rcl ON r.id = rcl.roleid
             WHERE rcl.contextlevel = :contextlevel
          ORDER BY r.sortorder ASC";
    $roles = $DB->get_records_sql($sql, $params);

    // Only include Adobe Connect roles.
    $param = array('shortname' => 'adobeconnectpresenter');
    $presenterid    = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'adobeconnectparticipant');
    $participantid  = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'adobeconnecthost');
    $hostid         = $DB->get_field('role', 'id', $param);

    foreach ($roles as $key => $data) {
        if ($key != $participantid and $key != $presenterid and $key != $hostid) {
            unset($roles[$key]);
        }
    }

    $rolenames = array();
    foreach ($roles as $role) {
        if ($rolenamedisplay == ROLENAME_SHORT) {
            $rolenames[$role->id] = $role->shortname;
            continue;
        }
        $rolenames[$role->id] = $role->name;
        if ($rolenamedisplay == ROLENAME_ORIGINALANDSHORT) {
            $rolenames[$role->id] .= ' (' . $role->shortname . ')';
        }
    }
    if ($rolenamedisplay != ROLENAME_ORIGINALANDSHORT and $rolenamedisplay != ROLENAME_SHORT) {
        $rolenames = role_fix_names($rolenames, $context, $rolenamedisplay);
    }

    if (!$withusercounts) {
        return $rolenames;
    }

    $rolecounts = array();
    $nameswithcounts = array();
    foreach ($roles as $role) {
        $nameswithcounts[$role->id] = $rolenames[$role->id] . ' (' . $roles[$role->id]->usercount . ')';
        $rolecounts[$role->id] = $roles[$role->id]->usercount;
    }
    return array($rolenames, $rolecounts, $nameswithcounts);
}

/**
 * This function accepts a username and an email and returns the user's
 * adobe connect user name, depending on the module's configuration settings
 * 
 * @param string - moodle username
 * @param string - moodle email
 * 
 * @return string - user's adobe connect user name
 */
function set_username($username, $email) {
    global $CFG;

    if (isset($CFG->adobeconnect_email_login) and !empty($CFG->adobeconnect_email_login)) {
        return $email;
    } else {
        return $username;
    }
}

/**
 * This function search through the user-meetings folder for a folder named
 * after the user's login name and returns the sco-id of the user's folder
 * 
 * @param obj - adobe connection connection object
 * @param string - the name of the user's folder
 * @return mixed - sco-id of the user folder (int) or false if no folder exists
 * 
 */
function aconnect_get_user_folder_sco_id($aconnect, $folder_name) {

    $scoid   = false;
    $usr_meet_scoid = aconnect_get_folder($aconnect, 'user-meetings');

    if (empty($usr_meet_scoid)) {
        return $scoid;
    }

    $params = array('action' => 'sco-expanded-contents',
                    'sco-id' => $usr_meet_scoid,
                    'filter-name' => $folder_name);

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {

        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('sco');

        if (!empty($domnodelist->length)) {
            if ($domnodelist->item(0)->hasAttributes()) {
                $domnode = $domnodelist->item(0)->attributes->getNamedItem('sco-id');

                if (!is_null($domnode)) {
                    $scoid = (int) $domnode->nodeValue;
                }
            }
        }
    }

    return $scoid;
}

/**
 * This function returns the current version of Adobe Connect Server.
 * 
 * @param obj Adobe Connect connection object
 * @return string The Adobe Connect version
 * 
 */
function aconnect_get_version($aconnect) {
    $version = '';
    $params = array('action' => 'common-info');

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('version');

        if (!empty($domnodelist->length)) {
            $version = (string) $domnodelist->item(0)->nodeValue;
        }
    }

    return $version;
}

/**
 * This function returns the user's adobe connect login username based off of
 * the adobe connect module's login configuration settings (Moodle username or
 * Moodle email)
 * 
 * @param int userid
 * @return mixed - user's login username or false if something bad happened
 */
function get_connect_username($userid) {
    global $DB;

    $username = '';
    $param    = array('id' => $userid);
    $record   = $DB->get_record('user', $param, 'id,username,email');

    if (!empty($userid) && !empty($record)) {
        $username = set_username($record->username, $record->email);
    }

    return $username;
}
