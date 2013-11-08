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

// Not sure if this page is needed anymore.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

// Course.
$id = required_param('id', PARAM_INT);

global $USER, $DB;

$params = array('id' => $id);
if (! $course = $DB->get_record('course', $params)) {
    error('Course ID is incorrect');
}

$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'adobeconnect', 'view all', "index.php?id=$course->id", '');


// Get all required strings.
$stradobeconnects   = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect    = get_string('modulename', 'adobeconnect');
$strsectionname     = get_string('sectionname', 'format_'.$course->format);
$strname            = get_string('name');
$strintro           = get_string('moduleintro');


$PAGE->set_url('/mod/adobeconnect/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$stradobeconnects);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($stradobeconnects);
echo $OUTPUT->header();

if (! $adobeconnects = get_all_instances_in_course('adobeconnect', $course)) {
    notice(get_string('noinstances', 'adobeconnect'), "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances (your module will probably extend this).

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

foreach ($adobeconnects as $adobeconnect) {
    $linkparams = array('id' => $adobeconnect->coursemodule);
    $linkoptions = array();

    $modviewurl = new moodle_url('/mod/adobeconnect/view.php', $linkparams);

    if (!$adobeconnect->visible) {
        $linkoptions['class'] = 'dimmed';
    }

    $link = html_writer::link($modviewurl, format_string($adobeconnect->name), $linkoptions);
    $intro = $adobeconnect->intro;

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($adobeconnect->section, $link, $intro);
    } else {
        $table->data[] = array ($link, $intro);
    }
}

echo html_writer::table($table);

echo $OUTPUT->footer();
