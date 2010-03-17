<?php // $Id$

/**
 * This page lists all the instances of adobeconnect in a particular course
 *
 * @author  Your Name Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @version $Id$
 * @package mod/adobeconnect
 */

/// Replace adobeconnect with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);

global $USER;

add_to_log($course->id, 'adobeconnect', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsadobeconnect

$stradobeconnects = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect  = get_string('modulename', 'adobeconnect');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $stradobeconnects, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($stradobeconnects, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $adobeconnects = get_all_instances_in_course('adobeconnect', $course)) {
    notice('There are no instances of adobeconnect', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

$groups = groups_get_user_groups($course->id, $USER->id);
$groupid = '';
$groupmode = '';

if (array_key_exists(0, $groups)) {
    $groupid = '&amp;group='.current($groups[0]);
}

foreach ($adobeconnects as $adobeconnect) {
    $group = $groupid;

    if (0 == $adobeconnect->groupmode) {
        $group = '&amp;group=0';
    }

    if (!$adobeconnect->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$adobeconnect->coursemodule.$group.'">'.format_string($adobeconnect->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$adobeconnect->coursemodule.$group.'">'.format_string($adobeconnect->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($adobeconnect->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

print_heading($stradobeconnects);
print_table($table);

/// Finish the page

print_footer($course);

?>
