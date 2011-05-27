<?php // $Id: settings.php,v 1.1.2.8 2011/04/05 15:27:03 adelamarre Exp $
require_once($CFG->dirroot . '/mod/adobeconnect/locallib.php');
require_js($CFG->wwwroot . '/mod/adobeconnect/testserverconnection.js');

$settings->add(new admin_setting_configtext('adobeconnect_host', get_string('host', 'adobeconnect'),
                   get_string('host_desc', 'adobeconnect'), 'localhost/api/xml', PARAM_URL));

$settings->add(new admin_setting_configtext('adobeconnect_meethost', get_string('meetinghost', 'adobeconnect'),
                   get_string('meethost_desc', 'adobeconnect'), 'localhost', PARAM_URL));

$settings->add(new admin_setting_configtext('adobeconnect_port', get_string('port', 'adobeconnect'),
                   get_string('port_desc', 'adobeconnect'), '80', PARAM_INT));

$settings->add(new admin_setting_configtext('adobeconnect_admin_login', get_string('admin_login', 'adobeconnect'),
                   get_string('admin_login_desc', 'adobeconnect'), 'admin', PARAM_TEXT));

$settings->add(new admin_setting_configpasswordunmask('adobeconnect_admin_password', get_string('admin_password', 'adobeconnect'),
                   get_string('admin_password_desc', 'adobeconnect'), ''));

$settings->add(new admin_setting_configtext('adobeconnect_admin_httpauth', get_string('admin_httpauth', 'adobeconnect'),
                   get_string('admin_httpauth_desc', 'adobeconnect'), 'my-user-id', PARAM_TEXT));

$settings->add(new admin_setting_configcheckbox('adobeconnect_email_login', get_string('email_login', 'adobeconnect'),
                   get_string('email_login_desc', 'adobeconnect'), '0'));

$settings->add(new admin_setting_configcheckbox('adobeconnect_https', get_string('https', 'adobeconnect'),
                   get_string('https_desc', 'adobeconnect'), '0'));


//$settings->add(new admin_setting_configcheckbox('adobeconnect_record_force', get_string('record_force', 'adobeconnect'),
//                   get_string('record_force_desc', 'adobeconnect'), '0'));
//

$str = '<center><input type="button" onclick="return adobetestConnection(document.getElementById(\'adminsettings\'));" value="'.
       get_string('testconnection', 'adobeconnect') . '" /></center>';

$settings->add(new admin_setting_heading('adobeconnect_test', '', $str));

$str = '<center><img src="'.$CFG->wwwroot.'/mod/adobeconnect/pix/rl_logo.png" /></center><br /><p>Adobe Systems Inc. and Remote-Learner.net have partnered together to create the first publicly available
and officially sponsored, integration method between Moodle and Adobe Acrobat Connect Pro. This new
integration is designed to simplify the use of synchronous events within Moodle. It provides a
single-sign-on between the two systems with easy creation and management of Adobe Connect Pro
meetings.

<!--
</p>
<p>About Adobe Connect Pro
For more information about Adobe Connect Pro visit http:// (justin williams is getting a landing URL for here ping him)
</p>
-->

<p>
About Remote-Learner
Remote-Learner has been providing educational technologies services since 1982 to its business,
educational and governmental clients. Today, these services include support for best-of-breed
open source programs. Remote-Learner is an official Moodle partner, JasperSoft partner and
Alfresco partner. The company offers SaaS hosting services, IT support contracts, custom
programming, workforce development training, instructional design and strategic consulting
services for organizations planning online learning programs.</p>

<p>Visit http://remote-learner.net/adobeconnectpro for information on Enterprise support</p>';

$settings->add(new admin_setting_heading('adobeconnect_intro', '', $str));
?>