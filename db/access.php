<?php // $Id$
$mod_adobeconnect_capabilities = array(
    'mod/adobeconnect:meetingpresenter' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        )
    ),

    'mod/adobeconnect:meetingparticipant' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        )
    ),

    'mod/adobeconnect:meetinghost' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        )
    ),

);
?>