<?php  // $Id: lib.php,v 1.9 2011/05/03 22:43:25 adelamarre Exp $
/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

$adobeconnect_EXAMPLE_CONSTANT = 42;

/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');
/** Include calendar/lib.php */
require_once($CFG->dirroot.'/calendar/lib.php');


/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function adobeconnect_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

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

    $adobeconnect->timecreated  = time();
    $adobeconnect->meeturl      = adobeconnect_clean_meet_url($adobeconnect->meeturl);
    $adobeconnect->userid       = $USER->id;

    $return       = false;
    $meeting      = new stdClass();
    $username     = set_username($USER->username, $USER->email);
    $meetfldscoid = '';

    // Assign the current user with the Adobe Presenter role
    $context = get_context_instance(CONTEXT_COURSE, $adobeconnect->course);

    if (!has_capability('mod/adobeconnect:meetinghost', $context, $USER->id, false)) {

        $param = array('shortname' => 'adobeconnecthost');
        $roleid = $DB->get_field('role', 'id', $param);

        if (role_assign($roleid, $USER->id, $context->id, 'mod_adobeconnect')) {
            //DEBUG
        } else {
            debugging('role assignment failed', DEBUG_DEVELOPER);
            return false;
        }
    }

    $recid = $DB->insert_record('adobeconnect', $adobeconnect);

    if (empty($recid)) {
        debugging('creating adobeconnect module instance failed', DEBUG_DEVELOPER);
        return false;
    }
    
    $aconnect = aconnect_login();
    
    // Get the user's meeting folder location, if non exists then get the shared
    // meeting folder location
    $meetfldscoid = aconnect_get_user_folder_sco_id($aconnect, $username);
    if (empty($meetfldscoid)) {
        $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
    }

    $meeting = clone $adobeconnect;

    if (0 != $adobeconnect->groupmode) { // Allow for multiple groups

        // get all groups for the course
        $crsgroups = groups_get_all_groups($COURSE->id);

        if (empty($crsgroups)) {
            return 0;
        }

        require_once(dirname(dirname(dirname(__FILE__))).'/group/lib.php');

        // Create the meeting for each group
        foreach($crsgroups as $crsgroup) {

            // The teacher role if they don't already have one and
            // Assign them to each group
            if (!groups_is_member($crsgroup->id, $USER->id)) {

                groups_add_member($crsgroup->id, $USER->id);

            }

            $meeting->name = $adobeconnect->name . '_' . $crsgroup->name;

            if (!empty($adobeconnect->meeturl)) {
                $meeting->meeturl = adobeconnect_clean_meet_url($adobeconnect->meeturl   . '_' . $crsgroup->name);
            }

            // If creating the meeting failed, then return false and revert the group role assignments
            if (!$meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid)) {
                
                groups_remove_member($crsgroup->id, $USER->id);
                debugging('error creating meeting', DEBUG_DEVELOPER);
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
            $record->groupid = $crsgroup->id;

            $record->id = $DB->insert_record('adobeconnect_meeting_groups', $record);

            // Add event to calendar
            $event = new stdClass();

            $event->name = $meeting->name;
            $event->description = format_module_intro('adobeconnect', $adobeconnect, $adobeconnect->coursemodule);
            $event->courseid = $adobeconnect->course;
            $event->groupid = $crsgroup->id;
            $event->userid = 0;
            $event->instance = $recid;
            $event->eventtype = 'group';
            $event->timestart = $adobeconnect->starttime;
            $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
            $event->visible = 1;
            $event->modulename = 'adobeconnect';

            calendar_event::create($event);

        }

    } else { // no groups support
        $meetingscoid = aconnect_create_meeting($aconnect, $meeting, $meetfldscoid);
        
        // If creating the meeting failed, then return false and revert the group role assignments
        if (!$meetingscoid) {
            debugging('error creating meeting', DEBUG_DEVELOPER);
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

        $record->id = $DB->insert_record('adobeconnect_meeting_groups', $record);

        // Add event to calendar
        $event = new stdClass();

        $event->name = $meeting->name;
        $event->description = format_module_intro('adobeconnect', $adobeconnect, $adobeconnect->coursemodule);
        $event->courseid = $adobeconnect->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->instance = $recid;
        $event->eventtype = 'course';
        $event->timestart = $adobeconnect->starttime;
        $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
        $event->visible = 1;
        $event->modulename = 'adobeconnect';

        calendar_event::create($event);

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
    global $DB, $USER;

    $adobeconnect->timemodified = time();
    $adobeconnect->id           = $adobeconnect->instance;
    
    $meetfldscoid = '';

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
            
            /* if the userid is not empty then set the meeting folder sco id to 
               the user's connect folder.  If this line of code is not executed
               then user's meetings that were previously in the user's connect folder
               would be moved into the shared folder */
            if (!empty($adobeconnect->userid)) {
                
                $username = get_connect_username($adobeconnect->userid);
                $user_folder = aconnect_get_user_folder_sco_id($aconnect, $username);
                
                if (!empty($user_folder)) {
                    $meetfldscoid = $user_folder;
                }

            }
            
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
            $param = array('courseid' => $adobeconnect->course, 'instance' =>
                           $adobeconnect->id, 'groupid' => $grpmeeting->groupid,
                           'modulename' => 'adobeconnect');

            $eventid = $DB->get_field('event', 'id', $param);

            if (!empty($eventid)) {

                $event = new stdClass();
                $event->id = $eventid;
                $event->name = $meetingobj->name;
                $event->description = format_module_intro('adobeconnect', $adobeconnect, $adobeconnect->coursemodule);
                $event->courseid = $adobeconnect->course;
                $event->groupid = $grpmeeting->groupid;
                $event->userid = 0;
                $event->instance = $adobeconnect->id;
                $event->eventtype = 0 == $grpmeeting->groupid ? 'course' : 'group';
                $event->timestart = $adobeconnect->starttime;
                $event->timeduration = $adobeconnect->endtime - $adobeconnect->starttime;
                $event->visible = 1;
                $event->modulename = 'adobeconnect';

                $calendarevent = calendar_event::load($eventid);
                $calendarevent->update($event);
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
            $param = array('courseid' => $adobeconnect->course, 'instance' => $adobeconnect->id,
                           'groupid' => $meeting->groupid, 'modulename' => 'adobeconnect');
            $eventid = $DB->get_field('event', 'id', $param);

            if (!empty($eventid)) {
                $event = calendar_event::load($eventid);
                $event->delete();
            }

            aconnect_remove_meeting($aconnect, $meeting->meetingscoid);
        }

        aconnect_logout($aconnect);
    }

    $param = array('id' => $adobeconnect->id);
    $result &= $DB->delete_records('adobeconnect', $param);

    $param = array('instanceid' => $adobeconnect->id);
    $result &= $DB->delete_records('adobeconnect_meeting_groups', $param);

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
    return false;
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
    return false;
}

/**
 * Meeting URLs need to start with an alpha then be alphanumeric
 * or hyphen('-')
 *
 * @param string $meeturl Incoming URL
 * @return string cleaned URL
 */
function adobeconnect_clean_meet_url($meeturl) {
    $meeturl = preg_replace ('/[^a-z0-9]/i', '-', $meeturl);
    return $meeturl;
}
