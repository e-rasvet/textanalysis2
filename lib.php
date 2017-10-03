<?php  // $Id: mysql.php,v 1.0 2007/07/02 12:37:00 serafim panov

if (!@$CFG->textanalysis_textcompare) {
    $CFG->textanalysis_textcompare = 5;
}
if (!@$CFG->textanalysis_textword) {
    $CFG->textanalysis_textword= 0;
}


function textanalysis_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_ADVANCED_GRADING:        return true;

        default: return null;
    }
}


function textanalysis_add_instance($textanalysis) {
    global $CFG, $USER, $DB;
    
    
    //$id = $textanalysis->courseid;
    $textanalysis->timemodified = time();
    
    if(empty($textanalysis->show_forum)) 
      $textanalysis->show_forum = 0;

    if(empty($textanalysis->show_blog)) 
      $textanalysis->show_blog = 0;
      
    if(empty($textanalysis->show_journal)) 
      $textanalysis->show_journal = 0;
      
    if(empty($textanalysis->show_map)) 
      $textanalysis->show_map = 0;
    
    return $DB->insert_record("textanalysis", $textanalysis);
}


function textanalysis_update_instance($textanalysis, $id) {
    global $CFG, $DB;
    
   
    $textanalysis->timemodified = time();
    $textanalysis->id = $textanalysis->instance;
    
    if(empty($textanalysis->show_forum)) 
      $textanalysis->show_forum = 0;

    if(empty($textanalysis->show_blog)) 
      $textanalysis->show_blog = 0;
      
    if(empty($textanalysis->show_journal)) 
      $textanalysis->show_journal = 0;
      
    if(empty($textanalysis->show_map)) 
      $textanalysis->show_map = 0;

    # May have to add extra stuff in here #

    return $DB->update_record("textanalysis", $textanalysis);
    
}


function textanalysis_submit_instance($textanalysis, $id) {
    global $CFG, $DB;
}


function textanalysis_process_options($config) {
    global $CFG, $DB;

    return true;
}


function textanalysis_delete_instance($id) {
    global $CFG, $DB;

    if (! $textanalysis = $DB->get_record("textanalysis", array("id" => $id))) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (!$DB->delete_records("textanalysis", array("id"=>$textanalysis->id))) {
        $result = false;
    }

    return $result;
}


function textanalysis_user_outline($course, $user, $mod, $textanalysis) {
    return true;
}


function textanalysis_user_complete($course, $user, $mod, $textanalysis) {
    return true;
}


function textanalysis_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $DB;

    return false;  
}


function textanalysis_cron () {
    global $CFG, $DB;
    
    textanalysis_runcompareupdate ();

    return true;
}


function textanalysis_grades($textanalysisid) {
   return NULL;
}


function textanalysis_get_participants($textanalysisid) {
    global $CFG, $DB;
    return $DB->get_records_sql("SELECT * FROM ".$CFG->prefix."user");
}


function textanalysis_scale_used ($textanalysisid,$scaleid) {
    $return = false;
   
    return $return;
}


function textanalysis_wordcount ($text) {
    return str_word_count_my ($text);
}


function textanalysis_worduniquecount ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = Array ();
    
    foreach ($words as $word) {
        if (!in_array($word, $words_)) {
            $words_[] = strtolower ($word);
        }
    }

    return count ($words_);
}


function textanalysis_numberofsentences ($text, $returntext = false) {
    $text = @ereg_replace("<!--.*-->","",$text);
    $text = @ereg_replace("{.*}","",$text);
    $text = strip_tags($text);
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
    
    if (is_array($returntext)) {
        $count = count($textarrayf);
        return $count;
    }
}


function textanalysis_averagepersentence ($text, $words, $sentences) {
    $count = round($words / $sentences, 2);
    return $count;

}


function textanalysis_lexicaldensity ($text, $word, $wordunic) {
    if ($word > 0)
        $count = round(($wordunic / $word) * 100, 2);
    else
        $count = 0;
        
    return $count;
}


function textanalysis_fogindex ($text, $averagepersentence, $hardwordspersent) {
    $count = round(($averagepersentence + $hardwordspersent) * 0.4, 2);
    return $count;
}


function textanalysis_laters ($text) {
    $words  = str_word_count ($text, 1);
    $words_ = array();
    $result = array();
    
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
                if(isset($result[$i]))
                    $result[$i]++;
                else
                    $result[$i] = 1;
            }
        }
    }

    return $result;
}


function textanalysis_hardwords($text, $wordstotal) {
    $syllables = 0;
    $words = explode(' ', $text);
    for ($i = 0; $i < count($words); $i++) {
        if (textanalysis_count_syllables($words[$i]) > 2) {
            $syllables ++;
        }
    }
    
    if ($wordstotal > 0)
        $score = round(($syllables / $wordstotal) * 100, 2);
    else 
        $score = 0;

    return Array($syllables, $score);
}


