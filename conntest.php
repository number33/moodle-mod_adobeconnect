<?php // $Id$

    require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
    require_once(dirname(__FILE__) . '/locallib.php');

    require_login(SITEID, false);

    if (!isadmin()) {
        redirect($CFG->wwwroot);
    }

    if (!$site = get_site()) {
        redirect($CFG->wwwroot);
    }

    $serverhost = required_param('serverURL', PARAM_NOTAGS);
    $port       = required_param('port', PARAM_INT);
    $username   = required_param('authUsername', PARAM_NOTAGS);
    $password   = required_param('authPassword', PARAM_NOTAGS);
    $httpheader = required_param('authHTTPheader', PARAM_NOTAGS);

    $strtitle = get_string('connectiontesttitle', 'adobeconnect');


    print_header_simple(format_string($strtitle));
    print_simple_box_start('center', '100%');

    print_string('conntestintro', 'adobeconnect');

    adobe_connection_test($serverhost, $port, $username, $password);

    echo '<center>'. "\n";
    echo '<input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" />';
    echo '</center>';

    print_simple_box_end();
    print_footer('none');

?>