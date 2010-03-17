<?php // $Id$

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

class meeting_detail_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        // Needed so that form displays below the groups drop down box
        $mform->addElement('html', '<br />');

        $mform->addElement('header', 'meetinginfo', get_string('meetinginfo', 'adobeconnect'));

        // Select Which group meeting to view
        $groupselected  = $this->_customdata['selectedgroup'];
        $starttime      = $this->_customdata['starttime'];
        $endtime        = $this->_customdata['endtime'];
        $recordings     = $this->_customdata['recordings'];
        $adobesession   = $this->_customdata['adobesession'];
        $instance       = $this->_customdata['instanceid'];
        $groupid        = $this->_customdata['groupid'];
        $cmid           = $this->_customdata['cmid'];
        $groupmode      = $this->_customdata['groupmode'];
        $meetingpublic  = $this->_customdata['meetingpublic'];

        $groupobj = new stdClass;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        // Get details for the meeting from the Adobe server
        $scoid = get_field('adobeconnect_meeting_groups', 'meetingscoid', 'instanceid', $instance, 'groupid', $groupid);
        $intro = get_field('adobeconnect', 'intro', 'id', $instance);

        $aconnect = aconnect_login();

        // Get the Meeting details
        $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
        $filter = array('filter-sco-id' => $scoid);
        $meeting = current(aconnect_meeting_exists($aconnect, $meetfldscoid, $filter));

        aconnect_logout($aconnect);

        $meetingname = format_string($meeting->name);
        $mform->addElement('static', 'meetingname', get_string('meetingname', 'adobeconnect'),
                  $meetingname);

        $context = get_context_instance(CONTEXT_MODULE, $cmid);

        if (has_capability('mod/adobeconnect:meetingpresenter', $context)) {
            $url = 'http://' . $CFG->adobeconnect_meethost . ':'
                             . $CFG->adobeconnect_port . $meeting->url;
            $mform->addElement('static', 'meetingurl', get_string('meeturl', 'adobeconnect'),
                      $url);
        }

        $mform->addElement('static', 'meetingstart', get_string('meetingstart', 'adobeconnect'),
                  date("D F, Y", $starttime));
        $mform->addElement('static', 'meetingend', get_string('meetingend', 'adobeconnect'),
                  date("D F, Y", $endtime));


        $mform->addElement('static', 'meetingstart', get_string('meetingintro', 'adobeconnect'), $intro);

        // Check for capability to join or edit participants
        $buttonarray=array();

        $courseid = $this->_customdata['courseid'];
        $userid = $this->_customdata['userid'];

        $context = get_context_instance(CONTEXT_COURSE, $courseid);

        $buttonarray[] = &$mform->createElement('submit', 'joinmeeting', get_string('joinmeeting', 'adobeconnect'));


        if (has_capability('mod/adobeconnect:editparticipants', $context, $userid)) {
            $buttonarray[] = &$mform->createElement('submit', 'participants', get_string('selectparticipants', 'adobeconnect'));
        }


        $btn = button_to_popup_window('ack.html?id=6', 'btnname', 'linkname', 400, 500, null, null, true);
        $mform->addElement('html', $btn);
        $mform->addElement('submit', 'participants', get_string('selectparticipants', 'adobeconnect'));

        $mform->closeHeaderBefore('participants');
        $showrecordings = false;
        // Check if meeting is private, if so check the user's capability.  If public show recorded meetings
        if (!$meetingpublic) {
            if (has_capability('mod/adobeconnect:meetingpresenter', $context, $userid) or
                has_capability('mod/adobeconnect:meetingparticipant', $context, $userid)) {
                    $showrecordings = true;
            }
        } else {
            $showrecordings = true;
        }

        if ($showrecordings and !empty($recordings)) {
            $mform->addElement('header', 'recordinghdr', get_string('recordinghdr', 'adobeconnect'));

            foreach ($recordings as $key => $recordinggrp) {
                if (!empty($recordinggrp)) {
                    foreach($recordinggrp as $recording) {

                        $mform->addElement('html', '<a href="http://'.$CFG->adobeconnect_meethost.':'.
                                                   $CFG->adobeconnect_port.$recording->url.'?session='.$adobesession.
                                                   '" target="_blank">'.$recording->name.'</a><br />');
                    }
                }
            }
        }

    }
}
?>