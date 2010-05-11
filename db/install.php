<?php // $Id$
/**
 * This file replaces:
 *   * STATEMENTS section in db/install.xml
 *   * lib.php/modulename_install() post installation hook
 *   * partially defaults.php
 *
 * @package   adobeconnect
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o
 */

function xmldb_adobeconnect_install() {
    global $DB;

    $result = true;
    $timenow = time();
    $sysctx  = get_context_instance(CONTEXT_SYSTEM);

//    $adminrid          = get_field('role', 'id', 'shortname', 'admin');
    $param = array('shortname' => 'coursecreator');
    $coursecreatorrid  = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'editingteacher');
    $editingteacherrid = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'teacher');
    $teacherrid        = $DB->get_field('role', 'id', $param);

/// Fully setup the Adobe Connect Presenter role.
    $param = array('shortname' => 'adobeconnectpresenter');
    if ($result && !$mrole = $DB->get_record('role', $param)) {
        if ($rid = create_role(get_string('adobeconnectpresenter', 'adobeconnect'), 'adobeconnectpresenter',
                               get_string('adobeconnectpresenterdescription', 'adobeconnect'))) {

            $param = array('id' => $rid);
            $mrole  = $DB->get_record('role', $param);
            $result = $result && assign_capability('mod/adobeconnect:meetingpresenter', CAP_ALLOW, $mrole->id, $sysctx->id);

            // Set role context level to course
            set_role_contextlevels($mrole->id, array(CONTEXT_COURSE, CONTEXT_MODULE));
        } else {
            $result = false;

        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($coursecreatorrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($editingteacherrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($teacherrid, $mrole->id);
    }


/// Fully setup the Adobe Connect Participant role.
    $param = array('shortname' => 'adobeconnectparticipant');
    if ($result && !$mrole = $DB->get_record('role', $param)) {
        if ($rid = create_role(get_string('adobeconnectparticipant', 'adobeconnect'), 'adobeconnectparticipant',
                               get_string('adobeconnectparticipantdescription', 'adobeconnect'))) {
            $param = array('id' => $rid);
            $mrole  = $DB->get_record('role', $param);
            $result = $result && assign_capability('mod/adobeconnect:meetingparticipant', CAP_ALLOW, $mrole->id, $sysctx->id);

            // Set role context level to course
            set_role_contextlevels($mrole->id, array(CONTEXT_COURSE, CONTEXT_MODULE));
        } else {
            $result = false;
        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($coursecreatorrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($editingteacherrid, $mrole->id);
    }


    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($teacherrid, $mrole->id);
    }


/// Fully setup the Adobe Connect Host role.
    $param = array('shortname' => 'adobeconnecthost');
    if ($result && !$mrole = $DB->get_record('role', $param)) {
        if ($rid = create_role(get_string('adobeconnecthost', 'adobeconnect'), 'adobeconnecthost',
                               get_string('adobeconnecthostdescription', 'adobeconnect'))) {

            $param = array('id' => $rid);
            $mrole  = $DB->get_record('role', $param);
            $result = $result && assign_capability('mod/adobeconnect:meetinghost', CAP_ALLOW, $mrole->id, $sysctx->id);

            // Set role context level to course
            set_role_contextlevels($mrole->id, array(CONTEXT_COURSE, CONTEXT_MODULE));

        } else {
            $result = false;
        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($coursecreatorrid, $mrole->id);
    }


    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($editingteacherrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        // role_assign doesn't return anything May 10th
        allow_assign($teacherrid, $mrole->id);
    }

/// Install logging support
    update_log_display_entry('adobeconnect', 'add', 'adobeconnect', 'name');
    update_log_display_entry('adobeconnect', 'update', 'adobeconnect', 'name');
    update_log_display_entry('adobeconnect', 'view', 'adobeconnect', 'name');

    return $result;
}
?>