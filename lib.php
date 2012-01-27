<?php  // $Id: lib.php,v 1.1.2.9 2011/05/03 22:42:07 adelamarre Exp $

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

/// (replace adobeconnect with the name of your module and delete this line)

$adobeconnect_EXAMPLE_CONSTANT = 42;     /// for example


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

    global $COURSE, $USER;

    $adobeconnect->timecreated  = time();
    $adobeconnect->meeturl      = adobeconnect_clean_meet_url($adobeconnect->meeturl);

    $return = false;
    $meeting = new stdClass();

    // Assign the current user with the Adobe Presenter role
    $context = get_context_instance(CONTEXT_COURSE, $adobeconnect->course);

    if (!has_capability('mod/adobeconnect:meetinghost', $context, $USER->id, false)) {
        $roleid = get_field('role', 'id', 'shortname', 'adobeconnecthost');

        if (role_assign($roleid, $USER->id, 0, $context->id)) {
            //DEBUG
        } else {
            echo 'role assignment failed'; die();
        }
    }

    $recid = insert_record('adobeconnect', $adobeconnect);

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
            debugging('Trying to create an activity in group mode, but the course (or the selected grouping) has no groups defined.', DEBUG_DEVELOPER);
            if (!delete_records('adobeconnect', 'id', $recid)) {
                debugging('Could not delete Moodle\'s record of the meeting which was not created in Adobe Connect.', DEBUG_DEVELOPER);
            }
            return false;
        }

        // Create place to store scoids of meetings succesfully created on server in case they need to be deleted
        $rollback = array();

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
                $meeting->meeturl = adobeconnect_clean_meet_url($adobeconnect->meeturl   . '_' . $crsgroup->name);
            }

            if (!$meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid)) {
                debugging('There was an error creating the meeting "'.$meeting->name.'" on the Adobe Connect Server.', DEBUG_DEVELOPER);
                //delete all local records for this meeting (or meetings if in group mode)
                $result = delete_records('adobeconnect', 'id', $recid);
                $result2 = delete_records('adobeconnect_meeting_groups', 'instanceid', $recid);
                if (!$result || !$result2) {
                    debugging('There was a problem deleting Moodle\'s record of the meeting "'.$meeting->name.'" (which was not created on the AdobeConnect server).', DEBUG_DEVELOPER);
                }
                //delete any meetings on the server
                //(for example if it created a couple of meetings for a group activity before failing)
                foreach ($rollback as $meetingscoid) {
                    if (!aconnect_remove_meeting($aconnect, $meetingscoid)) {
                        debugging('There was a problem deleting Adobe Connect\'s record of the meeting "'.$meetingobj->name.'" from the server.', DEBUG_DEVELOPER);
                    }
                }
                return false;
            } else {
                $rollback[] = $meetingscoid;
            }

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

            $record->id = insert_record('adobeconnect_meeting_groups', $record);

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
        if (!$meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid)) {
            debugging('There was an error creating the meeting "'.$meeting->name.'" on the Adobe Connect Server.', DEBUG_DEVELOPER);
            //delete all local records for this meeting (or meetings if in group mode)
            $result = delete_records('adobeconnect', 'id', $recid);
            $result2 = delete_records('adobeconnect_meeting_groups', 'instanceid', $recid);
            if (!$result || !$result2) {
                debugging('There was a problem deleting Moodle\'s record of the meeting "'.$meeting->name.'" (which was not created on the AdobeConnect server).', DEBUG_DEVELOPER);
            }
            return false;
        }

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

        $record->id = insert_record('adobeconnect_meeting_groups', $record);

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
        update_record('adobeconnect', $record);
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
function adobeconnect_update_instance($adobeconnect) {

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

    $url = adobeconnect_clean_meet_url($url);

    // Get all instances of the activity meetings
    $grpmeetings = get_records('adobeconnect_meeting_groups', 'instanceid', $adobeconnect->instance);

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
            $eventid = get_field('event', 'id', 'courseid', $adobeconnect->course,
                                 'instance', $adobeconnect->id, 'groupid', $grpmeeting->groupid);

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

    return update_record('adobeconnect', $adobeconnect);
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

    if (! $adobeconnect = get_record('adobeconnect', 'id', $id)) {
        return false;
    }

    $result = true;

    // Remove meeting from Adobe connect server
    $adbmeetings = get_records('adobeconnect_meeting_groups', 'instanceid', $adobeconnect->id);

    if (!empty($adbmeetings)) {
        $aconnect = aconnect_login();
        foreach ($adbmeetings as $meeting) {
            // Update calendar event
            $eventid = get_field('event', 'id', 'courseid', $adobeconnect->course,
                                 'instance', $adobeconnect->id, 'groupid', $meeting->groupid);

            if (!empty($eventid)) {
                delete_event($eventid);
            }

            aconnect_remove_meeting($aconnect, $meeting->meetingscoid);
        }

        aconnect_logout($aconnect);
    }

    $result &= delete_records('adobeconnect', 'id', $adobeconnect->id);
    $result &= delete_records('adobeconnect_meeting_groups', 'instanceid', $adobeconnect->id);

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
    $result = true;
    $timenow = time();
    $sysctx  = get_context_instance(CONTEXT_SYSTEM);

//    $adminrid          = get_field('role', 'id', 'shortname', 'admin');
    $coursecreatorrid  = get_field('role', 'id', 'shortname', 'coursecreator');
    $editingteacherrid = get_field('role', 'id', 'shortname', 'editingteacher');
    $teacherrid        = get_field('role', 'id', 'shortname', 'teacher');

/// Fully setup the Adobe Connect Presenter role.
    if ($result && !$mrole = get_record('role', 'shortname', 'adobeconnectpresenter')) {
        if ($rid = create_role(get_string('adobeconnectpresenter', 'adobeconnect'), 'adobeconnectpresenter',
                               get_string('adobeconnectpresenterdescription', 'adobeconnect'))) {

            $mrole  = get_record('role', 'id', $rid);
            $result = $result && assign_capability('mod/adobeconnect:meetingpresenter', CAP_ALLOW, $mrole->id, $sysctx->id);
        } else {
            $result = false;
        }
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $coursecreatorrid)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $editingteacherrid)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $teacherrid)) {
        $result = $result && allow_assign($teacherrid, $mrole->id);
    }

