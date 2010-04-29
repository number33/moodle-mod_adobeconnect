<?php // $Id$

    require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
    require_once(dirname(__FILE__) . '/locallib.php');

    global $USER, $PAGE, $OUTPUT;;

    require_login(SITEID, false);

    if (!is_siteadmin($USER->id)) {
        redirect($CFG->wwwroot);
    }

    if (!$site = get_site()) {
        redirect($CFG->wwwroot);
    }

    $serverhost = required_param('serverURL', PARAM_NOTAGS);
    $port       = optional_param('port', 80, PARAM_INT);
    $username   = required_param('authUsername', PARAM_NOTAGS);
    $password   = required_param('authPassword', PARAM_NOTAGS);
    $httpheader = required_param('authHTTPheader', PARAM_NOTAGS);
    $emaillogin = required_param('authEmaillogin', PARAM_INT);

    $strtitle = get_string('connectiontesttitle', 'adobeconnect');


    $PAGE->set_pagetype('site-index');

    $site = get_site();
    $PAGE->set_course($site);
    $PAGE->set_url('/mod/adobeconnect/locallib.php');
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title('Adobe Connect Pro connection test');
    $PAGE->set_heading('Adobe Connect Pro connection test');
    echo $OUTPUT->header();

    $OUTPUT->box_start();

    print_string('conntestintro', 'adobeconnect');

    adobe_connection_test($serverhost, $port, $username, $password, $httpheader, $emaillogin);

    echo '<center>'. "\n";
    echo '<input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" />';
    echo '</center>';

    $OUTPUT->box_end();

    $OUTPUT->footer();