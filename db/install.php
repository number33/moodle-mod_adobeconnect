<?php // $Id$
/**
 * This file replaces:
 *   * STATEMENTS section in db/install.xml
 *   * lib.php/modulename_install() post installation hook
 *   * partially defaults.php
 *
 * @package   adobeconnect
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o
 */

function xmldb_adobeconnect_install() {
    global $DB;

/// Install logging support
    update_log_display_entry('adobeconnect', 'add', 'adobeconnect', 'name');
    update_log_display_entry('adobeconnect', 'update', 'adobeconnect', 'name');
    update_log_display_entry('adobeconnect', 'view', 'adobeconnect', 'name');

}
?>