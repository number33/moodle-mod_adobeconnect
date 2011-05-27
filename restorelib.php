<?php // $Id: restorelib.php,v 1.1.2.1 2010/04/26 23:44:29 adelamarre Exp $

    //This function executes all the restore procedure about this mod
    function adobeconnect_restore_mods($mod,$restore) {
        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                               //Debug
            //print_object ($GLOBALS['traverse_array']);                                            //Debug
            //$GLOBALS['traverse_array']="";                                                        //Debug
            //Now, build the certificate record structure
            $adobeconnect = new stdClass();
            $adobeconnect->course = $restore->course_id;
            $adobeconnect->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $adobeconnect->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $adobeconnect->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
            $adobeconnect->templatescoid = backup_todb($info['MOD']['#']['TEMPLATESCOID']['0']['#']);
            $adobeconnect->meeturl = backup_todb($info['MOD']['#']['MEETURL']['0']['#']);
            $adobeconnect->starttime = backup_todb($info['MOD']['#']['STARTTIME']['0']['#']);
            $adobeconnect->endtime = backup_todb($info['MOD']['#']['ENDTIME']['0']['#']);
            $adobeconnect->meetingpublic = backup_todb($info['MOD']['#']['MEETINGPUBLIC']['0']['#']);
            $adobeconnect->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $adobeconnect->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the meeting
            $newid = insert_record ("adobeconnect",$adobeconnect);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string('modulename','adobeconnect')." \"".format_string(stripslashes($adobeconnect->name),true)."\"</li>";
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                //We have to restore the lesson pages which are held in their logical order...
                $status = adobeconnect_pages_restore_mods($newid,$info,$restore);
            }
            else {
                $status = false;
            }

        } else {
            $status = false;
        }

        return $status;
    }

    function adobeconnect_pages_restore_mods($adobeconnectid,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the lesson_elements array
        $meetgroups = $info['MOD']['#']['MEETINGGROUPS']['0']['#']['MEETINGGROUP'];

        //Iterate over lesson pages (they are held in their logical order)
        $prevpageid = 0;
        for($i = 0; $i < sizeof($meetgroups); $i++) {
            $meetgroup_info = $meetgroups[$i];
            //traverse_xmlize($ele_info);                                                          //Debug
            //print_object ($GLOBALS['traverse_array']);                                           //Debug
            //$GLOBALS['traverse_array']="";                                                       //Debug

            //Now, build the lesson_pages record structure
            $oldid = backup_todb($meetgroup_info['#']['ID']['0']['#']);

            $meeting = new stdClass();
            $meeting->instanceid = $adobeconnectid;
            $meeting->meetingscoid = backup_todb($meetgroup_info['#']['MEETINGSCOID']['0']['#']);
            $meeting->groupid = backup_todb($meetgroup_info['#']['GROUPID']['0']['#']);

            //We have to recode the groupid field
            $group = restore_group_getid($restore, $meeting->groupid);
            if ($group) {
                $meeting->groupid = $group->new_id;
            }

            //The structure is equal to the db, so insert the certificate_issue
            $newid = insert_record ("adobeconnect_meeting_groups",$meeting);

            //Do some output
            if (($i+1) % 10 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 200 == 0) {
                        echo "<br/>";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids (restore logs will use it!!)
                backup_putid($restore->backup_unique_code,"adobeconnect_meeting_groups", $oldid, $newid);

            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function adobeconnect_restore_logs($restore,$log) {
        $status = false;

        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br/>";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }

?>