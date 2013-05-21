<?php
/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/adobeconnect/locallib.php');

class mod_adobeconnect_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE, $CFG;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('adobeconnectname', 'adobeconnect'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $this->add_intro_editor(false, get_string('adobeconnectintro', 'adobeconnect'));

//        $mform->addElement('htmleditor', 'intro', get_string('adobeconnectintro', 'adobeconnect'));
//        $mform->setType('intro', PARAM_RAW);
//        $mform->addRule('intro', get_string('required'), 'required', null, 'client');
//        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

    /// Adding "introformat" field
//        $mform->addElement('format', 'introformat', get_string('format'));

//-------------------------------------------------------------------------------
    /// Adding the rest of adobeconnect settings, spreeading all them into this fieldset
    /// or adding more fieldsets ('header' elements) if needed for better logic

        $mform->addElement('header', 'adobeconnectfieldset', get_string('adobeconnectfieldset', 'adobeconnect'));

        // Meeting URL
        $attributes=array('size'=>'20');
        $mform->addElement('text', 'meeturl', get_string('meeturl', 'adobeconnect'), $attributes);
        $mform->setType('meeturl', PARAM_PATH);
        $mform->addHelpButton('meeturl', 'meeturl', 'adobeconnect');
//        $mform->addHelpButton('meeturl', array('meeturl', get_string('meeturl', 'adobeconnect'), 'adobeconnect'));
        $mform->disabledIf('meeturl', 'tempenable', 'eq', 0);

        // Public or private meeting
        $meetingpublic = array(1 => get_string('public', 'adobeconnect'), 0 => get_string('private', 'adobeconnect'));
        $mform->addElement('select', 'meetingpublic', get_string('meetingtype', 'adobeconnect'), $meetingpublic);
        $mform->addHelpButton('meetingpublic', 'meetingtype', 'adobeconnect');
//        $mform->addHelpButton('meetingpublic', array('meetingtype', get_string('meetingtype', 'adobeconnect'), 'adobeconnect'));

        // Meeting Template
        $templates = array();
        $templates = $this->get_templates();
        ksort($templates);
        $mform->addElement('select', 'templatescoid', get_string('meettemplates', 'adobeconnect'), $templates);
        $mform->addHelpButton('templatescoid', 'meettemplates', 'adobeconnect');
//        $mform->addHelpButton('templatescoid', array('templatescoid', get_string('meettemplates', 'adobeconnect'), 'adobeconnect'));
        $mform->disabledIf('templatescoid', 'tempenable', 'eq', 0);


        $mform->addElement('hidden', 'tempenable');
        $mform->setType('type', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('type', PARAM_INT);

        // Start and end date selectors
        $time       = time();
        $starttime  = usertime($time);
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'adobeconnect'));
        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'adobeconnect'));
        $mform->setDefault('endtime', strtotime('+2 hours'));


