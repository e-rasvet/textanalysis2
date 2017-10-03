<?php  // $Id: mysql.php,v 1.0 2007/07/02 12:37:00 serafim panov

    require_once("../../config.php");
    require_once("lib.php");

    $id   = optional_param('id', 0, PARAM_INT); 
    $a    = optional_param('a', NULL, PARAM_CLEAN);  
    $jid  = optional_param('jid', 0, PARAM_INT);  
    $sf   = optional_param('sf', 0, PARAM_INT);  
    $tf   = optional_param('tf', 0, PARAM_INT);  
    $student = optional_param('student', 0, PARAM_INT);  
    $type = optional_param('type', NULL, PARAM_CLEAN);  
    

    if ($id) {
        if (! $cm = get_coursemodule_from_id('textanalysis', $id)) {
            error('Course Module ID was incorrect');
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            error('Course is misconfigured');
        }

        if (! $textanalysis = $DB->get_record('textanalysis', array('id' => $cm->instance))) {
            error('Course module is incorrect');
        }
    } else {
        error('You must specify a course_module ID or an instance ID');
    }

    require_login($course, true, $cm);
    
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $contextmodule = get_context_instance(CONTEXT_MODULE, $cm->id);
    $coursestudents = get_enrolled_users($context);

    add_to_log($course->id, "textanalysis", "view", "view.php?id=$cm->id", "$textanalysis->id");

    /// Print the page header
    $strtextanalysiss = get_string('modulenameplural', 'textanalysis');
    $strtextanalysis  = get_string('modulename', 'textanalysis');

    $PAGE->set_url('/mod/textanalysis/view.php', array('id' => $id));
        
    $title = $course->shortname . ': ' . format_string($textanalysis->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    /// Print the main part of the page

    require_once ('tabs.php');
    
    echo $OUTPUT->box_start('generalbox');
    
    if (empty ($type)) {
        //-------------Journal title-----------//
        
        if ($textanalysis->show_journal == 1) {
            echo "<h2>Journal:</h2><br />";
            
            $jurnals = $DB->get_records ("assignment", array("course"=>$course->id, "assignmenttype"=>"online"));
            
            if (is_array($jurnals)) {
                foreach ($jurnals as $jurnal) {
                    if (!$students = get_enrolled_users($context)) {
                        $students = array();
                    }
                    foreach ($students as $student) {
                        $coursestudents[$student->username] = $student->id;
                    }
                    $jurnals = $DB->get_records ("assignment_submissions", array("assignment"=>$jurnal->id), "id");
                    $count = 0;
                    foreach ($jurnals as $jurnal_) {
                      if (has_capability('mod/textanalysis:teacher', $contextmodule) || $jurnal_->userid == $USER->id) {
                        $userdata = $DB->get_record ("user", array("id"=>$jurnal_->userid));
                        if (in_array($jurnal_->userid,$coursestudents)) {
                            $count++;
                        }
                      }
                    
                    }
                    
                    if ($count > 0)
                      echo '<a href="?id='.$id.'&type=journal&jid='.$jurnal->id.'">'.$jurnal->name.'</a> (' . $count . ') <br />';
                    else
                      echo $jurnal->name.' (' . $count . ') <br />';
              }
            }
        }
        
        //-------------Forums  title-----------//
        
        if ($textanalysis->show_forum == 1) {
            echo "<h2>Forums:</h2><br />";
            
            $forums = $DB->get_records ("forum", array("course"=>$cm->course));
        
            if (is_array($forums)) {
                foreach ($forums as $forum) {
                    if (has_capability('mod/textanalysis:teacher', $contextmodule)) {
                        $summarylink = '<a href="?id='.$id.'&jid='.$forum->id.'&type=forum&tf=1">summary students data</a>';
                    } else {
                        $summarylink = "";
                    }
                    
                    if (!$students = get_enrolled_users($context)) {
                        $students = array();
                    }
                    foreach ($students as $student) {
                        $coursestudents[$student->username] = $student->id;
                    }
                    $jurnals = $DB->get_records("forum_discussions", array("forum"=>$forum->id));
                    $count = 0;
                    foreach ($jurnals as $jurnal) {
                        if (has_capability('mod/textanalysis:teacher', $contextmodule) || $jurnal->userid == $USER->id) {
                            $userdata = $DB->get_record("user", array("id"=>$jurnal->userid));
                            if (in_array($jurnal->userid,$coursestudents)) {
                                $count++;
                            }
                        }
                    }
                    
                    if ($count > 0)
                      echo '<a href="?id='.$id.'&type=forum&jid='.$forum->id.'">'.$forum->name.'</a> (' . $count . ')  '.$summarylink.' <br />';
                    else
                      echo $forum->name.' (' . $count . ') <br />';
                }
            }
        }
        //-------------Blog    title-----------//
        
        if ($textanalysis->show_blog == 1) {
            echo "<h2>Blogs:</h2><br />";
            
            $allstudents = get_enrolled_users($context);
                
            if (is_array($allstudents)) {
                foreach ($allstudents as $allstudent) {
                    if (has_capability('mod/textanalysis:teacher', $contextmodule) || $allstudent->id == $USER->id) {
                        $sdata = $DB->get_records("post",array("userid"=>$allstudent->id));
                        $additionaldata = "";
                        while(list($key,$value)=each($sdata)) {
                            $additionaldata .= "({$value->subject} & ".textanalysis_wordcount($value->summary).") ";
                        }
                        
                        $count = $DB->count_records ("post", array("userid"=>$allstudent->id));
                        
                        if ($count > 0)
                          echo '<a href="?id='.$id.'&type=blog&student='.$allstudent->id.'">'.fullname($allstudent).'</a> (<a href="?id='.$id.'&type=blog&tf=1&student='.$allstudent->id.'">only summary</a>) Total number of posts:'. $count .$additionaldata .'<br />';
                        else
                          echo fullname($allstudent).'<br />';
                    }
                }
            }
        }
        
        //-------------Chat    title-----------//
        
        /*
        echo "<h2>Chat:</h2><br />";
        
        $chats = $DB->get_records ("chat", array("course"=>$course->id));
    
        if (is_array($chats)) {
            foreach ($chats as $chat) {
                echo '<a href="?id='.$id.'&type=chat&jid='.$chat->id.'">'.$chat->name.'</a> <br />';
            }
        }
        */
    
        //-------------Google title-----------//
        if ($textanalysis->show_map == 1) {
            echo "<h2>Google Map:</h2><br />";
            
            if (is_file($CFG->dirroot."/blocks/map/lib.php")) {
              include_once($CFG->dirroot."/blocks/map/lib.php");
              $allstudents = get_enrolled_users($context);
              
              if ($gmap = $DB->get_records("map_items", array("courseid"=>block_map_checkshareid_course($course->id)))) {
                foreach ($allstudents as $allstudent) {
                    $sdata = $DB->get_records_sql(" SELECT * FROM {$CFG->prefix}map_items WHERE userid = {$allstudent->id} AND courseid= ".block_map_checkshareid_course($course->id));
                    $additionaldata = "";
                    while(list($key,$value)=each($sdata)) {
                        $additionaldata .= "({$value->name} & ".textanalysis_wordcount($value->descr).") ";
                    }
                    if (has_capability('mod/textanalysis:teacher', $contextmodule) || $allstudent->id == $USER->id) {
                        if ($DB->count_records("map_items", array('courseid'=>block_map_checkshareid_course($course->id), 'userid'=>$allstudent->id)) > 0)
                          echo '<a href="?id='.$id.'&type=gmap&tf=1&student='.$allstudent->id.'">'.fullname($allstudent).'</a> (<a href="?id='.$id.'&type=gmap&student='.$allstudent->id.'">only summary</a>) Total number of posts:'. $DB->count_records("map_items", array('courseid'=>block_map_checkshareid_course($course->id), 'userid'=>$allstudent->id)).$additionaldata .'<br />';
                        else
                          echo fullname($allstudent).'<br />';
                    }
                }
              }
            }
        }
    } else if ($type == "journal") {
        if (!$students = get_enrolled_users($context)) {
            $students = array();
        }
            
        foreach ($students as $student) {
            $coursestudents[$student->username] = $student->id;
        }
    
        echo "<h2>Journal:</h2><br />";
        echo '<a href="get_csv.php?id='.$id.'&type=journal&jid='.$jid.'">Download CSV</a><br /><br />';
        $jurnals = $DB->get_records ("assignment_submissions", array("assignment"=>$jid), "id");
        
        foreach ($jurnals as $jurnal) {
          if (has_capability('mod/textanalysis:teacher', $contextmodule) || $jurnal->userid == $USER->id) {
            $userdata = $DB->get_record ("user", array("id"=>$jurnal->userid));
            if (in_array($jurnal->userid,$coursestudents)) {
                $imagepath = textanalysis_getuserimage($jurnal->userid);
                echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
                echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$jurnal->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%">'.fullname($userdata).'</td></tr><tr><td width="100%">'.format_text($jurnal->data1).'<hr />';
                textanalysis_printanalizeform($jurnal->data1);
                echo '</td></tr>';
                echo '</table>';
            }
          }
        }
    } else if ($type == "forum") {
        echo "<h2>Forums:</h2><br />";
        echo '<a href="get_csv.php?id='.$id.'&type=forum">Download CSV</a><br /><br />';
        if ($tf == 0) {
            if ($sf == 0) {
                $forums = $DB->get_records ("forum", array("id"=>$jid));
                foreach ($forums as $forum) {
                    $forumentrys = $DB->get_records_sql("SELECT * FROM ".$CFG->prefix."forum_discussions WHERE forum = '".$forum->id."'");
                    if (is_array($forumentrys)) {
                        foreach ($forumentrys as $forumentry) {
                            if (!has_capability('mod/textanalysis:teacher', $contextmodule)) {
                                $userdpostscount = " (" . $DB->count_records("forum_posts", array("discussion"=>$forumentry->id, "userid"=>$USER->id)) . ")";
                            }
                            else
                            {
                                $userdpostscount = " (" . $DB->count_records("forum_posts", array("discussion"=>$forumentry->id)) . ")";
                            }
                            echo '<a href="?id='.$id.'&jid='.$jid.'&type=forum&sf='.$forumentry->id.'">' . $forumentry->name ."</a> ".$userdpostscount.' <br />';
                        }
                    }
                }
            } else {
                $forums = $DB->get_records("forum_posts", array("discussion"=>$sf), "modified");

                foreach ($forums as $forum) {
                  if (has_capability('mod/textanalysis:teacher', $contextmodule) || $forum->userid == $USER->id) {
                    $userdata = $DB->get_record ("user", array("id"=>$forum->userid));
          
                    $imagepath = textanalysis_getuserimage($forum->userid);

                    echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
                    echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$forum->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%">'.fullname($userdata).'</td></tr><tr><td width="100%">'.format_text($forum->message).'<hr />';
            
                    textanalysis_printanalizeform($forum->message);

                    echo '</td></tr>';
        
                    echo '</table>';
                  }
                
                }
            
            }
        
        } else if ($tf == 1) {
          if ($student == 0) {
            $allstudents = get_enrolled_users($context);
            
            foreach ($allstudents as $allstudent) {
                echo '<a href="?id='.$id.'&jid='.$jid.'&type=forum&tf=1&student='.$allstudent->id.'">'.fullname($allstudent).'</a> count of posts:'. $DB->count_records ("forum_posts", array("userid"=>$allstudent->id)) .'<br />';
            }
            
          } else {
              $userdata = $DB->get_record("user", array("id"=>$student));
              
              $forums = $DB->get_records_sql ("SELECT * FROM {forum_posts} WHERE userid = ?", array($student));

              foreach ($forums as $forum) {
                  $text .= $forum->message . " ";
              }
          
              echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
              echo '<tr><td><b>'.fullname($userdata).'</b><br /><hr />';
            
              textanalysis_printanalizeform($text);

              echo '</td></tr>';
        
              echo '</table>';
          }
        }
    } else if ($type == "blog") {
        echo "<h2>Blogs:</h2><br />";
        echo '<a href="get_csv.php?id='.$id.'&type=blog">Download CSV</a><br /><br />';
        if ($tf == 0) {
            $blogs = $DB->get_records ("post", array("userid"=>$student), "lastmodified");
            foreach ($blogs as $blog) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $blog->userid == $USER->id) {
                $userdata = $DB->get_record ("user", array("id"=>$blog->userid));
          
                $imagepath = textanalysis_getuserimage($blog->userid);

                echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
                echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$blog->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%">'.fullname($userdata).' '.format_text($blog->subject).'</td></tr><tr><td width="100%">'.format_text($blog->summary).'<hr />';
            
                textanalysis_printanalizeform($blog->summary);

                echo '</td></tr>';
        
                echo '</table>';
                /*
                echo '<div><strong>Debuging</strong></div>';
                echo '<div>'.str_word_count_my ($blog->summary, true).'</div>';
                echo '<div><strong>Sentenses</strong></div>';
                echo '<div>'.textanalysis_numberofsentences ($blog->summary, true).'</div>';
                */
              }
            }
        } else {
            $blogs = $DB->get_records ("post", array("userid"=>$student), "lastmodified");
            $text = '';
            foreach ($blogs as $blog) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $blog->userid == $USER->id) {
                  $text .= $blog->summary;
              }
            }
            
            $userdata  = $DB->get_record ("user", array("id"=>$student));
            $imagepath = textanalysis_getuserimage($student);
            
            echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
            echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$blog->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%">'.fullname($userdata).'</td></tr><tr><td width="100%"><hr />';
            
            textanalysis_printanalizeform($text);
            
            echo '</td></tr>';
        
            echo '</table>';
        }
    } else if ($type == "gmap") {
        include_once($CFG->dirroot."/blocks/map/lib.php");
        echo "<h2>Google map:</h2><br />";
        
        $allstudents = get_enrolled_users($context);
          
        if ($gmap = $DB->get_records("map_items", array("courseid"=>block_map_checkshareid_course($course->id)))) {
            $f = 0;
            $stnext = false;
            foreach ($allstudents as $allstudent) {
              $contofmaprecords = $DB->count_records("map_items",array('courseid'=>block_map_checkshareid_course($course->id), 'userid'=>$allstudent->id));
              if (!empty($contofmaprecords)) {
                if ($f == 0)
                  $stfirst = $allstudent->id;
                if ($stnext == true) {
                  $studentnext = $allstudent->id;
                  break;
                }
                if ($allstudent->id == $student)
                  $stnext = true;
                $f++;
              }
            }
        }
        
        if (empty($studentnext)) $studentnext = $stfirst;
        
        $mapblockinstanse = block_map_checkshareid_getinstanse ($course->id);
        
        echo '<div><a href="'.$CFG->wwwroot.'/mod/textanalysis/view.php?id='.$id.'">Back</a><br /><br />';
        echo '<a href="get_csv.php?id='.$id.'&type=gmap">Download CSV</a><br /><br /></div>';
        echo '<div style="text-align:right"><a href="'.$CFG->wwwroot.'/mod/textanalysis/view.php?id='.$id.'&type=gmap&tf='.$tf.'&student='.$studentnext.'">NEXT -></a></div>';
        if ($tf == 0) {
            $marks = $DB->get_records_sql ("SELECT * FROM {$CFG->prefix}map_items WHERE courseid='".block_map_checkshareid_course($course->id)."' and userid='{$student}'");
            foreach ($marks as $mark) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $mark->userid == $USER->id) {
                $userdata = $DB->get_record ("user", array("id"=>$mark->userid));
          
                $imagepath = textanalysis_getuserimage($mark->userid);

                echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
                echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/user/profile.php?id='.$mark->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%"><a href="'.$CFG->wwwroot.'/user/profile.php?id='.$mark->userid.'&course='.$course->id.'">'.fullname($userdata).'</a> '.format_text($mark->descr).'</td></tr><tr><td width="100%"><hr />';
            
                textanalysis_printanalizeform($mark->descr);

                echo '</td></tr>';
        
                echo '</table>';
                
                /*
                echo '<div><strong>Debuging</strong></div>';
                echo '<div>'.str_word_count_my ($mark->descr, true).'</div>';
                echo '<div><strong>Sentenses</strong></div>';
                echo '<div>'.textanalysis_numberofsentences ($mark->descr, true).'</div>';
                */
              }
            }
        } else {
            $marks = $DB->get_records_sql ("SELECT * FROM {map_items} WHERE courseid='".block_map_checkshareid_course($course->id)."' and userid='{$student}' ORDER BY time");
            
            $text = "";
            
            foreach ($marks as $mark) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $mark->userid == $USER->id) {
                  $text .= $mark->descr;
              }
            }
            
            $userdata  = $DB->get_record ("user", array("id"=>$student));
            $imagepath = textanalysis_getuserimage($student);
            
            echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
            echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$mark->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%"><a href="'.$CFG->wwwroot.'/user/profile.php?id='.$mark->userid.'&course='.$course->id.'">'.fullname($userdata).'</a></td></tr><tr><td width="100%">';
            
            textanalysis_printanalizeform($text);
            
            echo '</td></tr>';
        
            echo '</table>';
        }
    } else if ($type == "chat") {
        echo "<h2>Chat:</h2><br />";
        echo '<a href="get_csv.php?id='.$id.'&type=chat">Download CSV</a><br /><br />';
        if ($tf == 0) {
            $allstudents = get_enrolled_users($context);
            foreach ($allstudents as $allstudent) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $allstudent->id == $USER->id) {
                echo '<a href="?id='.$id.'&jid='.$jid.'&type=chat&tf=1&student='.$allstudent->id.'">'.fullname($allstudent).'</a> count of posts:'. $DB->count_records ("chat_messages", array("userid"=>$allstudent->id)) .'<br />';
              }
            }
        } else {
            $chats = $DB->get_records ("chat_messages", array("userid"=>$student), "timestamp");

            foreach ($chats as $chat) {
              if (has_capability('mod/textanalysis:teacher', $contextmodule) || $chat->userid == $USER->id) {
                  $text .= $chat->message . " ";
              }
            }
            
            $userdata  = $DB->get_record ("user", array("id"=>$student));
            $imagepath = textanalysis_getuserimage($student);
            
            echo '<table border="1" cellspacing="0" valign="top" cellpadding="10">';
        
            echo '<tr><td rowspan="2" width="35" valign="top"><a  href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$chat->userid.'&course='.$course->id.'"><img class="userpicture" align="middle" src="'.$imagepath.'" border="0" width="35" height="35" alt="" /></a></td><td width="100%">'.fullname($userdata).'</td></tr><tr><td width="100%"><hr />';
            
            textanalysis_printanalizeform($text);
            
            echo '</td></tr>';
        
            echo '</table>';
        }
    }
    

    echo $OUTPUT->box_end();

    echo $OUTPUT->footer();



