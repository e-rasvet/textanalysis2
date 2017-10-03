<?php // $Id: index.php,v 1.5 2006/08/28 16:41:20  Exp $
/********************************************************/
/*  Copyright Serafim Panov serafimpanov@gmail.com       /
/********************************************************/

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    
    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }
    
    require_course_login($course);
    
    add_to_log($course->id, "Text analysis", "view all", "index.php?id=$course->id", "");
    
    $strdisplay = get_string("modulename", "textanalysis");
    $strdisplays = get_string("modulenameplural", "textanalysis");
    //$strweek = get_string("week");
    //$strtopic = get_string("topic");

    print_header_simple("$strdisplays", "", "$strdisplays", 
                 "", "", true, "", navmenu($course)); 
                 
    if (! $displays = get_all_instances_in_course("textanalysis", $course)) {
        notice("There are no displays", "../../course/view.php?id=$course->id");
        die;
    }
    
    
    $timenow = time();
    
    echo "<br />";
    
    print_simple_box_start('center', '500', '#ffffff', 10); 

    foreach ($displays as $display) {

        echo '<a href="view.php?id='.$display->coursemodule.'">'.$display->name.'</a><br />';

    }

    print_simple_box_end();

    echo "<br />";



    print_footer($course);

    //redirect ("view.php?id=".$id);

?>