<?php // $Id$

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('locallib.php');

class parcipants_view_form extends moodleform {
    function definition() {
        global $CFG;
        
        $available = array();
        $presenter = array();
        $participant = array();

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $ausers = $this->_customdata['ausers'];
        $susers = $this->_customdata['susers'];
        $groupname = $this->_customdata['groupname'];
        
        foreach($ausers as $key => $auser) {
            $available[$auser->id] = $auser->lastname . ', ' . $auser->firstname;
        }
        
        foreach ($susers as $key => $suser) {
            if (ADOBE_PARTICIPANT == $suser->roleid) {
                $participant[$suser->userid] = $suser->lastname . ', ' . $suser->firstname;
            } elseif (ADOBE_PRESENTER == $suser->roleid) {
                $presenter[$suser->userid] = $suser->lastname . ', ' . $suser->firstname;
            }
        }

        $mform->addElement('static', 'title', '',
                          get_string('editingfor', 'adobeconnect', $groupname));


        $empty = array();
        $objs = array();

//        $objs[0] =& $mform->createElement('html', 'availablelist', '',
//                          get_string('availablelist', 'adobeconnect'));
        $objs[0] =& $mform->createElement('select', 'ausers', get_string('availablelist', 'adobeconnect'), $available, '');
        $objs[0]->setMultiple(true);
 
//        $objs[2] =& $mform->createElement('static', 'availablelist', '',
//                          get_string('participantslabel', 'adobeconnect'));
//        $objs[1] =& $mform->createElement('html', '<label>hello</label>');


        $objs[1] =& $mform->createElement('select', 'participants', get_string('participantslabel', 'adobeconnect'), $participant, '');
        $objs[1]->setMultiple(true);

//        $objs[4] =& $mform->createElement('static', 'availablelist', '',
//                          get_string('participantslabel', 'adobeconnect'));

        $objs[2] =& $mform->createElement('select', 'presenter', get_string('presenterlabel', 'adobeconnect'), $presenter, '');
        $objs[2]->setMultiple(true);

//        $objs[0] =& $mform->addElement('select', 'ausers', get_string('availablelist', 'adobeconnect'), $available, 'size="15"');
//        $objs[0]->setMultiple(true);
//        $objs[1] =& $mform->addElement('select', 'participants', get_string('participantslabel', 'adobeconnect'), $participant, 'size="15"');
//        $objs[1]->setMultiple(true);
//        $objs[3] =& $mform->addElement('select', 'presenter', get_string('presenterlabel', 'adobeconnect'), $presenter, 'size="15"');
//        $objs[3]->setMultiple(true);

        $prebtnobjs[0] = &$mform->createElement('submit', 'addpresenter', get_string('addpresenter', 'adobeconnect'));
        $prebtnobjs[1] = &$mform->createElement('submit', 'removepresenter', get_string('removepresenter', 'adobeconnect'));

        $parbtnobjs[0] = &$mform->createElement('submit', 'addparticipant', get_string('addparticipant', 'adobeconnect'));
        $parbtnobjs[1] = &$mform->createElement('submit', 'removeparticipant', get_string('removeparticipant', 'adobeconnect'));

        
        $actionbtnobjs[0] = &$mform->createElement('submit', 'savechanges', get_string('savechanges', 'adobeconnect'));
        $actionbtnobjs[1] = &$mform->createElement('submit', 'cancelchanges', get_string('cancelchanges', 'adobeconnect'));
        

        $grp =& $mform->addElement('group', 'usersgrp', get_string('participantsgrp', 'adobeconnect'), $objs, ' ', false);

//        $grp->setHelpButton(array('lists', get_string('users'), 'bulkusers'));

        $grp =& $mform->addElement('group', 'prebtngroup', get_string('presenterbtngrp', 'adobeconnect'), $prebtnobjs, ' ', false);
        $grp =& $mform->addElement('group', 'parbtngroup', get_string('participantbtngrp', 'adobeconnect'), $parbtnobjs, ' ', false);
        $grp =& $mform->addElement('group', 'actionbtngroup', get_string('actinbtngrp', 'adobeconnect'), $actionbtnobjs  , ' ', false);
    }
}
?>