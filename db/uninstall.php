<?php
/**
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php
 *
 * @package   mod-workshop
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is called at the beginning of the uninstallation process to give the module
 * a chance to clean-up its hacks, bits etc. where possible.
 *
 * @return bool true if success
 */
function xmldb_adobeconnect_uninstall() {
    global $DB;

    $result = true;

    $param = array('shortname' => 'adobeconnectparticipant');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
        $result = $result && $DB->delete_records('role_allow_assign', array('allowassign' => $mrole->id));
    }

    $param = array('shortname' => 'adobeconnectpresenter');
    if ($prole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($prole->id);
        $result = $result && $DB->delete_records('role_allow_assign', array('allowassign' => $prole->id));
    }

    $param = array('shortname' => 'adobeconnecthost');
    if ($prole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($prole->id);
        $result = $result && $DB->delete_records('role_allow_assign', array('allowassign' => $prole->id));
    }

    return $result;
}