/// Fully setup the Adobe Connect Participant role.
    if ($result && !$mrole = get_record('role', 'shortname', 'adobeconnectparticipant')) {
        if ($rid = create_role(get_string('adobeconnectparticipant', 'adobeconnect'), 'adobeconnectparticipant',
                               get_string('adobeconnectparticipantdescription', 'adobeconnect'))) {

            $mrole  = get_record('role', 'id', $rid);
            $result = $result && assign_capability('mod/adobeconnect:meetingparticipant', CAP_ALLOW, $mrole->id, $sysctx->id);
        } else {
            $result = false;
        }
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $coursecreatorrid)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $editingteacherrid)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $teacherrid)) {
        $result = $result && allow_assign($teacherrid, $mrole->id);
    }


/// Fully setup the Adobe Connect Host role.
    if ($result && !$mrole = get_record('role', 'shortname', 'adobeconnecthost')) {
        if ($rid = create_role(get_string('adobeconnecthost', 'adobeconnect'), 'adobeconnecthost',
                               get_string('adobeconnecthostdescription', 'adobeconnect'))) {

            $mrole  = get_record('role', 'id', $rid);
            $result = $result && assign_capability('mod/adobeconnect:meetinghost', CAP_ALLOW, $mrole->id, $sysctx->id);
        } else {
            $result = false;
        }
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $coursecreatorrid)) {
        $result = $result && allow_assign($coursecreatorrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $editingteacherrid)) {
        $result = $result && allow_assign($editingteacherrid, $mrole->id);
    }

    if (!get_field('role_allow_assign', 'id', 'allowassign', $mrole->id, 'roleid', $teacherrid)) {
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
    $result = true;

    if ($mrole = get_record('role', 'shortname', 'adobeconnectparticipant')) {
        $result = $result && delete_role($mrole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $mrole->id);
    }

    if ($prole = get_record('role', 'shortname', 'adobeconnectpresenter')) {
        $result = $result && delete_role($prole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $prole->id);
    }

    if ($prole = get_record('role', 'shortname', 'adobeconnecthost')) {
        $result = $result && delete_role($prole->id);
        $result = $result && delete_records('role_allow_assign', 'allowassign', $prole->id);
    }

    return $result;
}

/**
 * Meeting URLs need to start with an alpha then be alphanumeric or hyphen('-')
 *
 * @param string $meeturl Incoming URL
 * @return string cleaned URL
 */
function adobeconnect_clean_meet_url($meeturl) {
    $meeturl = preg_replace ('/[^a-z0-9]/i', '-', $meeturl);
    return $meeturl;
}
?>