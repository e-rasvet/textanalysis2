<?php // $Id: ,v 1.0 2009/03/20 16:41:20 serafim panov 


    $currenttab = $a;
    if (!isset($currenttab)) {
        $currenttab = 'detailed';
    }
    if (!isset($cm)) {
        $cm = $cm = get_record("course_modules", "id", $id);
    }
    if (!isset($course)) {
        $course = get_record('course', 'id', $textanalysis->course);
    }

    $tabs = array();
    $row  = array();
    $inactive = array();

    $row[] = new tabobject('detailed', "view.php?a=detailed&id=".$id, "Detailed view");
    $row[] = new tabobject('summary', "stats.php?a=summary&id=".$id, "Summary view");
    
    $tabs[] = $row;

    print_tabs($tabs, $currenttab, $inactive); 

?>