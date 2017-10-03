<?php // $Id: index.php,v 1.35.2.3 2008/02/25 01:25:43 scyrma Exp $

/**
 * file index.php
 * index page to view blogs. if no blog is specified then site wide entries are shown
 * if a blog id is specified then the latest entries from that blog are shown
 */

require_once('../../config.php');
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->libdir .'/blocklib.php');

$id           = optional_param('id', 0, PARAM_INT);
$module       = optional_param('module', NULL, PARAM_CLEAN);

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

add_to_log($course->id, "textanalysis", "view", "compare.php?id=$cm->id", "$textanalysis->id");

/// Print the page header
$strtextanalysiss = get_string('modulenameplural', 'textanalysis');
$strtextanalysis  = get_string('modulename', 'textanalysis');

$PAGE->set_url('/mod/textanalysis/compare.php', array('id' => $id));

$title = $course->shortname . ': ' . format_string($textanalysis->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/// Print the main part of the page

require_once ('tabs.php');

$hashdata = $DB->get_record("textanalysis_c_hash", array("recid"=>$id, "module"=>"gallery"));
       
if ($hashdata->persent != 100) {
     $compareblogs = explode("::", $hashdata->compare);
}

$compareblogs[] = $id;
        
$posts = textanalysis_single_getgallerytexts($compareblogs);

foreach ($posts as $postid => $postsummary) {
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


unset($checkarray);

foreach ($postdata as $blogid => $blogsdata_) {
    foreach ($blogsdata_ as $sentens => $words) {
        if (count($words) >= $CFG->textanalysis_textcompare) {
            for ($i=0; $i <= count($words); $i++) {
                for ($j=$i; $j < ($i + $CFG->textanalysis_textcompare); $j++) {
                    if ($words[$i + $CFG->textanalysis_textcompare - 1]) {
                        $checkarray[$blogid][$sentens][$i] .= $words[$j]." ";
                    }
                }
            }
        }
    }
}


foreach ($checkarray as $key => $value) {  // $key - id, $value - sentense
    foreach ($value as $sentenstarget => $wordstarget) {  // $sentenstarget - id of sentens  $wordstarget - array of lines
        foreach ($wordstarget as $wordstarget_) {    // $wordstarget_  - earch lines
            if ($key == $id) {
                $etalon[] = strtolower($wordstarget_);
            }
        }
    }
}

foreach ($checkarray as $key => $value) {  // $key - id, $value - sentense
    foreach ($value as $sentenstarget => $wordstarget) {  // $sentenstarget - id of sentens  $wordstarget - array of lines
        foreach ($wordstarget as $wordstarget_) {    // $wordstarget_  - earch lines
            if ($key != $id) {
                if (in_array(strtolower($wordstarget_), $etalon)) {
                  if (!in_array(strtolower($wordstarget_), $needmark)) {
                    $needmark[] = trim(strtolower($wordstarget_));
                  }
                }
            }
        }
    }
}

foreach ($posts as $postid => $poststext) {
    foreach ($needmark as $needmark_) {
        $simvols = array(",");
        unset($value3add);
        $value3add[] = $needmark_;
        foreach ($simvols as $simvol) {
            $wordsarrayofvalue3 = explode(" ", $needmark_);
            for ($k=0; $k < ($CFG->textanalysis_textcompare - 1); $k++) {
                $phrase = "";
                foreach ($wordsarrayofvalue3 as $keyvalue3 => $wordsarrayofvalue3_) {
                    $phrase .= $wordsarrayofvalue3_;
                    if ($keyvalue3 == $k) {
                        $phrase .= $simvol;
                    }
                    $phrase .= " ";
                }
                $value3add[] = substr($phrase, 0, -1);
            }
        }
        foreach ($value3add as $value3add_) {
            $poststext = str_ireplace($value3add_, "<b>{$needmark_}</b>", $poststext);
        }
    }
    $newposts[$postid] = $poststext;
}

textanalysis_compare_print($newposts[$id], $id);

unset($newposts[$id]);

foreach ($newposts as $key => $newpost) {
    textanalysis_compare_print($newpost, $key);
}


echo $OUTPUT->footer();



function textanalysis_compare_print($post, $idofpost) {
    global $CFG, $DB;
    
    $hashdata = $DB->get_record("textanalysis_c_hash", array("recid"=>$idofpost, "module"=>"gallery"));
    $userdata = textanalysis_get_gallery_user_name($hashdata->guserid);
    $user = $DB->get_record('user',array('username'=>$userdata));

    echo '<table cellspacing="0" class="forumpost blogpost" width="100%">';
    echo '<tr class="header"><td class="picture left">';
    print_user_picture($user, SITEID, $user->picture);
    echo '</td>';
    echo '<td class="topic starter"><a href="'.$CFG->wwwroot.'/courses/user/view.php?id='.$user->id.'">'.fullname($user).'</a>';
    echo " :: <a href=\"{$CFG->wwwroot}/gallery2/main.php?g2_itemId={$idofpost}\" target=\"_blank\">Go to gallery image</a>";
    echo '</td></tr>';
    echo '<tr><td class="left side">';
    echo '</td><td class="content">'."\n";
    echo str_replace("</b><b>", " ", format_text($post));
    echo '<div class="commands">';
    echo '</div>';
    echo '</td></tr></table>'."\n\n";
}


function textanalysis_single_getgallerytexts($ids) {
    global $CFG, $USER, $storeConfig;
          
    return "no found";
}
    
    
function textanalysis_get_gallery_user_name($id) {
    global $CFG, $USER, $storeConfig;
    return "no found";
}

