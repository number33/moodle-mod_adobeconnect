<?php // $Id: backuplib.php,v 1.1.2.1 2010/04/26 23:44:29 adelamarre Exp $
    function adobeconnect_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over certificate table

        if ($meetings = get_records('adobeconnect','course',$preferences->backup_course,"id")) {
            foreach ($meetings as $meeting) {
                $backup_mod_selected = backup_mod_selected($preferences, 'adobeconnect', $meeting->id);
                if ($backup_mod_selected) {
                    $status = adobeconnect_backup_one_mod($bf,$preferences,$data);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        return $status;
    }


    function adobeconnect_backup_one_mod($bf,$preferences,$adobeconnect) {

        global $CFG;

        if (is_numeric($adobeconnect)) {
            $adobeconnect = get_record('adobeconnect','id',$adobeconnect);
        }

        $status = true;

        //Start mod
        fwrite($bf, start_tag("MOD",3,true));

        //Print lesson data
        fwrite ($bf,full_tag("ID",4,false,$adobeconnect->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"adobeconnect"));
        fwrite ($bf,full_tag("NAME",4,false,$adobeconnect->name));
        fwrite ($bf,full_tag("INTRO",4,false,$adobeconnect->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$adobeconnect->introformat));
        fwrite ($bf,full_tag("TEMPLATESCOID",4,false,$adobeconnect->templatescoid));
        fwrite ($bf,full_tag("MEETURL",4,false,$adobeconnect->meeturl));
        fwrite ($bf,full_tag("STARTTIME",4,false,$adobeconnect->starttime));
        fwrite ($bf,full_tag("ENDTIME",4,false,$adobeconnect->endtime));
        fwrite ($bf,full_tag("MEETINGPUBLIC",4,false,$adobeconnect->meetingpublic));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$adobeconnect->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$adobeconnect->timemodified));


        //Now we backup lesson pages
        $status = backup_adobeconnect_meeting_groups($bf,$preferences,$adobeconnect->id);
        //if we've selected to backup users info, then backup grades, high scores, and timer info
        if ($status) {
            //End mod
            if ($status) {
                $status =fwrite ($bf,end_tag("MOD",3,true));
            }
        }

        return $status;
    }

    //Backup lesson_pages contents (executed from lesson_backup_mods)
    function backup_adobeconnect_meeting_groups($bf, $preferences, $adobeconnectid) {

        global $CFG;

        // Set status to false because there must be at least one meeting instance
        $status = false;

        // Go through all of the meeting instances and backup each group's meeting
        if ($meetgroups = get_records("adobeconnect_meeting_groups", 'instanceid', $adobeconnectid)) {

            if ($meetgroups) {

                //Write start tag
                $status = fwrite ($bf,start_tag("MEETINGGROUPS",4,true));

                //Iterate over each meeting instance
                foreach ($meetgroups as $meetgroup) {

                    //Start of meeting group instance
                    $status =fwrite ($bf,start_tag("MEETINGGROUP",5,true));

                    fwrite ($bf,full_tag("ID",6,false,$meetgroup->id));
                    fwrite ($bf,full_tag("INSTANCEID",6,false,$meetgroup->instanceid));
                    fwrite ($bf,full_tag("MEETINGSCOID",6,false,$meetgroup->meetingscoid));
                    fwrite ($bf,full_tag("GROUPID",6,false,$meetgroup->groupid));

                    //End of meeting group instance
                    $status =fwrite ($bf,end_tag("MEETINGGROUP",5,true));

                }

                //Write end tag
                $status =fwrite ($bf,end_tag("MEETINGGROUPS",4,true));
            }
        }
        return $status;
    }

    function adobeconnect_check_backup_mods($course, $user_data=false,$backup_unique_code,$instances=null) {

        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += adobeconnect_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }

        //First the course data
        $info[0][0] = get_string('modulenameplural','certificate');
        if ($ids = adobeconnect_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }


        return $info;
    }

 ////Return an array of info (name,value)
   function adobeconnect_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function adobeconnect_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of lessons
        $buscar="/(".$base."\/mod\/adobeconnect\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@ADOBECONNECTINDEX*$2@$',$content);

        //Link to lesson view by moduleid
        $buscar="/(".$base."\/mod\/adobeconnect\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@ADOBECONNECTVIEWBYID*$2@$',$result);

        return $result;
    }

// INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE
   //Returns an array of certificate ids
    function adobeconnect_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}adobeconnect a
                                 WHERE a.course = '$course'");
    }
?>