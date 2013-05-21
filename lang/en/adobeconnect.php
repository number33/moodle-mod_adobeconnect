<?php

$string['actinbtngrp'] = '';
$string['addparticipant'] = 'Add';
$string['addpresenter']  = 'Add';
$string['admin_httpauth'] = 'HTTP Authentication Header';
$string['admin_httpauth_desc'] = 'The HTTP_AUTH_HEADER value used in your custom.ini';
$string['admin_login'] = 'Admin Login';
$string['admin_login_desc'] = 'Login for main admin account';
$string['admin_password'] = 'Admin Password';
$string['admin_password_desc'] = 'Password for main admin account';
$string['adobeconnect'] = 'Adobe Connect';
$string['adobeconnectfieldset'] = 'Adobe Connect Settings';
$string['adobeconnecthost'] = 'Adobe Connect Host';
$string['adobeconnecthostdescription'] = 'The host can give other users privileges, start and stop a meeting in addition to what a persenter can do';
$string['adobeconnectintro'] = 'Intro';
$string['adobeconnectname'] = 'Meeting title';
$string['adobeconnectparticipant'] = 'Adobe Connect Participant';
$string['adobeconnectparticipantdescription'] = 'Can view, but cannot modify any of the meeting settings';
$string['adobeconnectpresenter'] = 'Adobe Connect Presenter';
$string['adobeconnectpresenterdescription'] = 'The presenter of a meeting and can present content, share a screen, send text messages, moderate questions, create text notes, broadcast audio and video, and push content from web links';
$string['allusers'] = 'all users';
$string['assignadoberole'] = 'Assigning Adobe Roles';
$string['assignadoberoles'] = 'Assigning $a->role role for $a->meetname ($a->groupname)';
$string['assignroles'] = 'Assign roles';
$string['availablelist'] = 'Available';
$string['backtomeeting'] = 'Back to $a meeting';
$string['cancelchanges'] = 'Cancel';
$string['duplicatemeetingname'] = 'A duplicate meeting name was found on the server';
$string['duplicateurl'] = 'A duplicate meeting URL was found on the server';
$string['editingfor'] = 'Editing for: $a';
$string['email_login'] = 'Email address login';
$string['email_login_desc'] = 'Check this option only if your Connect Pro server login is set to use email address. Note that toggling '.
                              'this option on/off during regular usage of this activity module can potentially create duplicaed users on the Connect Pro server';
$string['endtime'] = 'End time';
$string['existingusers'] = '$a existing users';
$string['groupswitch'] = 'Filter by group';
$string['host'] = 'Host';
$string['host_desc'] = 'Where REST calls get sent to';
$string['joinmeeting'] = 'Join Meeting';
$string['meethost_desc'] = 'Domain where the Adobe server is installed';
$string['meetinghost'] = 'Meeting domain';
$string['meetingend'] = 'Meeting end time';
$string['meetinginfo'] = 'Meeting Info';
$string['meetingintro'] = 'Meeting Summary';
$string['meetinggroup'] = 'Meeting group';
$string['meetingname'] = 'Meeting Name';
$string['meetingstart'] = 'Meeting start time';
$string['meetingtype'] = 'Meeting type';
$string['modulename'] = 'Adobe Connect';
$string['modulenameplural'] = 'Adobe Connect';
$string['meettemplates'] = 'Meeting Templates';
$string['meeturl'] = 'Meeting URL';
$string['participantbtngrp'] = 'Participant Actions';
$string['participantsgrp'] = 'Meeting Users';
$string['particpantslabel'] = 'Participants';
$string['potentialusers'] = '$a potential users';
$string['port'] = 'Port';
$string['port_desc'] = 'Port used to connect to Adobe Connect';
$string['presenterbtngrp'] = 'Presenter Actions';
$string['presenterlabel'] = 'Presenter';
$string['recordinghdr'] = 'Meeting Recordings';
$string['record_force'] = 'Force Meeting Recordings';
$string['record_force_desc'] = 'Force all Adobe Connect meetings to be recorded.  This is a site wide effect and the Adobe Connect server must be restarted';
$string['removeparticipant'] = 'Remove';
$string['removepresenter'] = 'Remove';
$string['roletoassign'] = 'Role to assign';
$string['samemeettime'] = 'Invalid Meeting time';
$string['savechanges'] = 'Save';
$string['selectparticipants'] = 'Assign roles';
$string['starttime'] = 'Start time';
$string['usergrouprequired'] = 'This Meeting requires users to be in a group in order to join';
$string['testconnection'] = 'Test Connection';
$string['connectiontesttitle'] = 'Connection test window';
$string['conntestintro'] = '<p>A series of tests have been run in order to determine whether the Adobe Connect Pro server has been properly setup for this integration to work'.
' and to also determine whether the user credentials provided in the activity global settings has the correct permissions to perform the neccessary tasks required by the'.
' activity module.  If any of the tests below have failed, this activity module will not function properly.</p><p> For further assistance and documentation in how to set up your'.
' Adobe Connect Pro server please consult the MoodleDocs help page for this activity module <a href="{$a->url}">Help page</a></p>';
$string['greaterstarttime'] = 'The start time cannot be greater than the end time';
$string['invalidadobemeeturl'] = 'Invalid entry for this field.  Click the help bubble for valid entries';

