<?php // $Id$
require_once('connect_class.php');

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

define('ADOBE_MEETPERM_PUBLIC', 0); //means the Acrobat Connect meeting is public, and anyone who has the URL for the meeting can enter the room.
define('ADOBE_MEETPERM_PROTECTED', 1); //means the meeting is protected, and only registered users and accepted guests can enter the room.
define('ADOBE_MEETPERM_PRIVATE', 2); // means the meeting is private, and only registered users and participants can enter the room

function aconnect_login() {
    global $CFG;

    try {
    $aconnect = new connect_class($CFG->adobeconnect_host,
                                  $CFG->adobeconnect_port,
                                  $CFG->adobeconnect_admin_login,
                                  $CFG->adobeconnect_admin_password);

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

    } catch (Exception $e) {
        debugging("There was an error communicating with the Adobe Connect server. 22", DEBUG_DEVELOPER);
        return false;
    }
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

function aconnect_get_meetings($xml) {
    $scoid = false;
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $scoid;
    }

    foreach($xml->shortcuts[0]->sco as $key => $sco) {
        if (0 == strcmp('meetings', $sco['type'])) {
            $scoid = (int) $sco['sco-id'];
            break;
        }
    }

    return $scoid;
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
        // Get shared templates folder sco-id
        $tempfldscoid = aconnect_get_shared_templates($aconnect->_xmlresponse);
//        $meetfldscoid = aconnect_get_meetings($aconnect->_xmlresponse);
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

    if (false !== $meetfldscoid) {
        $params = array(
            'action' => 'sco-expanded-contents',
            'sco-id' => $meetfldscoid,
            'filter-type' => 'meeting',
        );

        $aconnect->create_request($params);

        if ($aconnect->call_success()) {
            $meetings = aconnect_return_all_meetings($aconnect->_xmlresponse);
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
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $scoid;
    }

    foreach($xml->shortcuts[0]->sco as $key => $sco) {
        if (0 == strcmp('shared-meeting-templates', $sco['type'])) {
            $scoid = $sco['sco-id'];
            break;
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

    foreach($xml->{'expanded-scos'}[0]->sco as $key => $sco) {
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
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $templates;
    }

    foreach($xml->{'expanded-scos'}[0]->sco as $key => $sco) {
        if (0 == strcmp('meeting', $sco['type'])) {
            $tkey = (int) $sco['sco-id'];
            $templates[$tkey] = (string) current($sco->name) .' ' . ADOBE_TEMPLATE_POSTFIX;
        }
    }

    return $templates;
}

/**
 * Returns the Meeting folder sco-id
 * @param object an adobe connection_class object
 * @return mixed adobe connect meeting folder sco-id || false if there was an error
 */
function aconnect_get_meeting_folder($aconnect) {
    $folderscoid = false;
    $params = array('action' => 'sco-shortcuts');

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $folderscoid = aconnect_get_meeting_folder_sco_id($aconnect->_xmlresponse);
//        $params = array('action' => 'sco-contents', 'sco-id' => $folderscoid);
    }

    return $folderscoid;
}

/**
 * Returns the meeting folder sco-id
 * @param string $xml
 * @return int sco-id of the meeting folder
 */
function aconnect_get_meeting_folder_sco_id($xml) {
    $scoid = false;
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $scoid;
    }

    foreach($xml->shortcuts[0]->sco as $key => $sco) {
        if (0 == strcmp('meetings', $sco['type'])) {
            $scoid = (int) $sco['sco-id'];
            break;
        }
    }

    return $scoid;
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
                    'filter-source-sco-id' => $sourcescoid,
                    'sort-name' => 'asc',
                    );

    $aconnect->create_request($params);

    $recordings = array();

    if ($aconnect->call_success()) {
        $xml = new SimpleXMLElement($aconnect->_xmlresponse);
        if (isset($xml->scos->sco)) {
            foreach ($xml->scos->sco as $data) {
                $i = (int) $data['sco-id'];
                $recordings[$i]->name = (string) $data->name;
                $recordings[$i]->url = (string) $data->{'url-path'};
                $recordings[$i]->startdate = (string) $data->{'date-begin'};
                $recordings[$i]->enddate = (string) $data->{'date-end'};
                $recordings[$i]->createdate = (string) $data->{'date-created'};
                $recordings[$i]->modified = (string) $data->{'date-modified'};
                $recordings[$i]->duration = (string) $data->duration;
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
 * Returns the forced archive folder sco-id
 * @param object an adobe connection_class object
 * @param string $folder name of the folder to get
 * (ex. forced-archives = recording folder | meetings = meetings folder
 * | content = shared content folder)
 * @return mixed adobe connect folder sco-id || false if there was an error
 *
 */
function aconnect_get_folder($aconnect, $folder = '') {
    $folderscoid = false;
    $params = array('action' => 'sco-shortcuts');

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $folderscoid = aconnect_get_folder_sco_id($aconnect->_xmlresponse, $folder);
//        $params = array('action' => 'sco-contents', 'sco-id' => $folderscoid);
    }

    return $folderscoid;
}

/**
 * TODO: comment function and return something meaningful
 */
function aconnect_get_folder_sco_id($xml, $folder) {
    $scoid = false;
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $scoid;
    }

    foreach($xml->shortcuts[0]->sco as $key => $sco) {
        if (0 == strcmp($folder, $sco['type'])) {
            $scoid = (int) $sco['sco-id'];
            break;
        }
    }

    return $scoid;
}

/**
 * Parses XML and returns the meeting sco-id
 * @param string XML obtained from a sco-update call
 */
function aconnect_get_meeting_scoid($xml) {
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        echo ' | aconnect_get_meeting_scoid xml empty ';
        return false;
    }

    return (int) $xml->sco['sco-id'];
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
// updating meeting URL using the API corrupts the meeting for some reason
//                    'url-path' => '/'.$meetingobj->meeturl,
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
     $params = array('action' => 'permissions-update',
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

/**
 * Creates a meeting
 * @param obj $aconnect connect_class object
 * @param obj $meetingobj an adobeconnect module object
 * @param int $meetingfdl adobe connect meeting folder sco-id
 * @return mixed meeting sco-id on success || false on error
 */
function aconnect_create_meeting($aconnect, $meetingobj, $meetingfdl) {
    //date("Y-m-d\TH:i
    $params = array('action' => 'sco-update',
                    'type' => 'meeting',
                    'name' => $meetingobj->name,
                    'folder-id' => $meetingfdl,
                    'date-begin' => date("c",$meetingobj->starttime),
                    'date-end' => date("c",$meetingobj->endtime)
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
 * Parses XML looking for a matching meeting sco-id
 * @param string $xml returned XML from a sco-expanded-contents call
 * @return true of meeting sco-id is found otherwise false
 */
function aconnect_meeting_scoid_exists($xml) {
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return false;
    }

    if (isset($xml->{'expanded-scos'}[0]->sco[0])) {
        return true;
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
        $xml = new SimpleXMLElement($aconnect->_xmlresponse);

        if (empty($xml)) {
            return false;
        }

        if (isset($xml->scos[0]->sco[0])) {
            foreach ($xml->scos[0]->sco as $data) {
                $key = (int) $data['sco-id'];
                $matches[$key]->name = (string) $data->name;
                $matches[$key]->url = (string) $data->{'url-path'};
                $matches[$key]->scoid = (int) $data['sco-id'];
                $matches[$key]->starttime = (string) $data->{'date-begin'};
                $matches[$key]->endtime = (string) $data->{'date-end'};
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
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return false;
    }

    if (isset($xml->{'principal-list'}[0]->principal['principal-id'])) {
        return $xml->{'principal-list'}[0]->principal['principal-id'];
    } else {
        return false;
    }
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
//            'filter-type' => 'meeting',
// add more filters if this process begins to get slow
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return aconnect_get_user_principal_id($aconnect->_xmlresponse);
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
 * @return mixed principal-id of the new user or false
 */
function aconnect_create_user($aconnect, $usrdata) {
    $principal_id = false;

    $params = array(
        'action' => 'principal-update',
        'first-name' => $usrdata->firstname,
        'last-name' => $usrdata->lastname,
        'login' => $usrdata->username,
        'password' => $usrdata->username,
        'extlogin' => $usrdata->username,
        'type' => 'user',
        'send-email' => 'false',
        'has-children' => 0,
        'email' => $usrdata->email,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $xml = new SimpleXMLElement($aconnect->_xmlresponse);

        if (empty($xml)) {
            return false;
        }

        if (isset($xml->principal[0])) {
            $principal_id = $xml->principal[0]['principal-id'];
        }
    }

      return $principal_id;
}

function aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $type) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
        'permission-id' => $type, //  host, mini-host, view
        'principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
          return true;
//        print_object($aconnect->_xmlresponse);
    } else {
          return false;
//        print_object($aconnect->_xmlresponse);
    }
}

function aconnect_remove_user_perm($aconnect, $usrprincipal, $meetingscoid) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
        'permission-id' => ADOBE_REMOVE_ROLE, //  host, mini-host, view
        'principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
//        print_object($aconnect->_xmlresponse);
    } else {
//        print_object($aconnect->_xmlresponse);
    }

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
    //  'filter-permission-id' => 'mini-host',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
//        'filter-permission-id' => $perm_type, //  host, mini-host, view
        'filter-principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    if (ADOBE_REMOVE_ROLE != $perm_type) {
        $params['filter-permission-id'] = $perm_type;
    }

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $xml = new SimpleXMLElement($aconnect->_xmlresponse);

        if (empty($xml)) {
            return $hasperm;
        }

        if (isset($xml->permissions[0]->principal)) {
            $hasperm = true;
        }

        if (ADOBE_REMOVE_ROLE != $perm_type and $assign and !$hasperm) {
            // TODO: check return values of the two functions below
            // Assign permission to user
            return aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $perm_type);
        } elseif (ADOBE_REMOVE_ROLE == $perm_type) {
            // Remove user's permission
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
    // Get shared folder sco-id
    $shscoid = aconnect_get_folder($aconnect, 'content');

    // Iterate through list of sco and move them all to the shared folder
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

function aconnect_is_meeting($aconnect, $itemscoid, $meetfldscoid) {
    $params = array(
        'action' => 'sco-contents',
        'sco-id' => $meetfldscoid,
        'filter-type' => 'meeting',
        'filter-sco-id' => $itemscoid,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $xml = new SimpleXMLElement($aconnect->_xmlresponse);

        if (empty($xml)) {
            return false;
        }

        if (isset($xml->scos[0]->sco[0])) {
            return $itemscoid;
        } else {
            return false;
        }
    }
}

/**
 * Assign a role for a list of users
 * @param obj $aconnect a connect_class object
 * @param array an array of user principal ids
 * the user's role is the array key and the user principal id is the value
 * @param int $scoid the sco-id of the content
 *
 */
function assign_roles_to_users($aconnect, $usrlist = array(), $scoid = 0, $roleid = 0) {
    foreach ($usrlist as $usrprincipal) {
        aconnect_check_user_perm($aconnect, $usrprincipal, $scoid, $roleid, true);
    }
}

function save_user_roles($aconnect, $userlist) {
    global $CFG;

    // look for existing meeting
    $instanceid = key($userlist);
    $groupid = key($userlist[$instanceid]);
    $match = false;
    $scotype = false;

    $meeting = get_record('adobeconnect', 'id', $instanceid, '', '', '', '', 'id,name,templatescoid,meeturl,starttime,endtime');

    $sql = "SELECT id, meetingscoid FROM {$CFG->prefix}adobeconnect_meeting_groups WHERE instanceid = $instanceid".
           " AND groupid = $groupid";

    $grpmeetscoid = get_record_sql($sql);

    // Get group name
    $group = groups_get_group($groupid);

    if (empty($group)) {
        $group = new stdClass;
        $group->name = '_';
    }

    if (empty($meeting)) {
        return false;
    }

    $meetfldscoid = aconnect_get_meeting_folder($aconnect);


    if (empty($grpmeetscoid) or !($scotype = aconnect_is_meeting($aconnect, $grpmeetscoid->meetingscoid, $meetfldscoid))) {
        // Create meeting
        $meeting->name .= '_'. $group->name;
        $meeting->meeturl .= '_' . $group->name;

        // Check if the activity is linked to an existing meeting or template
        // if linked to an existing meeting we do not want to create a new meeting
        // if linked to a template, we need to create a meeting from that template

        $meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid);

        if (empty($meetingscoid)) {
//            print_object($aconnect->_xmlresponse);
//            print_object($aconnect->_xmlrequest);
            return;
        } else {
            // DO NOTHING, adobeconnect_group_meetings insertion is handled later on
        }

        // TODO check for return value
        aconnect_update_meeting_perm($aconnect, $meetingscoid, ADOBE_MEETPERM_PRIVATE);

        // insert record
        $record = new stdClass;
        $record->instanceid = $instanceid;
        $record->meetingscoid = $meetingscoid;
        $record->groupid = $groupid;

        $record->id = insert_record('adobeconnect_meeting_groups', $record);

    } else {

        $meetingscoid = $scotype;
        $record = new stdClass;
        $record->id = $grpmeetscoid->id;
        $record->instanceid = $instanceid;
        $record->meetingscoid = $meetingscoid;
        $record->groupid = $groupid;

    }

    // Check to see if a user exists
    $users = current(current($userlist));

    foreach ($users as $roleid => $usr) {
        if (ADOBE_REMOVE == $roleid) { // list of users who were removed from either participants list

            foreach($usr as $userid => $usrdata) {
                // See if the user exists, remove permission from meeting
                if (($usrprincipal = aconnect_user_exists($aconnect, $usrdata))) {
                    // Remove user's role for the meeting
                    aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, $roleid);

                    $sql = "SELECT amu.id FROM {$CFG->prefix}adobeconnect_meeting_users amu JOIN ".
                           "{$CFG->prefix}adobeconnect_meeting_groups amg ON amg.id = amu.meetgroupid ".
                           "WHERE amg.groupid = $groupid AND amg.instanceid = $instanceid AND ".
                           "amu.userid = {$usrdata->id}";
                    $id = get_record_sql($sql);

                    if (!empty($id)) {
                        delete_records('adobeconnect_meeting_users', 'id', $id->id);
                    }
                }

                // Check if user is participant/presenter in any of the activity instance's meetings
                // if not then we must remove their view permission on the recordings shared folder in Adobe
                $sql = "SELECT amu.id FROM {$CFG->prefix}adobeconnect_meeting_users amu JOIN ".
                       "{$CFG->prefix}adobeconnect_meeting_groups amg ON amg.id = amu.meetgroupid ".
                       "WHERE amg.instanceid = $instanceid AND amu.userid = {$usrdata->id}";

                if (!record_exists_sql($sql)) {
                    // Remove view permission
                    $fldid = aconnect_get_folder($aconnect, 'content');
                    aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_REMOVE_ROLE);
                }
            }
        } else { // list of users who were added to either participants list
            foreach($usr as $userid => $usrdata) {
                if (($usrprincipal = aconnect_user_exists($aconnect, $usrdata))) {
                    //User exists

                    // Check the permissions the user has for this meeting
                    aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, $roleid, true);

                } else {
                    // Create user
                    if (($usrprincipal = aconnect_create_user($aconnect, $usrdata))) {
                        // Assign the user's role for the meeting

                        // Check the permissions the user has for this meeting
                        aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, $roleid, true);
                    }
                }

                // Insesrt meeting record for user
                if (!empty($usrprincipal)) {
                    $amurec = new stdClass();
                    $amurec->roleid = $usrdata->roleid;
                    $amurec->userprincipalid = $usrprincipal;
                    $amurec->userid = $usrdata->userid;
                    $amurec->meetgroupid = $record->id; // $record->id is set above with the adobeconnect_meeting_groups id

                    // Check if this user has a record in Adobe Moodle table
                    $sql = "SELECT amu.id FROM {$CFG->prefix}adobeconnect_meeting_users amu JOIN ".
                           "{$CFG->prefix}adobeconnect_meeting_groups amg ON amg.id = amu.meetgroupid ".
                           " WHERE amg.instanceid = $instanceid AND amg.groupid = $groupid AND ".
                           "amu.userid = {$amurec->userid}";
                    $id = get_field_sql($sql);

                    if (empty($id)) {
                        insert_record('adobeconnect_meeting_users', $amurec);
                    } else {
                        $amurec->id = $id;
                        update_record('adobeconnect_meeting_users', $amurec);
                    }

                    // Add view permission
                    $fldid = aconnect_get_folder($aconnect, 'content');
                    aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_VIEW_ROLE);

                }
            }
        }
    }
}

