<?php  // $Id$

require_once('locallib.php');

/**
 * Library of functions and constants for module adobeconnect
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the adobeconnect specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $adobeconnect An object from the form in mod_form.php
 * @return int The id of the newly inserted adobeconnect record
 */
function adobeconnect_add_instance($adobeconnect) {

    global $COURSE, $USER, $DB;
    $adobeconnect->timecreated = time();
    $return = false;
    $meeting = new stdClass();

    // Assign the current user with the Adobe Presenter role
    $context = get_context_instance(CONTEXT_COURSE, $adobeconnect->course);

    if (!has_capability('mod/adobeconnect:meetinghost', $context, $USER->id)) {
        $roleid = get_field('role', 'id', 'shortname', 'adobeconnecthost');

        if (role_assign($roleid, $USER->id, 0, $context->id)) {
            //DEBUG
        } else {
            echo 'role assignment failed'; die();
        }
    }

    $recid = $DB->insert_record('adobeconnect', $adobeconnect);

    if (empty($recid)) {
        return false;
    }

    $aconnect = aconnect_login();
    $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');

    $meeting = clone $adobeconnect;

    if (0 != $adobeconnect->groupmode) { // Allow for multiple groups

        // get all groups for the course
        $crsgroups = groups_get_all_groups($COURSE->id);

        if (empty($crsgroups)) {
            return;
        }

        // Create the meeting for each group
        foreach($crsgroups as $crsgroup) {

            // The teacher role if they don't already have one and
            // Assign them to each group
            if (!groups_is_member($crsgroup->id, $USER->id)) {
                $roleid = get_field('role', 'id', 'shortname', 'editingteacher');

                if (!user_has_role_assignment($USER->id, $roleid, $context->id)) {
                    role_assign($roleid, $USER->id, 0, $context->id);
                }

                groups_add_member($crsgroup->id, $USER->id);

            }

            $meeting->name = $adobeconnect->name . '_' . $crsgroup->name;

            if (!empty($adobeconnect->meeturl)) {
                $meeting->meeturl = $adobeconnect->meeturl   . '_' . $crsgroup->name;
            }

            $meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid);

            // Update permissions for meeting
            if (empty($adobeconnect->meetingpublic)) {
                aconnect_update_meeting_perm($aconnect, $meetingscoid, ADOBE_MEETPERM_PRIVATE);
            } else {
                aconnect_update_meeting_perm($aconnect, $meetingscoid, ADOBE_MEETPERM_PUBLIC);
            }

            // Insert record to activity instance in meeting_groups table
            $record = new stdClass;
            $record->instanceid = $recid;
            $record->meetingscoid = $meetingscoid;
            $record->groupid = $crsgroup->id;

            $record->id = $DB->insert_record('adobeconnect_meeting_groups', $record);

            // Add event to calendar
            $event = new stdClass();

            $event->name = $meeting->name;
            $event->description = $adobeconnect->intro;
            $event->format = 1;
            $event->courseid = $adobeconnect->course;
            $event->groupid = $crsgroup->id;
            $event->userid = 0;
            $event->instance = $recid;
            $event->eventtype = '';
            $event->timestart = $adobeconnect->starttime;
            $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
            $event->visible = 1;
            $event->modulename = 'adobeconnect';

            add_event($event);

        }

    } else { // no groups support
        $meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid);

        // Update permissions for meeting
        if (empty($adobeconnect->meetingpublic)) {
            aconnect_update_meeting_perm($aconnect, $meetingscoid, ADOBE_MEETPERM_PRIVATE);
        } else {
            aconnect_update_meeting_perm($aconnect, $meetingscoid, ADOBE_MEETPERM_PUBLIC);
        }

        // Insert record to activity instance in meeting_groups table
        $record = new stdClass;
        $record->instanceid = $recid;
        $record->meetingscoid = $meetingscoid;
        $record->groupid = 0;

        $record->id = $DB->insert_record('adobeconnect_meeting_groups', $record);

        // Add event to calendar
        $event = new stdClass();

        $event->name = $meeting->name;
        $event->description = $adobeconnect->intro;
        $event->format = 1;
        $event->courseid = $adobeconnect->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->instance = $recid;
        $event->eventtype = '';
        $event->timestart = $adobeconnect->starttime;
        $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
        $event->visible = 1;
        $event->modulename = 'adobeconnect';

        add_event($event);

    }

    // If no meeting URL was submitted,
    // update meeting URL for activity with server assigned URL
    if (empty($adobeconnect->meeturl) and (0 == $adobeconnect->groupmode)) {
        $filter = array('filter-sco-id' => $meetingscoid);
        $meeting = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

        if (!empty($meeting)) {
            $meeting = current($meeting);

            $record = new stdClass();
            $record->id = $recid;
            $record->meeturl = trim($meeting->url, '/');
            $DB->update_record('adobeconnect', $record);
        }
    }

    aconnect_logout($aconnect);