$string['adobeconnect:meetingpresenter'] = 'Meeting Presenter';
$string['adobeconnect:meetingparticipant'] = 'Meeting Particpant';
$string['adobeconnect:meetinghost'] = 'Meeting Host';
$string['public'] = 'Public';
$string['private'] = 'Private';

// Error codes
$string['emptyxml'] = 'Unable to connect to the Adobe Connect Pro server at this time.  Please inform your Moodle administrator.';
$string['adminemptyxml'] = 'Unable to connect to the Adobe Connect Pro server at this time.  Click continue to proceed to the activity settings page and test the connection';
$string['notsetupproperty'] = 'The activity module is not properly setup.  Please contact your Moodle administrator';
$string['adminnotsetupproperty'] = 'The activity module is not properly setup.  Click continue to proceed to the activity settings page and test the connection';
$string['notparticipant'] = 'You are not a participant for this meeting';
$string['unableretrdetails'] = 'Unable to retrieve meeting details';
$string['usernotenrolled'] = 'Only users enrolled and have a role in this course can join this meeting';
$string['nopresenterrole'] = 'error: error finding adobeconnectpresenter role';
$string['nomeeting'] = 'No meeting exists on the server';
$string['noinstances'] = 'There are no instances of adobeconnect';
$string['error1'] = 'You must be a site administrator to access this page';
$string['error2'] = 'The property \'{$a}\' is empty, please input a value and save the settings';
$string['errormeeting'] = 'Error retrieving recording';
$string['settingblurb'] = '<center><img src="{$a->image}" /></center><br />
    <p>Adobe Systems Inc. and Remote-Learner.net have partnered together to create the first publicly available
    and officially sponsored, integration method between Moodle and Adobe Acrobat Connect Pro. This new
    integration is designed to simplify the use of synchronous events within Moodle. It provides a
    single-sign-on between the two systems with easy creation and management of Adobe Connect Pro
    meetings.</p><br />
    <p><center>About Remote-Learner</center>
    Remote-Learner has been providing educational technologies services since 1982 to its business,
    educational and governmental clients. Today, these services include support for best-of-breed
    open source programs. Remote-Learner is an official Moodle partner, JasperSoft partner and
    Alfresco partner. The company offers SaaS hosting services, IT support contracts, custom
    programming, workforce development training, instructional design and strategic consulting
    services for organizations planning online learning programs.</p><br />
    <p>Visit {$a->url} for information on Enterprise support</p>';
$string['meeturl_help'] = '<p>You can customize the URL that is used to connect to the Adobe connect meeting.  The Adobe Server domain will always remain the same.
  However the last part of the URL can be customized.
</p>
<p>For example if the Adobe Connect server domain was located at <b>http://adobe.connect.server/</b>
  when customizing the URL to <b>mymeeting</b>, the URL to connect to the meeting would be <b>http://adobe.connect.server/mymeeting</b>.  Leave out the trailing forward slash
</p>
<p>Valid URL entries consists of the name with
<ul>
<li>mymeeting</li>
<li>/mymeeting</li>
</ul>

Invalid URL entries consist of more than one forward slash:
<ul>
<li>mymeeting/mymeeting</li>
<li>mymeeting/mymeeting/</li>
<li>mymeeting/mymeeting//anothermeeting</li>
<li>mymeeting/</li>
</ul>

</p>
<p>Once the meeting has been saved, you will no longer be able to edit/update this field as the field will be disabled.
If updating the activity settings and if <b>Groups Mode</b> is set to no group then you will see part of the URL in the text field.
Otherwise the text field will remain blank as each course group will have their own meeting URL.
</p>';
$string['meetingtype_help'] = '<p>A public meeting type is one where anyone who has the URL for the meeting can enter the room.</p>
<p>A private meeting type is one where only registered users and participants can enter. The login page does not allow
guests to log in.  With private meetings the meeting does not actually start until the meeting Presenter or Host joins the meeting.</p>

<p>
If you are creating a private meeting it is always good practice to assign at
least 1 host or presenter who will be present in the meeting; because users with
the participant role will be unable to join the meeting unless a user with the
host or presenter roles has already joined th meeting.
</p>

<p>
If the meeting has support for separate groups at least 1 user in each group, who is
to be present in the meeting, should have either the host or presenter role.
</p>';
$string['meettemplates_help'] = '<p>A meeting room template creates meeting with a custom layout for the meeting room.</p>';
$string['pluginadministration'] = 'Adobe Connect Administration';
$string['pluginname'] = 'Adobe Connect';
$string['modulename'] = 'Adobe Connect';
$string['recordinghdr'] = 'Recordings';
$string['https'] = 'HTTPS Connection';
$string['https_desc'] = 'Connect to the Connect server via HTTPS';
$string['invalidurl'] = 'The URL needs to start with a letter (a-z)';
$string['longurl'] = 'That meeting URL is too long. Try shortening it';
$string['errorrecording'] = 'Unable to find recording session';
$string['meetinfo'] = 'More Meeting Detail';
$string['meetinfotxt'] = 'See server meeting details';
$string['missingexpectedgroups'] = 'There are no groups available.';
