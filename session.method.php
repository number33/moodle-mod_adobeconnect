<?php
require('connect_class.php');
require('lib.php');

$login = 'akin.iver@gmail.com';
$password = 'FeXHNBL12';
$serverurl = 'http://localhost/api/xml';

$aconnect = new connect_class($serverurl, $login, $password);

//header("Content-type: text/xml");
//echo $aconnect->send_request_custom1();
//die();

//DEBBUG
//echo $aconnect->get_serverurl();
//echo $aconnect->get_username();
//echo $aconnect->get_password();

//$aconnect->request_common_info();
$params = array(
  'action' => 'common-info'
);
$aconnect->create_request($params);
$aconnect->read_cookie_xml($aconnect->_xmlresponse);

// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;
//echo $aconnect->get_cookie();
//die();

//$aconnect->request_login();
$params = array(
  'action' => 'login',
  'login' => $login,
  'password' => $password,
);
$aconnect->create_request($params);

//die();
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse; die();
//echo $aconnect->get_cookie();
//var_dump($aconnect->read_status_xml());
//die();


//$aconnect->request_principal_list();

$params = array(
  'action' => 'principal-list'
);
$aconnect->create_request($params);

// CREATE NEW USER
$params = array(
  'action' => 'principal-update',
  'first-name' => 'test2_first',
  'last-name' => 'test2_last',
  'login' => 'test2',
  'password' => 'test2',
  'type' => 'user',
  'has-children' => 0
);
//$aconnect->create_request($params);


//FIND A USER
$params = array(
  'action' => 'principal-list',
  'filter-login' => 'test2',
);
//$aconnect->create_request($params);
//print_r($aconnect->response_to_object());

// UPDATE USER

// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;


// SCO shortcuts
$params = array(
  'action' => 'sco-shortcuts',
);
$aconnect->create_request($params);
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;

// SCO get specific SCO (meetings)
$params = array(
  'action' => 'sco-expanded-contents',
  'sco-id' => '11003',
  );
  
//$aconnect->create_request($params);
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;die();
// TO DOWNLOAD A FILE PARSE THE LINE ABOVE AND GET THE URL-PATH.
//CONSTRUCT A REQUEST LIKE SO: http://localhost:700/p89988024/ send a heaader to download the file


//FIND A MEETING IN A MEETING FOLDER
// can also use sco-expanded-contents but sco-contents is better performance wise (if you know the sco-id for the meeting folder
// for sco-id must use the folder (sco-id) that contains the meeting you are looking for.
$params = array(
  'action' => 'sco-contents',
  'sco-id' => '11003',
  'filter-type' => 'meeting',
  'filter-name' => 'TEST1',
);
//$aconnect->create_request($params);
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;
  
  
// FIND ALL MEETING ON A SERVER
$params = array(
  'action' => 'report-bulk-objects',
  'filter-type' => 'meeting',
//  'filter-name' => 'TEST1', //will also give you the specific meeting you are looking for
//'filter-expired'=> 'false', only meetings that have no expired (might be able to use this parameter for report-bulk-objects
);
//$aconnect->create_request($params);
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;
  
// ANOTHER SCO TEST USING SCO-INFO AND MEETING ID
$params = array(
  'action' => 'sco-info',
  'sco-id' => '11209', // sco-id OF MEETING TEST1
);
//$aconnect->create_request($params);
// DEBUG
//header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
//echo $aconnect->_xmlresponse;


// GET A LIST OF GROUPS
$params = array(
  'action' => 'principal-list',
  'filter-type' => 'group',
);

$aconnect->create_request($params);
// DEBUG
header("Content-type: text/xml");
//echo $aconnect->_xmlrequest;
echo $aconnect->_xmlresponse;

?>