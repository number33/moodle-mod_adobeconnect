<?php  //$Id: upgrade.php,v 1.8 2011/05/10 14:31:41 adelamarre Exp $

// This file keeps track of upgrades to
// the adobeconnect module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_adobeconnect_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

//===== 1.9.0 upgrade line ======//
    if ($oldversion < 2010120800) {

    /// Define field introformat to be added to survey
        $table = new xmldb_table('adobeconnect');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

    /// Conditionally launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // conditionally migrate to html format in intro
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('adobeconnect', array('introformat'=>FORMAT_MOODLE), '', 'id,intro,introformat');
            foreach ($rs as $s) {
                $s->intro       = text_to_html($s->intro, false, false, true);
                $s->introformat = FORMAT_HTML;
                $DB->update_record('adobeconnect', $s);
                upgrade_set_timeout();
            }
            $rs->close();
        }

    /// adobeconnect savepoint reached
        upgrade_mod_savepoint(true, 2010120800, 'adobeconnect');
    }

    if ($oldversion < 2011041400) {

        // Changing precision of field meeturl on table adobeconnect to (60)
        $table = new xmldb_table('adobeconnect');
        $field = new xmldb_field('meeturl', XMLDB_TYPE_CHAR, '60', null, null, null, null, 'templatescoid');

        // Launch change of precision for field meeturl
        $dbman->change_field_precision($table, $field);

        // adobeconnect savepoint reached
        upgrade_mod_savepoint(true, 2011041400, 'adobeconnect');
    }
    
    if ($oldversion < 2012012250) {
        $table = new xmldb_table('adobeconnect');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', true, true, null, 0, 'introformat');
    
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    
        // adobeconnect savepoint reached
        upgrade_mod_savepoint(true, 2012012500, 'adobeconnect');

    }

    return true;

}