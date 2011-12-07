<?php //$Id: mod_form.php,v 1.1.2.6 2011/05/03 22:42:07 adelamarre Exp $

/**
 * This file defines the main adobeconnect configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             adobeconnect type (index.php) and in the header
 *             of the adobeconnect main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

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
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $mform->addElement('htmleditor', 'intro', get_string('adobeconnectintro', 'adobeconnect'));
        $mform->setType('intro', PARAM_RAW);
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

    /// Adding "introformat" field
        $mform->addElement('format', 'introformat', get_string('format'));

//-------------------------------------------------------------------------------
    /// Adding the rest of adobeconnect settings, spreeading all them into this fieldset
    /// or adding more fieldsets ('header' elements) if needed for better logic

        $mform->addElement('header', 'adobeconnectfieldset', get_string('adobeconnectfieldset', 'adobeconnect'));

        // Meeting URL
        $attributes=array('size'=>'20');
        $mform->addElement('text', 'meeturl', get_string('meeturl', 'adobeconnect'), $attributes);
        $mform->setType('meeturl', PARAM_PATH);
        $mform->setHelpButton('meeturl', array('meeturl', get_string('meeturl', 'adobeconnect'), 'adobeconnect'));
        $mform->disabledIf('meeturl', 'tempenable', 'eq', 0);

        // Public or private meeting
        $meetingpublic = array(1 => get_string('public', 'adobeconnect'), 0 => get_string('private', 'adobeconnect'));
        $mform->addElement('select', 'meetingpublic', get_string('meetingtype', 'adobeconnect'), $meetingpublic);
        $mform->setHelpButton('meetingpublic', array('meetingtype', get_string('meetingtype', 'adobeconnect'), 'adobeconnect'));

        // Meeting Template
        $templates = array();
        $templates = $this->get_templates();
        ksort($templates);
        $mform->addElement('select', 'templatescoid', get_string('meettemplates', 'adobeconnect'), $templates);
        $mform->setHelpButton('templatescoid', array('templatescoid', get_string('meettemplates', 'adobeconnect'), 'adobeconnect'));
        $mform->disabledIf('templatescoid', 'tempenable', 'eq', 0);


        $mform->addElement('hidden', 'tempenable');
        $mform->setType('type', PARAM_INT);

        // Start and end date selectors
        $time = time();
        $starttime = usertime($time);

        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'adobeconnect'));
        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'adobeconnect'));
        $mform->setDefault('endtime', strtotime('+2 hours', $starttime));


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
        global $CFG;

        if (array_key_exists('update', $default_values)) {
            $sql = "SELECT id FROM {$CFG->prefix}adobeconnect_meeting_groups WHERE ".
                   "instanceid = " . $default_values['id'];
            if (record_exists_sql($sql)) {
                $default_values['tempenable'] = 0;
            }
        }
    }

    function validation($data, $files) {
        global $CFG, $USER, $COURSE;

        $errors = parent::validation($data, $files);

        $aconnect = aconnect_login();


        // Search for a Meeting with the same starting name.  It will cause a duplicate
        // meeting name (and error) when the user begins to add participants to the meeting
        $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
        $filter = array('filter-like-name' => $data['name']);
        $namematches = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

        if (empty($namematches)) {
            $namematches = array();
        }

        // Now search for existing meeting room URLs
        $url = $data['meeturl'] = adobeconnect_clean_meet_url($data['meeturl']);

        // Check to see if there are any trailing slashes or additional parts to the url
        // ex. mymeeting/mysecondmeeting/  Only the 'mymeeting' part is valid
        if ((0 != substr_count($url, '/')) and (false !== strpos($url, '/', 1))) {
            $errors['meeturl'] = get_string('invalidadobemeeturl', 'adobeconnect');
        }

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

        // Check URL for correct length and format
        if (strlen($data['meeturl']) > 60) {
            $errors['meeturl'] = get_string('longurl', 'adobeconnect');
        } elseif (empty($data['meeturl'])) {
            // Do nothing
        } elseif (!preg_match('/^[a-z][a-z\-]*/i', $data['meeturl'])) {
            $errors['meeturl'] = get_string('invalidurl', 'adobeconnect');
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
            if (record_exists('adobeconnect', 'name', $data['name'])) {
                $errors['name'] = get_string('duplicatemeetingname', 'adobeconnect');
                return $errors;
            }

            // Check that the course has groups if group mode is on
            if (0 != $data['groupmode']) { // Allow for multiple groups
                // get all groups for the course
                $crsgroups = groups_get_all_groups($COURSE->id);
                if (empty($crsgroups)) {
                    $errors['groupmode'] = get_string('invalidgroupmode', 'adobeconnect');
                }
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
            $sql = "SELECT meetingscoid, groupid FROM {$CFG->prefix}adobeconnect_meeting_groups ".
                   " WHERE instanceid = ". $data['instance'];
            $grpmeetings = get_records_sql($sql);

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

?>