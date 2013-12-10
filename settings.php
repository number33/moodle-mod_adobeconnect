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

defined('MOODLE_INTERNAL') || die;

global $PAGE;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot . '/mod/adobeconnect/locallib.php');
    $PAGE->requires->js_init_call('M.mod_adobeconnect.init');

    $settings->add(new admin_setting_configtext('adobeconnect_host', get_string('host', 'adobeconnect'),
            get_string('host_desc', 'adobeconnect'), 'localhost/api/xml', PARAM_URL));

    $settings->add(new admin_setting_configtext('adobeconnect_meethost', get_string('meetinghost', 'adobeconnect'),
            get_string('meethost_desc', 'adobeconnect'), 'localhost', PARAM_URL));

    $settings->add(new admin_setting_configtext('adobeconnect_port', get_string('port', 'adobeconnect'),
            get_string('port_desc', 'adobeconnect'), '80', PARAM_INT));

    $settings->add(new admin_setting_configtext('adobeconnect_admin_login', get_string('admin_login', 'adobeconnect'),
            get_string('admin_login_desc', 'adobeconnect'), 'admin', PARAM_TEXT));

    $settings->add(new admin_setting_configpasswordunmask('adobeconnect_admin_password',
            get_string('admin_password', 'adobeconnect'), get_string('admin_password_desc', 'adobeconnect'), ''));

    $settings->add(new admin_setting_configtext('adobeconnect_admin_httpauth', get_string('admin_httpauth', 'adobeconnect'),
            get_string('admin_httpauth_desc', 'adobeconnect'), 'my-user-id', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('adobeconnect_email_login', get_string('email_login', 'adobeconnect'),
            get_string('email_login_desc', 'adobeconnect'), '0'));

    $settings->add(new admin_setting_configcheckbox('adobeconnect_https', get_string('https', 'adobeconnect'),
            get_string('https_desc', 'adobeconnect'), '0'));
    
    $settings->add(new admin_setting_configtext('adobeconnect_admin_subfolder', get_string('admin_subfolder', 'adobeconnect'),
            get_string('admin_subfolder_desc', 'adobeconnect'), '', PARAM_TEXT));

    $url = $CFG->wwwroot . '/mod/adobeconnect/conntest.php';
    $url = htmlentities($url);
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<center><input type="button" onclick="window.open(\''.$url.'\', \'\', \''.$options.'\');" value="'.
           get_string('testconnection', 'adobeconnect') . '" /></center>';

    $settings->add(new admin_setting_heading('adobeconnect_test', '', $str));

    $param = new stdClass();
    $param->image = $CFG->wwwroot.'/mod/adobeconnect/pix/rl_logo.png';
    $param->url = 'http://remote-learner.net/adobeconnectpro';

    $settings->add(new admin_setting_heading('adobeconnect_intro', '', get_string('settingblurb', 'adobeconnect', $param)));
}