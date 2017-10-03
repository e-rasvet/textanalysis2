<?php  // $Id: mysql.php,v 1.0 2007/07/02 12:37:00 serafim panov

    require_once("../../config.php");
    require_once("lib.php");

    $id   = optional_param('id', 0, PARAM_INT); 
    $a    = optional_param('a', 0, PARAM_INT);  
    $jid  = optional_param('jid', 0, PARAM_INT);  
    $sf   = optional_param('sf', 0, PARAM_INT);  
    $tf   = optional_param('tf', 0, PARAM_INT);  
    $student = optional_param('student', 0, PARAM_INT);  
    $type = optional_param('type', NULL, PARAM_TEXT);  
    $addselect = optional_param('addselect', NULL, PARAM_TEXT);  
    

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

    add_to_log($course->id, "textanalysis", "view", "view.php?id=$cm->id", "$textanalysis->id");

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $contextmodule = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    $coursestudents = get_enrolled_users($context);
    
    $contents = "UserName,Course,Group,Wordcount,WordUniquecount,Number of sentences,Average Persentence,Hardwords,Lexicaldensity,Fogindex\r\n";
    
    if ($type == "journal") {
            $jurnals = $DB->get_records ("assignment_submissions", array("assignment"=>$jid), "id");
            foreach ($jurnals as $jurnal) {
              if (array_key_exists($jurnal->userid, $coursestudents)) {
                $jurnal->text = strip_tags($jurnal->text);
                
                $userdata = $DB->get_record("user", array("id"=>$jurnal->userid));
                $data = array ();
                $data['wordcount'] = wordcount ($jurnal->text);
                $data['worduniquecount'] = worduniquecount ($jurnal->text);
                $data['numberofsentences'] = numberofsentences ($jurnal->text);
                if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
                    $data['numberofsentences'] = 1;
                }
                $data['averagepersentence'] = averagepersentence ($jurnal->text, $data['wordcount'], $data['numberofsentences']);
                list ($data['hardwords'], $data['hardwordspersent']) = hardwords ($jurnal->text, $data['wordcount']);
                $data['lexicaldensity'] = lexicaldensity ($jurnal->text, $data['wordcount'], $data['worduniquecount']);
                $data['fogindex'] = fogindex ($jurnal->text, $data['averagepersentence'], $data['hardwordspersent']);
                $data['laters'] = laters ($jurnal->text);

                $groupsdata = "";
                if ($usergroups = groups_get_all_groups($course->id, $jurnal->userid)){
                    foreach ($usergroups as $group){
                        $groupsdata .= $group->name.'|';
                    }
                    $groupsdata = substr($groupsdata, 0, -2);
                }
            
                $contents .= $userdata->username.' ('.fullname($userdata) . '),' . $course->fullname . ','.$groupsdata.',' . $data['wordcount'] . ',' . $data['worduniquecount'] . ',' . $data['numberofsentences'] . ',' . $data['averagepersentence'] . ',' . $data['hardwords'] . ',' . $data['lexicaldensity'] . ',' . $data['fogindex'] ."\r\n";
              }
            }
        }
        else if ($type == "blog") {
          $coursestudents = get_enrolled_users($context);
          foreach ($coursestudents as $student) {
            if (array_key_exists($student->id, $coursestudents)) {
              $blogs = $DB->get_records("post", array("userid"=>$student->id), "lastmodified");
              $text = "";
              foreach ($blogs as $blog) {
                  $blog->summary = strip_tags($blog->summary);
                  $text .= $blog->summary;
              }
              
              $userdata = $DB->get_record("user", array("id"=>$student->id));
              $data = array ();
              $data['wordcount'] = wordcount ($text);
              $data['worduniquecount'] = worduniquecount ($text);
              $data['numberofsentences'] = numberofsentences ($text);
              if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
                  $data['numberofsentences'] = 1;
              }
              $data['averagepersentence'] = averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
              list ($data['hardwords'], $data['hardwordspersent']) = hardwords ($text, $data['wordcount']);
              $data['lexicaldensity'] = lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
              $data['fogindex'] = fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
              $data['laters'] = laters ($text);
                  
              $groupsdata = "";
              if ($usergroups = groups_get_all_groups($course->id, $student->id)){
                  foreach ($usergroups as $group){
                      $groupsdata .= $group->name.'|';
                  }
                  $groupsdata = substr($groupsdata, 0, -2);
              }
                  
              $contents .= $userdata->username.' ('.fullname($userdata) . '),' . $course->fullname . ','.$groupsdata.',' . $data['wordcount'] . ',' . $data['worduniquecount'] . ',' . $data['numberofsentences'] . ',' . $data['averagepersentence'] . ',' . $data['hardwords'] . ',' . $data['lexicaldensity'] . ',' . $data['fogindex'] ."\r\n";
            }
          }
        }
        else if ($type == "gmap") {
          include_once($CFG->dirroot."/blocks/map/lib.php");
          $coursestudents = get_enrolled_users($context);

          foreach ($coursestudents as $student) {
            if (array_key_exists($student->id, $coursestudents)) {
              $marks = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}map_items WHERE courseid='".block_map_checkshareid_course($course->id)."' and userid='{$student->id}' ORDER BY time");
              $text = "";
              foreach ($marks as $mark) {
                  $mark->descr = strip_tags($mark->descr);
                  $text .= $mark->descr;
              }
              
              $userdata = $DB->get_record ("user", array("id"=>$student->id));
              $data = Array ();
              $data['wordcount'] = wordcount ($text);
              $data['worduniquecount'] = worduniquecount ($text);
              $data['numberofsentences'] = numberofsentences ($text);
              if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
                  $data['numberofsentences'] = 1;
              }
              $data['averagepersentence'] = averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
              list ($data['hardwords'], $data['hardwordspersent']) = hardwords ($text, $data['wordcount']);
              $data['lexicaldensity'] = lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
              $data['fogindex'] = fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
              $data['laters'] = laters ($text);
                  
              $groupsdata = "";
              if ($usergroups = groups_get_all_groups($course->id, $student->id)){
                  foreach ($usergroups as $group){
                      $groupsdata .= $group->name.'|';
                  }
                  $groupsdata = substr($groupsdata, 0, -2);
              }
                  
              $contents .= $userdata->username.' ('.fullname($userdata) . '),' . $course->fullname . ','.$groupsdata.',' . $data['wordcount'] . ',' . $data['worduniquecount'] . ',' . $data['numberofsentences'] . ',' . $data['averagepersentence'] . ',' . $data['hardwords'] . ',' . $data['lexicaldensity'] . ',' . $data['fogindex'] ."\r\n";
            }
          }
        }
        /*
        else if ($type == "chat") {
          $coursestudents = get_enrolled_users($context);
          foreach ($coursestudents as $student) {
            $blogs = $DB->get_records ("chat_messages", array("userid"=>$student->id), "timestamp");
            $text = "";
            foreach ($blogs as $blog) {
                $blog->message = strip_tags($blog->message);
                $text .= $blog->message;
            }
            
            $userdata = $DB->get_record("user", array("id"=>$student->id));
            $data = array ();
            $data['wordcount'] = wordcount ($text);
            $data['worduniquecount'] = worduniquecount ($text);
            $data['numberofsentences'] = numberofsentences ($text);
            if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
                $data['numberofsentences'] = 1;
            }
            $data['averagepersentence'] = averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
            list ($data['hardwords'], $data['hardwordspersent']) = hardwords ($text, $data['wordcount']);
            $data['lexicaldensity'] = lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
            $data['fogindex'] = fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
            $data['laters'] = laters ($text);
                
            $groupsdata = "";
            if ($usergroups = groups_get_all_groups($course->id, $student->userid)){
                foreach ($usergroups as $group){
                    $groupsdata .= $group->name.'|';
                }
                $groupsdata = substr($groupsdata, 0, -2);
            }
                
            $contents .= $userdata->username.' ('.fullname($userdata) . '),' . $course->fullname . ','.$groupsdata.',' . $data['wordcount'] . ',' . $data['worduniquecount'] . ',' . $data['numberofsentences'] . ',' . $data['averagepersentence'] . ',' . $data['hardwords'] . ',' . $data['lexicaldensity'] . ',' . $data['fogindex'] ."\r\n";
          }
        }
        */
        else if ($type == "forum") {
          $coursestudents = get_enrolled_users($context);
          foreach ($coursestudents as $student) {
            if (array_key_exists($student->id, $coursestudents)) {
              $forums = $DB->get_records("forum_posts", array("userid"=>$student->id));
              $text = "";
              foreach ($forums as $forum) {
                  $forum->message = strip_tags($forum->message);
                  $text .= $forum->message;
              }
                
              $userdata = $DB->get_record ("user", array("id"=>$student->id));
              $data = Array ();
              $data['wordcount'] = wordcount ($text);
              $data['worduniquecount'] = worduniquecount ($text);
              $data['numberofsentences'] = numberofsentences ($text);
              if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
                  $data['numberofsentences'] = 1;
              }
              $data['averagepersentence'] = averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
              list ($data['hardwords'], $data['hardwordspersent']) = hardwords ($text, $data['wordcount']);
              $data['lexicaldensity'] = lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
              $data['fogindex'] = fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
              $data['laters'] = laters ($text);
                  
              $groupsdata = "";
              if ($usergroups = groups_get_all_groups($course->id, $student->userid)){
                  foreach ($usergroups as $group){
                      $groupsdata .= $group->name.'|';
                  }
                  $groupsdata = substr($groupsdata, 0, -2);
              }
                  
              $contents .= $userdata->username.' ('.fullname($userdata) . '),' . ',' . $course->fullname . ','.$groupsdata.',' . $data['wordcount'] . ',' . $data['worduniquecount'] . ',' . $data['numberofsentences'] . ',' . $data['averagepersentence'] . ',' . $data['hardwords'] . ',' . $data['lexicaldensity'] . ',' . $data['fogindex'] ."\r\n";
            }
          }
        }
        
        header("Content-type: application/octet-stream");
        header('Content-Disposition: inline; filename=text_content_analysis_tool.csv'); 
                
        echo $contents;


function wordcount ($text) {
    return str_word_count ($text);
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
    $textarrayf = array();
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
    if ($word > 0)
        $count = round(($wordunic / $word) * 100, 2);
    else
        $count = 0;
        
    return $count;
}


function fogindex ($text, $averagepersentence, $hardwordspersent) {
    $count = round(($averagepersentence + $hardwordspersent) * 0.4, 2);
    return $count;
}



function laters ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = array ();
    $result = array ();
    
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
                if (!isset($result[$i]))
                    $result[$i]=1;
                else 
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
            $syllables ++;
        }
    }
    
    if ($wordstotal > 0)
        $score = round(($syllables / $wordstotal) * 100, 2);
    else
        $score = 0;

    return array($syllables, $score);
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

  if(@$nos[strlen($nos)-1] == "E") $syllables --;
  if(@$nos[strlen($nos)-1] == "Y") $syllables ++;

  $before = $after;
  $nos = str_replace(array('A','E','I','O','U'),"",$nos);
  $after = strlen($nos);
  $syllables += ($before - $after);

  return $syllables;
}


function get_albumname ($albums, $item) {
    global $storeConfig;

    return "no name";
}

?>