<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/viewcontent_form.php');


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

global $CFG;

$stradobeconnects = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect  = get_string('modulename', 'adobeconnect');
$struploadcontent  = get_string('uploadcontent', 'adobeconnect');

$navlinks = array();
$navlinks[] = array('name' => $stradobeconnects, 'link' => "index.php?id={$course->id}", 'type' => 'activity');
$navlinks[] = array('name' => format_string($adobeconnect->name), 'link' => "view.php?id={$cm->id}", 'type' => 'activityinstance');
$navlinks[] = array('name' => format_string($struploadcontent), 'link' => '', 'type' => 'title');

if (!empty($group)) {
    $navlinks[] = array('name' => format_string($group->name), 'link' => '', 'type' => 'title');
}

$navigation = build_navigation($navlinks);

print_header_simple(format_string($adobeconnect->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $stradobeconnect), navmenu($course, $cm));


$file = array();
$content = new viewcontent_form();


if ($data = $content->get_data()) {
    
    if (!$content->save_files($CFG->dataroot . '/adobeconnect')) {
        notice('Error uploading file');
    }
    
    $file = $content->_upload_manager['attachment'];
    
    
  print_object($newfilename);
  print_object('--data--');
  print_object($content);
}

$content->set_data(array('id' => $id, 'groupid' => $groupid));

$content->display();

print_footer($course);

?>