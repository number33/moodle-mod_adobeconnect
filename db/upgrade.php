<?php  //$Id$

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

function xmldb_adobeconnect_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

 if ($result && $oldversion < 2011050301) { //New version in version.php
     $table = new XMLDBTable('adobeconnect');

     $field = new XMLDBField('meeturl');
     $field->setAttributes(XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null, null, '0', 'templatescoid');


     $result  = $result && change_field_precision($table, $field);
 }
    return $result;
}

?>
