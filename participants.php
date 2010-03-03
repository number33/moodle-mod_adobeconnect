<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/participants_form.php');


$id = required_param('id', PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // adobeconnect instance ID
$groupid = required_param('groupid', PARAM_INT);

if (! $cm = get_coursemodule_from_id('adobeconnect', $id)) {
    error('Course Module ID was incorrect');
}

if (! $course = get_record('course', 'id', $cm->course)) {
    error('Course is misconfigured');
}

if (! $adobeconnect = get_record('adobeconnect', 'id', $cm->instance)) {
    error('Course module is incorrect');
}

require_login($course, true, $cm);

if (!isset($SESSION->participant_users)) {
    $SESSION->participant_users = array();
}

$group = groups_get_group($groupid);

$stradobeconnects = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect  = get_string('modulename', 'adobeconnect');
$stradobeparticipants = get_string('crumbparticipants', 'adobeconnect');

$navlinks = array();
$navlinks[] = array('name' => $stradobeconnects, 'link' => "index.php?id={$course->id}", 'type' => 'activity');
$navlinks[] = array('name' => format_string($adobeconnect->name), 'link' => "view.php?id={$cm->id}", 'type' => 'activityinstance');
$navlinks[] = array('name' => format_string($stradobeparticipants), 'link' => '', 'type' => 'title');
if (!empty($group)) {
    $navlinks[] = array('name' => format_string($group->name), 'link' => '', 'type' => 'title');
}

$navigation = build_navigation($navlinks);

print_header_simple(format_string($adobeconnect->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $stradobeconnect), navmenu($course, $cm));

$ausers = get_nonparticipant_users($adobeconnect->id, $cm->course, $groupid);
$susers = get_participant_users($adobeconnect->id, $groupid);

//$participants = new parcipants_view_form(null, array('ausers' => $ausers, 'susers' => $susers, 'groupname' => $group->name));

if (($data = data_submitted($CFG->wwwroot . '/mod/adobeconnect/participants.php')) && confirm_sesskey()) {
//if ($data = $participants->get_data(false)) {

    if (isset($data->addpresenter)) { // Adding presenters
    
        if (isset($data->ausers)) {

            foreach ($data->ausers as $key => $usr) { // Populate participant users list
                if (isset($ausers[$usr])) {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER][$usr] = $ausers[$usr];
                } else {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER][$usr] = $susers[$usr];
                }
                unset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr]);
            }
        }
    } elseif (isset($data->removepresenter)) { // Removing presenters
        if (isset($data->presenter)) {
            foreach($data->presenter as $key => $usr) {
                if (isset($susers[$usr])) {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr] = $susers[$usr];
                } else {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr] = $ausers[$usr];
                }
                unset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER][$usr]);
                
            }
        }
    } elseif (isset($data->addparticipant)) { // Adding participant
        if (isset($data->ausers)) {
            foreach ($data->ausers as $key => $usr) { // Populate participant users list
                if (isset($ausers[$usr])) {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT][$usr] = $ausers[$usr];
                } else {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT][$usr] = $susers[$usr];
                }
                unset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr]);
            }
        }
    } elseif (isset($data->removeparticipant)) { // Removing participant
        if (isset($data->participants)) {
            foreach($data->participants as $key => $usr) {
                if (isset($susers[$usr])) {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr] = $susers[$usr];
                } else {
                    $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE][$usr] = $ausers[$usr];
                }
                unset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT][$usr]);
                
            }
        }
    } elseif (isset($data->savechanges)) {
        if (isset($SESSION->participant_users) and !empty($SESSION->participant_users)) { 
            $aconnect = aconnect_login();
            save_user_roles($aconnect, $SESSION->participant_users);
            aconnect_logout($aconnect);
            unset($SESSION->participant_users);
            // Refresh list of participants
            $ausers = get_nonparticipant_users($adobeconnect->id, $cm->course, $groupid);
            $susers = get_participant_users($adobeconnect->id, $groupid);
        }
    }
    
//    print_object($data);

//    print_object($susers);
}

// Update available and selected user lists
if (!empty($SESSION->participant_users)) {
    $templist = array();
    
    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER])) {
        $templist = $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER];
    } else {
        $templist = array();
    }
        
    foreach ($templist as $userid => $temp) { 
        $susers[$userid] = isset($ausers[$userid]) ? $ausers[$userid] : $susers[$userid];
        $susers[$userid]->roleid = ADOBE_PRESENTER;
        $susers[$userid]->userid = $userid;
        unset($ausers[$userid]);
    }

    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT])) {
        $templist = $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT];
    } else {
        $templist = array();
    }
    
    foreach ($templist as $userid => $temp) { // Update available and selected user lists
        $susers[$userid] = isset($ausers[$userid]) ? $ausers[$userid] : $susers[$userid];
        $susers[$userid]->roleid = ADOBE_PARTICIPANT;
        $susers[$userid]->userid = $userid;
        unset($ausers[$userid]);
    }

    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE])) {
        $templist = $SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE];
    } else {
        $templist = array();
    }
    
    foreach ($templist as $userid => $temp) { // Update available and selected user lists
        if (isset($susers[$userid])) {
            $ausers[$userid] = $susers[$userid];
            $ausers[$userid]->roleid = ADOBE_REMOVE;
            $ausers[$userid]->id = $userid;
            unset($susers[$userid]);
        }
    }

}

//DEBUG
/*
print_object('----DEBUG-----');
if(!empty($SESSION->participant_users)) {
    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT])) {
      print_object($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PARTICIPANT]);
    }
    print_object('+------+');
    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER])) {
      print_object($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_PRESENTER]);
    }
    print_object('+------+');
    if (isset($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE])) {
      print_object($SESSION->participant_users[$adobeconnect->id][$groupid][ADOBE_REMOVE]);
    }

}
print_object('----DEBUG END-----');
*/
//end DEBUG

$sesskey         = !empty($USER->sesskey) ? $USER->sesskey : '';

//$participants = new parcipants_view_form(null, array('ausers' => $ausers, 'susers' => $susers, 'groupname' => $group->name));

//$participants->set_data(array('id' => $id, 'groupid' => $groupid));

//$participants->display();

include($CFG->dirroot . '/mod/adobeconnect/participants.html');
 
/// Finish the page
print_footer($course);
?>