function get_nonparticipant_users($instanceid, $courseid, $groupid = 0) {
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $roles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW, $context);
    $users = array();

    if (empty($roles)) {
        return array();
    }

    $role = current($roles);

    if ($groupid) {
      $users = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC', true, $groupid);
    } else { // GET USER
      $users = get_role_users($role->id, $context, false, 'u.id,u.firstname,u.lastname,u.username,u.email', 'u.lastname ASC');
    }

    if (empty($users) ) {
        $users = array();
    }

    $sql = "SELECT amu.userid, amu.roleid FROM ".
           "{$CFG->prefix}adobeconnect_meeting_users amu JOIN ".
           "{$CFG->prefix}adobeconnect_meeting_groups amg ".
           "ON amu.meetgroupid = amg.id WHERE ".
           " amg.instanceid = $instanceid AND amg.groupid = $groupid";

    $participants = get_records_sql($sql);

    if (empty($participants)) {
        $participants = array();
    }

    foreach($users as $key => $user) {
        foreach($participants as $participant) {
            if ($user->id == $participant->userid) {
                unset($users[$key]);
            }
        }
    }

    return $users;
}

function get_participant_users($instanceid, $groupid) {
    global $CFG;

    $participants = array();


    $sql = "SELECT amu.userid, amu.roleid, u.firstname, u.lastname, u.username, u.email FROM ".
           "{$CFG->prefix}adobeconnect_meeting_users amu JOIN ".
           "{$CFG->prefix}user u ON u.id = amu.userid JOIN ".
           "{$CFG->prefix}adobeconnect_meeting_groups amg ON ".
           " amg.id = amu.meetgroupid WHERE ".
           " amg.instanceid = $instanceid AND amg.groupid = $groupid".
           " AND u.deleted = 0 ORDER BY u.lastname ASC";


    $participants = get_records_sql($sql);

    if (empty($participants)) {
        $participants = array();
    }

    return $participants;
}
?>