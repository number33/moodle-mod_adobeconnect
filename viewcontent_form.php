<?php // $Id$
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('locallib.php');

class viewcontent_form extends moodleform {
    function definition() {
        global $CFG;
        
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

//        $this->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 0, true, true, false));
        $mform->addElement('file', 'attachment', get_string('attachment', 'adobeconnect'));

        $this->add_action_buttons();
    }
}

?>