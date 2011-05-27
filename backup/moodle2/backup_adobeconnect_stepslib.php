<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_adobeconnect_activity_task
 */

/**
 * Define the complete adobeconnect structure for backup, with file and id annotations
 */
class backup_adobeconnect_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        //$userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $adobeconnect = new backup_nested_element('adobeconnect', array('id'), array(
                'name', 'intro', 'introformat', 'templatescoid',
                'meeturl', 'starttime', 'endtime', 'meetingpublic',
                'timecreated', 'timemodified'));

        $meetinggroups = new backup_nested_element('meeting_groups');

        $meetinggroup = new backup_nested_element('meeting_group', array('id'), array(
            'meetingscoid', 'groupid'));

        // Build the tree
        $adobeconnect->add_child($meetinggroups);
        $meetinggroups->add_child($meetinggroup);

        // Define sources
        $adobeconnect->set_source_table('adobeconnect', array('id' => backup::VAR_ACTIVITYID));

        $meetinggroup->set_source_sql('
            SELECT *
              FROM {adobeconnect_meeting_groups}
             WHERE instanceid = ?',
            array(backup::VAR_PARENTID));

        $meetinggroup->annotate_ids('group', 'groupid');

        // Define file annotations
        $adobeconnect->annotate_files('mod_adobeconnect', 'intro', null); // This file area hasn't itemid

        // Return the root element (survey), wrapped into standard activity structure
        return $this->prepare_activity_structure($adobeconnect);
    }
}