//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements(array('groups' => true));

        // Disabled the group mode if the meeting has already been created
        $mform->disabledIf('groupmode', 'tempenable', 'eq', 0);
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    function data_preprocessing(&$default_values) {
        global $CFG, $DB;

        if (array_key_exists('update', $default_values)) {

            $params = array('instanceid' => $default_values['id']);
            $sql = "SELECT id FROM {adobeconnect_meeting_groups} WHERE ".
                   "instanceid = :instanceid";

            if ($DB->record_exists_sql($sql, $params)) {
                $default_values['tempenable'] = 0;
            }
        }
    }

    function validation($data, $files) {
        global $CFG, $DB, $USER, $COURSE;

        $errors = parent::validation($data, $files);

        $username     = set_username($USER->username, $USER->email);
        $usr_fldscoid = '';
        $aconnect     = aconnect_login();

        // Search for a Meeting with the same starting name.  It will cause a duplicate
        // meeting name (and error) when the user begins to add participants to the meeting
        $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
        $filter = array('filter-like-name' => $data['name']);
        $namematches = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);        
        
        /// Search the user's adobe connect folder
        $usrfldscoid = aconnect_get_user_folder_sco_id($aconnect, $username);

	if (!empty($usrfldscoid)) {
        	$namematches = $namematches + aconnect_meeting_exists($aconnect, $usrfldscoid, $filter);
        }
        
        if (empty($namematches)) {
            $namematches = array();
        }

        // Now search for existing meeting room URLs
        $url = $data['meeturl'];
        $url = $data['meeturl'] = adobeconnect_clean_meet_url($data['meeturl']);

        // Check to see if there are any trailing slashes or additional parts to the url
        // ex. mymeeting/mysecondmeeting/  Only the 'mymeeting' part is valid
        if ((0 != substr_count($url, '/')) and (false !== strpos($url, '/', 1))) {
            $errors['meeturl'] = get_string('invalidadobemeeturl', 'adobeconnect');
        }

        $filter = array('filter-like-url-path' => $url);
        $urlmatches = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);
        
        /// Search the user's adobe connect folder
        if (!empty($usrfldscoid)) {
            $urlmatches = $urlmatches + aconnect_meeting_exists($aconnect, $usrfldscoid, $filter);
        }

        if (empty($urlmatches)) {
            $urlmatches = array();
        } else {

            // format url for comparison
            if ((false === strpos($url, '/')) or (0 != strpos($url, '/'))) {
                $url = '/' . $url;
            }

        }

        // Check URL for correct length and format
        if (strlen($data['meeturl']) > 60) {
            $errors['meeturl'] = get_string('longurl', 'adobeconnect');
        } elseif (empty($data['meeturl'])) {
            // Do nothing
        } elseif (!preg_match('/^[a-z][a-z\-]*/i', $data['meeturl'])) {
            $errors['meeturl'] = get_string('invalidurl', 'adobeconnect');
        }

        // Check for available groups if groupmode is selected
        if ($data['groupmode'] > 0) {
            $crsgroups = groups_get_all_groups($COURSE->id);
            if (empty($crsgroups)) {
                $errors['groupmode'] = get_string('missingexpectedgroups', 'adobeconnect');
            }
        }

        // Adding activity
        if (empty($data['update'])) {

            if ($data['starttime'] == $data['endtime']) {
                $errors['starttime'] = get_string('samemeettime', 'adobeconnect');
                $errors['endtime'] = get_string('samemeettime', 'adobeconnect');
            } elseif ($data['endtime'] < $data['starttime']) {
                $errors['starttime'] = get_string('greaterstarttime', 'adobeconnect');
            }

            // Check for local activities with the same name
            $params = array('name' => $data['name']);
            if ($DB->record_exists('adobeconnect', $params)) {
                $errors['name'] = get_string('duplicatemeetingname', 'adobeconnect');
                return $errors;
            }

            // Check Adobe connect server for duplicated names
            foreach($namematches as $matchkey => $match) {
                if (0 == substr_compare($match->name, $data['name'] . '_', 0, strlen($data['name'] . '_'), false)) {
                    $errors['name'] = get_string('duplicatemeetingname', 'adobeconnect');
                }
            }

            foreach($urlmatches as $matchkey => $match) {
                $matchurl = rtrim($match->url, '/');
                if (0 == substr_compare($matchurl, $url . '_', 0, strlen($url . '_'), false)) {
                    $errors['meeturl'] = get_string('duplicateurl', 'adobeconnect');
                }
            }

        } else {
            // Updating activity
            // Look for existing meeting names, excluding this activity's group meeting(s)
            $params = array('instanceid' => $data['instance']);
            $sql = "SELECT meetingscoid, groupid FROM {adobeconnect_meeting_groups} ".
                   " WHERE instanceid = :instanceid";

            $grpmeetings = $DB->get_records_sql($sql, $params);

            if (empty($grpmeetings)) {
                $grpmeetings = array();
            }

            foreach($namematches as $matchkey => $match) {
                if (!array_key_exists($match->scoid, $grpmeetings)) {
                    if (0 == substr_compare($match->name, $data['name'] . '_', 0, strlen($data['name'] . '_'), false)) {
                        $errors['name'] = get_string('duplicatemeetingname', 'adobeconnect');
                    }
                }
            }

            foreach($urlmatches as $matchkey => $match) {
                if (!array_key_exists($match->scoid, $grpmeetings)) {
                    if (0 == substr_compare($match->url, $url . '_', 0, strlen($url . '_'), false)) {
                        $errors['meeturl'] = get_string('duplicateurl', 'adobeconnect');
                    }
                }
            }

            // Validate start and end times
            if ($data['starttime'] == $data['endtime']) {
                $errors['starttime'] = get_string('samemeettime', 'adobeconnect');
                $errors['endtime'] = get_string('samemeettime', 'adobeconnect');
            } elseif ($data['endtime'] < $data['starttime']) {
                $errors['starttime'] = get_string('greaterstarttime', 'adobeconnect');
            }
        }

        aconnect_logout($aconnect);

        return $errors;
    }

    function get_templates() {
        $aconnect = aconnect_login();

        $templates_meetings = aconnect_get_templates_meetings($aconnect);
        aconnect_logout($aconnect);
        return $templates_meetings;
    }

}