//    $crsgroups = groups_get_course_group($COURSE);
//    print_object($adobeconnect);
//    print_object('---');
//    print_object($crsgroups);
//    print_object('---');
//    print_object($COURSE);
//    die();


    return $recid;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $adobeconnect An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
 /**TODO: change the rest of the DML methods **/
function adobeconnect_update_instance($adobeconnect) {
    global $DB;

    $adobeconnect->timemodified = time();
    $adobeconnect->id = $adobeconnect->instance;

    $aconnect = aconnect_login();

    $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');

    // Look for meetings whose names are similar
    $filter = array('filter-like-name' => $adobeconnect->name);

    $namematches = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

    if (empty($namematches)) {
        $namematches = array();
    }

    // Find meeting URLs that are similar
    $url = $adobeconnect->meeturl;
    $filter = array('filter-like-url-path' => $url);

    $urlmatches = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

    if (empty($urlmatches)) {
        $urlmatches = array();
    } else {
        // format url for comparison
        if ((false === strpos($url, '/')) or (0 != strpos($url, '/'))) {
            $url = '/' . $url;
        }
    }

    // Get all instances of the activity meetings
    $param = array('instanceid' => $adobeconnect->instance);
    $grpmeetings = $DB->get_records('adobeconnect_meeting_groups', $param);

    if (empty($grpmeetings)) {
        $grpmeetings = array();
    }


    // If no errors then check to see if the updated name and URL are actually different
    // If true, then update the meeting names and URLs now.
    $namechange = true;
    $urlchange = true;
    $timechange = true;

    // Look for meeting name change
    foreach($namematches as $matchkey => $match) {
        if (array_key_exists($match->scoid, $grpmeetings)) {
            if (0 == substr_compare($match->name, $adobeconnect->name . '_', 0, strlen($adobeconnect->name . '_'), false)) {
                // Break out of loop and change all referenced meetings
                $namechange = false;
                break;
            } elseif (date('c', $adobeconnect->starttime) == $match->starttime) {
                $timechange = false;
                break;
            } elseif (date('c', $adobeconnect->endtime) == $match->endtime) {
                $timechange = false;
                break;
            }
        }
    }

    // Look for URL change
    foreach($urlmatches as $matchkey => $match) {
        if (array_key_exists($match->scoid, $grpmeetings)) {
            if (0 == substr_compare($match->url, $url . '_', 0, strlen($url . '_'), false)) {
                // Break out of loop and change all referenced meetings
                $urlchange = false;
                break;
            } elseif (date('c', $adobeconnect->starttime) == $match->starttime) {
                $timechange = false;
                break;
            } elseif (date('c', $adobeconnect->endtime) == $match->endtime) {
                $timechange = false;
                break;
            }
        }
    }

    if ($timechange or $urlchange or $namechange) {
        $group = '';

        $meetingobj = new stdClass;
        foreach ($grpmeetings as $scoid => $grpmeeting) {

            if ($adobeconnect->groupmode) {
                $group = groups_get_group($grpmeeting->groupid);
                $group = '_' . $group->name;
            } else {
                $group = '';
            }

            $meetingobj->scoid = $grpmeeting->meetingscoid;
            $meetingobj->name = $adobeconnect->name . $group;
            // updating meeting URL using the API corrupts the meeting for some reason
            //  $meetingobj->meeturl = $data['meeturl'] . '_' . $group->name;
            $meetingobj->starttime = date('c', $adobeconnect->starttime);
            $meetingobj->endtime = date('c', $adobeconnect->endtime);

            // Update each meeting instance
            if (!aconnect_update_meeting($aconnect, $meetingobj, $meetfldscoid)) {
                debugging('error updating meeting', DEBUG_DEVELOPER);
            }

            if (empty($adobeconnect->meetingpublic)) {
                aconnect_update_meeting_perm($aconnect, $grpmeeting->meetingscoid, ADOBE_MEETPERM_PRIVATE);
            } else {
                aconnect_update_meeting_perm($aconnect, $grpmeeting->meetingscoid, ADOBE_MEETPERM_PUBLIC);
            }

            // Update calendar event
            $params = array('courseid' => $adobeconnect->course,
                            'instance' => $adobeconnect->id,
                            'groupid' => $grpmeeting->groupid);
            $eventid = $DB->get_field('event', 'id', $params);

            if (!empty($eventid)) {
                $event = new stdClass();
                $event->id = $eventid;
                $event->name = $meetingobj->name;
                $event->description = $adobeconnect->intro;
                $event->format = 1;
                $event->courseid = $adobeconnect->course;
                $event->groupid = $grpmeeting->groupid;
                $event->userid = 0;
                $event->instance = $adobeconnect->id;
                $event->eventtype = '';
                $event->timestart = $adobeconnect->starttime;
                $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
                $event->visible = 1;
                $event->modulename = 'adobeconnect';

                update_event($event);
            }
        }
    }



    aconnect_logout($aconnect);

    return $DB->update_record('adobeconnect', $adobeconnect);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function adobeconnect_delete_instance($id) {
    global $DB;

    $param = array('id' => $id);
    if (! $adobeconnect = $DB->get_record('adobeconnect', $param)) {
        return false;
    }

    $result = true;

    // Remove meeting from Adobe connect server
    $param = array('instanceid' => $adobeconnect->id);
    $adbmeetings = $DB->get_records('adobeconnect_meeting_groups', $param);

    if (!empty($adbmeetings)) {
        $aconnect = aconnect_login();
        foreach ($adbmeetings as $meeting) {
            // Update calendar event
            $param = array('courseid' => $adobeconnect->course,
                           'instance' => $adobeconnect->id,
                           'groupid' => $meeting->groupid);
            $eventid = $DB->get_field('event', 'id', $param);

            if (!empty($eventid)) {
                delete_event($eventid);
            }

            aconnect_remove_meeting($aconnect, $meeting->meetingscoid);
        }

        aconnect_logout($aconnect);
    }

    $firstparam = array('id' => $adobeconnect->id);
    $secondparam = array('instanceid' => $adobeconnect->id);
    if (! $DB->delete_records('adobeconnect', $firstparam) and
        ! delete_records('adobeconnect_meeting_groups', $secondparam)) {
        $result = false;
    }

    return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function adobeconnect_user_outline($course, $user, $mod, $adobeconnect) {
    return null;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function adobeconnect_user_complete($course, $user, $mod, $adobeconnect) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in adobeconnect activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function adobeconnect_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function adobeconnect_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of adobeconnect. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $adobeconnectid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function adobeconnect_get_participants($adobeconnectid) {
    return false;
}


/**
 * This function returns if a scale is being used by one adobeconnect
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $adobeconnectid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function adobeconnect_scale_used($adobeconnectid, $scaleid) {
    $return = false;

    //$rec = get_record("adobeconnect","id","$adobeconnectid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of adobeconnect.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any adobeconnect
 */
function adobeconnect_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('adobeconnect', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function adobeconnect_install() {
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
        } else {
            $result = false;
        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($teacherrid, $mrole->id);
    }

/// Fully setup the Adobe Connect Participant role.
    $param = array('shortname' => 'adobeconnectparticipant');
    if ($result && !$mrole = $DB->get_record('role', $param)) {
        if ($rid = create_role(get_string('adobeconnectparticipant', 'adobeconnect'), 'adobeconnectparticipant',
                               get_string('adobeconnectparticipantdescription', 'adobeconnect'))) {

            $param = array('id' => $rid);
            $mrole  = $DB->get_record('role', $param);
            $result = $result && assign_capability('mod/adobeconnect:meetingparticipant', CAP_ALLOW, $mrole->id, $sysctx->id);
        } else {
            $result = false;
        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }


    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', $param)) {
        $result = $result && allow_assign($teacherrid, $mrole->id);
    }


/// Fully setup the Adobe Connect Host role.
    if ($result && !$mrole = $DB->get_record('role', 'shortname', 'adobeconnecthost')) {
        if ($rid = create_role(get_string('adobeconnecthost', 'adobeconnect'), 'adobeconnecthost',
                               get_string('adobeconnecthostdescription', 'adobeconnect'))) {

            $param = array('id' => $rid);
            $mrole  = $DB->get_record('role', $param);
            $result = $result && assign_capability('mod/adobeconnect:meetinghost', CAP_ALLOW, $mrole->id, $sysctx->id);
        } else {
            $result = false;
        }
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $coursecreatorrid);
    if (!$DB->get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $coursecreatorrid)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }


    $param = array('allowassign' => $mrole->id,
                   'roleid' => $editingteacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $editingteacherrid)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }

    $param = array('allowassign' => $mrole->id,
                   'roleid' => $teacherrid);
    if (!$DB->get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $teacherrid)) {
        $result = $result && allow_assign($teacherrid, $mrole->id);
    }

    return $result;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function adobeconnect_uninstall() {
    global $DB;
    $result = true;

    $param = array('shortname' => 'adobeconnectparticipant');
    if ($mrole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($mrole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $mrole->id);
    }

    $param = array('shortname' => 'adobeconnectpresenter');
    if ($prole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($prole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $prole->id);
    }

    $param = array('shortname' => 'adobeconnecthost');
    if ($prole = $DB->get_record('role', $param)) {
        $result = $result && delete_role($prole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $prole->id);
    }

    return $result;
}