function wordcount ($text) {
    return str_word_count_my ($text);
}


function worduniquecount ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = Array ();
    
    foreach ($words as $word) {
        if (!in_array($word, $words_)) {
            $words_[] = strtolower ($word);
        }
    }

    return count ($words_);
}


function numberofsentences ($text) {
    $text = strip_tags ($text);
    $noneed = array ("\r", "\n", ".0", ".1", ".2", ".3", ".4", ".5", ".6", ".7", ".8", ".9");
    foreach ($noneed as $noneed_) {
        $text = str_replace ($noneed_, " ", $text);
    }
    $text = str_replace ("!", ".", $text);
    $text = str_replace ("?", ".", $text);
    $textarray = explode (".", $text);
    foreach ($textarray as $textarray_) {
        if (!empty($textarray_) && strlen ($textarray_) > 5) {
            $textarrayf[] = $textarray_;
        }
    } 
    $count = count($textarrayf);
    return $count;
}


function averagepersentence ($text, $words, $sentences) {
    $count = round($words / $sentences, 2);
    return $count;
}


function lexicaldensity ($text, $word, $wordunic) {
    $count = round(($wordunic / $word) * 100, 2);

    return $count;
}


function fogindex ($text, $averagepersentence, $hardwordspersent) {
    $count = round(($averagepersentence + $hardwordspersent) * 0.4, 2);

    return $count;
}


