<?php
/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_adobeconnect_uninstall() {
    global $DB;

    $result = true;

    $param = array('shortname' => 'adobeconnectparticipant');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
    }

    $param = array('shortname' => 'adobeconnectpresenter');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
    }

    $param = array('shortname' => 'adobeconnecthost');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
    }

    return $result;
}