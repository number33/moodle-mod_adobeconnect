<?php // $Id$

$settings->add(new admin_setting_configtext('adobeconnect_host', get_string('host', 'adobeconnect'),
                   get_string('host_desc', 'adobeconnect'), 'localhost/api/xml', PARAM_URL));

$settings->add(new admin_setting_configtext('adobeconnect_meethost', get_string('meetinghost', 'adobeconnect'),
                   get_string('meethost_desc', 'adobeconnect'), 'localhost', PARAM_URL));

$settings->add(new admin_setting_configtext('adobeconnect_port', get_string('port', 'adobeconnect'),
                   get_string('port_desc', 'adobeconnect'), '700', PARAM_INT));

$settings->add(new admin_setting_configtext('adobeconnect_admin_login', get_string('admin_login', 'adobeconnect'),
                   get_string('admin_login_desc', 'adobeconnect'), 'admin', PARAM_TEXT));

$settings->add(new admin_setting_configpasswordunmask('adobeconnect_admin_password', get_string('admin_password', 'adobeconnect'),
                   get_string('admin_password_desc', 'adobeconnect'), ''));

//$settings->add(new admin_setting_configcheckbox('adobeconnect_record_force', get_string('record_force', 'adobeconnect'),
//                   get_string('record_force_desc', 'adobeconnect'), '0'));
//
?>