function laters ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = Array ();
    
    $max = 1;
    
    foreach ($words as $word) {
        if (!in_array($word, $words_)) {
            $words_[] = strtolower ($word);
            if (strlen ($word) > $max) {
                $max = strlen ($word);
            }
        }
    }
    
    for ($i=1; $i<=$max; $i++) {
        foreach ($words as $word) {
            if (strlen($word) == $i) {
                $result[$i] ++;
            }
        }
    }

    return $result;
}


function hardwords($text, $wordstotal) {
    $syllables = 0;
    $words = explode(' ', $text);
    for ($i = 0; $i < count($words); $i++) {
        if (count_syllables($words[$i]) > 2) {
            //echo $words[$i] . "/" . count_syllables($words[$i]) ."<br />";
            $syllables ++;
        }
    }

    $score = round(($syllables / $wordstotal) * 100, 2);

    return Array($syllables, $score);
}


function count_syllables($word) {
    $nos = strtoupper($word);
    $syllables = 0;

    $before = strlen($nos);
    $nos = str_replace(array('AA','AE','AI','AO','AU',
    'EA','EE','EI','EO','EU','IA','IE','II','IO',
    'IU','OA','OE','OI','OO','OU','UA','UE',
    'UI','UO','UU'), "", $nos);
    $after = strlen($nos);
    $diference = $before - $after;
    if($before != $after) $syllables += $diference / 2;

    if($nos[strlen($nos)-1] == "E") $syllables --;
    if($nos[strlen($nos)-1] == "Y") $syllables ++;

    $before = $after;
    $nos = str_replace(array('A','E','I','O','U'),"",$nos);
    $after = strlen($nos);
    $syllables += ($before - $after);

    return $syllables;
}


function get_albumname ($albums, $item) {
    global $storeConfig;
    
    return "No name";
}
