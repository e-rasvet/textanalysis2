<?php  // $Id: mysql.php,v 1.0 2007/07/02 12:37:00 serafim panov

    require_once("../../config.php");
    require_once("lib.php");

    $id         = optional_param('id', 0, PARAM_INT); 
    $csv        = optional_param('csv', 0, PARAM_INT); 
    $a          = optional_param('a', NULL, PARAM_CLEAN);  
    $sort       = optional_param('sort', 'name', PARAM_ALPHA); 
    $orderby    = optional_param('orderby', 'ASC', PARAM_ALPHA); 

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
    

    add_to_log($course->id, "textanalysis", "view", "view.php?id=$cm->id", "$textanalysis->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    /// Print the page header
    $strtextanalysiss = get_string('modulenameplural', 'textanalysis');
    $strtextanalysis  = get_string('modulename', 'textanalysis');

    $PAGE->set_url('/mod/textanalysis/stats.php', array('id' => $id));
        
    $title = $course->shortname . ': ' . format_string($textanalysis->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);
    
    if (!$csv) {
      echo $OUTPUT->header();
      require_once ('tabs.php');
    }
    
    $coursestudents = get_enrolled_users($context);

    foreach ($coursestudents as $coursestudent) {
        if ($allposts = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}forum_posts WHERE userid = '{$coursestudent->id}' ORDER BY modified ASC")) {
            $stat[$coursestudent->id]['countofforumposts'] = count($allposts);
            $stat[$coursestudent->id]['forumposts'] = "";
            foreach ($allposts as $allpost) {
                $stat[$coursestudent->id]['forumposts'] .= strip_tags($allpost->message);
            }
            $stat[$coursestudent->id]['numberofwordsofforum'] = str_word_count_my($stat[$coursestudent->id]['forumposts']);
        } else {
            $stat[$coursestudent->id]['countofforumposts']    = 0;
            $stat[$coursestudent->id]['numberofwordsofforum'] = 0;
        }
        
        
        if ($alljournals = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}assignment_submissions WHERE userid = '{$coursestudent->id}'")) {
            $stat[$coursestudent->id]['countofjournalposts'] = count($alljournals);
            $stat[$coursestudent->id]['journalposts'] = "";
            foreach ($alljournals as $alljournal) {
                $stat[$coursestudent->id]['journalposts'] .= strip_tags($alljournal->data1);
            }
            $stat[$coursestudent->id]['numberofwordsofjournal'] = str_word_count_my($stat[$coursestudent->id]['journalposts']);
        } else {
            $stat[$coursestudent->id]['countofjournalposts'] = 0;
            $stat[$coursestudent->id]['numberofwordsofjournal'] = 0;
        }
        
        
        if ($allblogs = $DB->get_records_sql("SELECT * FROM {$CFG->prefix}post WHERE module = 'blog' and userid = '{$coursestudent->id}' ORDER BY lastmodified ASC")) {
            $stat[$coursestudent->id]['countofblogposts'] = count($allblogs);
            $stat[$coursestudent->id]['blogposts'] = "";
            foreach ($allblogs as $allblog) {
                $stat[$coursestudent->id]['blogposts'] .= strip_tags($allblog->summary);
            }
            $stat[$coursestudent->id]['numberofwordsofblog'] = str_word_count_my($stat[$coursestudent->id]['blogposts']);
        } else {
            $stat[$coursestudent->id]['countofblogposts'] = 0;
            $stat[$coursestudent->id]['numberofwordsofblog'] = 0;
        }
        
        $stat[$coursestudent->id]['countofgalleryposts'] = 0;
        $stat[$coursestudent->id]['numberofwordsofgallery'] = 0;
        $stat[$coursestudent->id]['totalgallerycomparepersent'] = 0;
        
        if (is_file($CFG->dirroot."/blocks/map/lib.php")) {
          include_once($CFG->dirroot."/blocks/map/lib.php");
          if ($marks = $DB->get_records_sql ("SELECT * FROM {$CFG->prefix}map_items WHERE courseid='".block_map_checkshareid_course($course->id)."' and userid='{$coursestudent->id}'")) {
            $stat[$coursestudent->id]['countofmapposts'] = count($marks);
            $stat[$coursestudent->id]['mapposts'] = "";
            foreach ($marks as $mark) {
              $stat[$coursestudent->id]['mapposts'] .= strip_tags($mark->descr);
            }
            $stat[$coursestudent->id]['numberofwordsofmap'] = str_word_count_my($stat[$coursestudent->id]['mapposts']);
          } else {
            $stat[$coursestudent->id]['countofmapposts']    = 0;
            $stat[$coursestudent->id]['numberofwordsofmap'] = 0;
          }
        } else {
          $stat[$coursestudent->id]['countofmapposts']    = 0;
          $stat[$coursestudent->id]['numberofwordsofmap'] = 0;
        }
    }
    
    $titlesarray = Array ('<small>student ID</small>'=>'studentid', '<small>Name</small>'=>'name');
    
    if ($textanalysis->show_forum == 1) 
      $titlesarray = array_merge($titlesarray, array('<small>forum<br /> (posts)</small>'=>'forumposts', '<small>forum<br /> (words)</small>'=>'forumwords'));
      
    if ($textanalysis->show_blog == 1)
      $titlesarray = array_merge($titlesarray, array('<small>blog<br /> (posts)</small>'=>'blogposts', '<small>blog<br /> (words)</small>'=>'blogwords'));
      
    if ($textanalysis->show_journal == 1)
      $titlesarray = array_merge($titlesarray, array('<small>Text assignment<br /> (posts)</small>'=>'journalposts', '<small>Text assignment<br /> (words)</small>'=>'journalwords'));
    
    if ($textanalysis->show_map == 1)
      $titlesarray = array_merge($titlesarray, array('<small>Map<br /> (posts)</small>'=>'mapposts', '<small>Map<br /> (words)</small>'=>'mapwords'));
      
    $titlesarray = array_merge($titlesarray, array('<small>all posts<br /> (words)</small>' => 'wordsinallposts'));
    
    $table = new html_table();
     
    $table->head = textanalysis_make_table_headers ($titlesarray, $orderby, $sort, 'stats.php?id='.$id.'&a='.$a);
    $table->align = array ("left", "left");
    
    if ($textanalysis->show_forum == 1)
      $table->align = array_merge($table->align, array('center', 'center'));
      
    if ($textanalysis->show_blog == 1)
      $table->align = array_merge($table->align, array('center', 'center'));
      
    if ($textanalysis->show_journal == 1)
      $table->align = array_merge($table->align, array('center', 'center'));
    
    if ($textanalysis->show_map == 1)
      $table->align = array_merge($table->align, array('center', 'center'));
    
    $table->align = array_merge($table->align, array('center'));
    
    $table->width = "100%";
    
    $c = 0;
    foreach ($stat as $key => $stat_) {
      if (has_capability('mod/textanalysis:teacher', $contextmodule) || $key == $USER->id) {
        $c++;
        $userdata = $DB->get_record ("user", array("id"=>$key));
        $table->data[$c] = @array ($userdata->username, '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userdata->id.'&course='.$course->id.'">'.fullname($userdata).'</a>');
        
        
        if ($textanalysis->show_forum == 1)
          $table->data[$c] = array_merge($table->data[$c], array($stat_['countofforumposts'], $stat_['numberofwordsofforum']));
          
        if ($textanalysis->show_blog == 1)
          $table->data[$c] = array_merge($table->data[$c], array($stat_['countofblogposts'], $stat_['numberofwordsofblog']));
          
        if ($textanalysis->show_journal == 1)
          $table->data[$c] = array_merge($table->data[$c], array($stat_['countofjournalposts'], $stat_['numberofwordsofjournal']));
        
        if ($textanalysis->show_map == 1)
          $table->data[$c] = array_merge($table->data[$c], array($stat_['countofmapposts'], $stat_['numberofwordsofmap']));
        
        $table->data[$c] = array_merge($table->data[$c], array($stat_['numberofwordsofforum'] + $stat_['numberofwordsofblog'] + $stat_['numberofwordsofjournal'] + $stat_['numberofwordsofmap']));
      }
    }
    
    $table->data = textanalysis_sort_table_data ($table->data, $titlesarray, $orderby, $sort);
    
    if ($table && !$csv) {
        echo '<a href="stats.php?a=summary&id='.$id.'&csv=1">Download CSV</a><br />';
        echo html_writer::table($table);
    } else {
        header("Content-type: application/octet-stream");
        header('Content-Disposition: inline; filename=summary.csv'); 
        
        $o = "student ID;Name;";
        
        if ($textanalysis->show_forum == 1)
          $o .= "forum (posts);forum (words);";
          
        if ($textanalysis->show_blog == 1)
          $o .= "blog (posts);blog (words);";
          
        if ($textanalysis->show_journal == 1)
          $o .= "text assignment (posts);text assignment (words);";
        
        if ($textanalysis->show_map == 1)
          $o .= "Map (posts);Map (words);";
        
        $o .= "all posts (words)\n";
        
        foreach ($table->data as $data) {
          foreach ($data as $val) {
            $o .= $val.";";
          }
          $o = substr($o, 0, -1);
          $o .= "\n";
        }
        
        
        echo $o;
    }
  
    if (!$csv)
        echo $OUTPUT->footer();