function textanalysis_count_syllables($word) {
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


function textanalysis_printanalizeform($text) {
    global $CFG;
    
    $data = Array ();
    
    $text = strip_tags ($text);
    
    $data['wordcount'] = textanalysis_wordcount ($text);
    $data['worduniquecount'] = textanalysis_worduniquecount ($text);
    $data['numberofsentences'] = textanalysis_numberofsentences ($text);
    if ($data['numberofsentences'] == 0 || empty($data['numberofsentences'])) {
        $data['numberofsentences'] = 1;
    }
    $data['averagepersentence'] = textanalysis_averagepersentence ($text, $data['wordcount'], $data['numberofsentences']);
    list ($data['hardwords'], $data['hardwordspersent']) = textanalysis_hardwords ($text, $data['wordcount']);
    $data['lexicaldensity'] = textanalysis_lexicaldensity ($text, $data['wordcount'], $data['worduniquecount']);
    $data['fogindex'] = textanalysis_fogindex ($text, $data['averagepersentence'], $data['hardwordspersent']);
    $data['laters'] = textanalysis_laters ($text);
    

    echo '<p><b>Text analysis:</b></p>';
            
    echo '<div id="function-example"><table cellspacing="20"><tr><td valign="top">';
    
    echo '<table width="600"><tr>';
    echo '<td align="right">Total Word Count: </td><td> <b>' . $data['wordcount'] . '</b></td></tr><tr>';
    echo '<td align="right">Total Unique Words: </td><td> <b>' . $data['worduniquecount'] . '</b></td></tr><tr>';
    echo '<td align="right">Number of Sentences: </td><td> <b>' . $data['numberofsentences'] . '</b></td></tr><tr>';
    echo '<td align="right">Average Words per Sentence: </td><td> <b>' . $data['averagepersentence'] . '</b></td></tr><tr>';
    echo '<td align="right">Hard Words: </td><td> <b>' . $data['hardwords'] . '</b> ('.$data['hardwordspersent'].'%)' . '</td></tr><tr>';
    echo '<td align="right">Lexical Density: </td><td> <b>'.$data['lexicaldensity'].'</b>%' . '</td></tr><tr>';
    echo '<td align="right">Fog Index: </td><td> <b>'.$data['fogindex'] . '</b></td>';
    
    echo '</tr></table>';

    echo '</td><td valign="top">';
    
    echo '<table width="400">';
    
    foreach ($data['laters'] as $key => $value) {
        if ($data['wordcount'] != 0)
            $persenttage = round (($value / $data['wordcount']) * 100, 2);
        else
            $persenttage = 0;
        
        $persenttageimage = round($persenttage * 2) + 1;
        
        echo '<tr><td width="140">'.$key.' letter words </td><td width="20">'.$value.'</td><td width="240"><img src="'.$CFG->wwwroot.'/mod/textanalysis/img/bar1.gif" height="10" width="'.$persenttageimage.'"> <em>'.$persenttage.'%</em></td></tr>';
    }
    
    echo '</table>';
    
    echo '</td></tr></table></div>';
}


function textanalysis_getuserimage($userid) {
    global $CFG, $DB;
    
    if (is_file($CFG->dataroot . "/user/".$userid."/f2.jpg")) {
        $imagepath = $CFG->wwwroot . "/user/pix.php/".$userid."/f2.jpg";
    } else {
        $imagepath = $CFG->wwwroot . "/pix/u/f2.png";
    }
    
    return $imagepath;
}


function textanalysis_runcompareupdate () {
    global $CFG, $USER, $checkcountofwords, $DB;

    list($msec_main,$sec_main)=explode(chr(32),microtime());
    $begin_main=$sec_main+$msec_main;

    $lastrecord = $DB->get_record_sql ("SELECT timeoriginal FROM {$CFG->prefix}textanalysis_c_hash ORDER BY timeoriginal DESC");

    if (@!$lastrecord->timeoriginal) $lastrecord->timeoriginal = 0;

    if (textanalysis_getgallerytexts($lastrecord->timeoriginal)) {
        list($galleryposts, $postsoriginationtimestamp, $postsoriginationuserid) = textanalysis_getgallerytexts($lastrecord->timeoriginal);

        //$db->Connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass,$CFG->dbname);

        foreach ($galleryposts as $postid => $postsummary) {
            $posttext = str_replace ("?", ". ", strip_tags($postsummary).". ");
            $posttext = str_replace ("!", ". ", $posttext);
            $posttext = str_replace ("--", "", $posttext);
            $posttexttextarray = explode (".", $posttext);
            foreach ($posttexttextarray as $posttexttextarray_) {
                if (strlen($posttexttextarray_) > 6) { //crope Date, money format and other
                    $stringarray = str_word_count($posttexttextarray_, 1); 
                    unset($stringarrayclear);
                    foreach ($stringarray as $stringarray_) {
                        if (strlen($stringarray_) > $CFG->textanalysis_textword && $stringarray_ != 'br' && $stringarray_ != 'lt' && $stringarray_ != 'gt' && $stringarray_ != '-') { 
                            $stringarrayclear[] = strtolower($stringarray_);
                        }
                    }
                    if (count($stringarrayclear) >= $CFG->textanalysis_textcompare) {
                        $postdata[$postid][] = $stringarrayclear;
                    }
                }
            }
        }

        //-- -----//

        foreach ($postdata as $key => $value) {
            foreach ($value as $posttexts) {
                if (count($posttexts) >= $CFG->textanalysis_textcompare) {
                    //----
                    $needcicl = (count($posttexts) + 1) - $CFG->textanalysis_textcompare;
                    for ($i=0; $i<$needcicl; $i++) {
                        $slottext = "";
                        for ($c=0; $c<$CFG->textanalysis_textcompare; $c++) {
                            $slotkey = $c + $i;
                            $slottext .= " {$posttexts[$slotkey]}";
                        }
                        if (!in_array($slottext, $posttextscicls[$key])) {
                            $posttextscicls[$key][] = $slottext;
                        }
                    }
                }
            }
        }

        //--- -----//

        foreach ($posttextscicls as $key => $value) {
            if (!$posthashid = $DB->get_record("textanalysis_c_hash", array("recid"=>$key, "module"=>"gallery", "hash"=>md5($galleryposts[$key])))) {
                $countofexlines = 0;
                $idofexlines = array();
                foreach ($value as $postline) {
                    if ($alreadyexists = $DB->get_records_sql("SELECT recid FROM {$CFG->prefix}textanalysis_c_t_lines WHERE compare='{$postline}' and module='gallery' and recid!={$key}")) {
                        $countofexlines++;
                        foreach ($alreadyexists as $alreadyexist) {
                            $idofexlines[] = $alreadyexist->recid;
                        }
                    }

                    $addline   = new object;
                    $addline->recid= $key;
                    $addline->guserid  = $postsoriginationuserid[$key];
                    $addline->module   = 'gallery';
                    $addline->hash = md5($postline);
                    $addline->compare  = $postline;
                    $addline->time = time();
                    $addline->timeoriginal = $postsoriginationtimestamp[$key];

                    if ($addlineid = $DB->get_record("textanalysis_c_t_lines", array("recid"=>$key, "module"=>"gallery", "compare"=>$postline))) {
                        $addline->id = $addlineid->id;
                        $DB->update_record("textanalysis_c_t_lines", $addline);
                    } else {
                        $DB->insert_record("textanalysis_c_t_lines", $addline);
                    }
                }

                $hashdata = new object;
                $hashdata->recid  = $key;
                $hashdata->guserid= $postsoriginationuserid[$key];
                $hashdata->module = 'gallery';
                $hashdata->hash   = md5($galleryposts[$key]);
                if ($countofexlines != 0) {
                    if (count($value) == $countofexlines) {
                        $hashdata->persent = 0;
                    } else {
                        $hashdata->persent = ((count($value) - $countofexlines) / count($value)) * 100;
                    }
                } else {
                    $hashdata->persent = 100;
                }
                $idofexlines= array_unique($idofexlines); 
                $hashdata->compare  = implode("::", $idofexlines);
                $hashdata->time = time();
                $hashdata->timeoriginal = $postsoriginationtimestamp[$key];

                if ($rid = $DB->get_record("textanalysis_c_hash", array("recid"=>$key, "module"=>"gallery"))) {
                    $hashdata->id = $rid->id;
                    $DB->update_record("textanalysis_c_hash", $hashdata);
                } else {
                    $DB->insert_record("textanalysis_c_hash", $hashdata);
                }
            }
        }
    }


    list($msec_main,$sec_main)=explode(chr(32),microtime());
    $end_main=$sec_main+$msec_main;

    echo "Work Time: ".round($end_main-$begin_main,4)." seconds.";

    return true;
}



function textanalysis_compare_checkstring($blogstring, $wordkey, $blogsdata, $checkblogid, $checkblogstring, $checkblogword) {
    global $CFG, $USER, $checkcountofwords;
  
    if ($blogstring[$wordkey] && $blogsdata[$checkblogid][$checkblogstring][$checkblogword]) {
        if (strtolower($blogstring[$wordkey]) == strtolower($blogsdata[$checkblogid][$checkblogstring][$checkblogword])) {
            $checkcountofwords++;
            textanalysis_compare_checkstring($blogstring, $wordkey+1, $blogsdata, $checkblogid, $checkblogstring, $checkblogword+1);
        } else {
            return $checkcountofwords;
        }
    }
}
    
    
function textanalysis_getgallerytexts($time = 0) {
    global $CFG, $USER, $DB;

    if (is_file($CFG->dirroot . "/gallery2/bootstrap.inc")) {
        require_once $CFG->dirroot . "/gallery2/bootstrap.inc";
          
        mysql_connect ($storeConfig['hostname'],$storeConfig['username'],$storeConfig['password']);
        $request=mysql_select_db ($storeConfig['database']);


        $r_tb=mysql_query("SELECT * FROM {$storeConfig['tablePrefix']}Item WHERE {$storeConfig['columnPrefix']}originationTimestamp >= {$time} ORDER BY {$storeConfig['columnPrefix']}originationTimestamp");
        while ($row_table = mysql_fetch_array ($r_tb)) {
            if (strstr($row_table[$storeConfig['columnPrefix'] . 'summary'], ".")) {
                $galleryposts[$row_table[$storeConfig['columnPrefix'] . 'id']] = $row_table[$storeConfig['columnPrefix'] . 'summary'];
                $postsoriginationtimestamp[$row_table[$storeConfig['columnPrefix'] . 'id']] = $row_table[$storeConfig['columnPrefix'] . 'originationTimestamp'];
                $postsoriginationuserid[$row_table[$storeConfig['columnPrefix'] . 'id']] = $row_table[$storeConfig['columnPrefix'] . 'ownerId'];
            }
        }


        return array($galleryposts, $postsoriginationtimestamp, $postsoriginationuserid);
    } else 
        return false;
}

    
function textanalysis_make_table_headers ($titlesarray, $orderby, $sort, $link) {
    global $USER, $CFG;

    if ($orderby == "ASC") {
        $columndir    = "DESC";
        $columndirimg = "down";
    } else {
        $columndir    = "ASC";
        $columndirimg = "up";
    }

    foreach ($titlesarray as $titlesarraykey => $titlesarrayvalue) {
        if ($sort != $titlesarrayvalue) {
            $columnicon = "";
        } else {
            $iconlink   = new moodle_url("/theme/image.php", array("theme" => $CFG->theme, "image" => "t/{$columndirimg}", "rev" => $CFG->themerev));
            $columnicon = " <img src=\"{$iconlink}\" alt=\"\" />";
        }
        if (!empty($titlesarrayvalue)) {
            $table->head[] = "<a href=\"".$link."&sort=$titlesarrayvalue&orderby=$columndir\">$titlesarraykey</a>$columnicon";
        } else {
            $table->head[] = "$titlesarraykey";
        } 
    }
    
    return $table->head;
}

    
function textanalysis_sort_table_data ($data, $titlesarray, $orderby, $sort) {
    global $USER, $CFG, $DB;

    $j = 0;
    if ($sort) {
        foreach ($titlesarray as $titlesarray_) {
            if ($titlesarray_ == $sort) {
                $orderkey = $j;
            }
            $j++;
        }
    } else {
        $orderkey = 0;
    }

    $i = 0;

    foreach ($data as $datakey => $datavalue) {
        if (!is_array($datavalue[$orderkey])) {
            $key = $datavalue[$orderkey];
        } else {
            $key = $datavalue[$orderkey][1];
        }

        for ($j=0; $j < count($datavalue); $j++) {
            if (!is_array($datavalue[$j])) {
                $newarray[(string)$key][$i][$j] = $datavalue[$j];
            } else {
                $newarray[(string)$key][$i][$j] = $datavalue[$j][0];
            }
        }
        
        $i ++;
    }
    
    if (empty($orderby) || $orderby == "ASC") {
        ksort ($newarray); 
    } else {
        krsort ($newarray);
    }
    
    reset($newarray);
    
    foreach ($newarray as $newarray_) {
        foreach ($newarray_ as $newarray__) {
            $newarraynew = array ();
            foreach ($newarray__ as $newarray___) {
                $newarraynew[] = $newarray___;
            }
            $finaldata[] = $newarraynew;
        }
    }
    
    return $finaldata;
}


function str_word_count_my ($text, $rettext=false) {
    $text = @ereg_replace("<!--.*-->","",$text);
    $text = @ereg_replace("{.*}","",$text);
    $text = strip_tags($text);
    $text = @ereg_replace("[^A-z]", " ", $text);
    if (!$rettext) {
        return str_word_count($text);
    } else {
        return $text;
    }
}


