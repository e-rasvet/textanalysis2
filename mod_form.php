<?php //$Id: mod_form.php,v 1.2 2012/03/10 22:00:00 Serafim Panov Exp $

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');  
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');


class mod_textanalysis_mod_form extends moodleform_mod {
    function definition() {
        global $COURSE, $CFG, $form, $USER;
        $mform    =& $this->_form;

        $fmstime = time();
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->add_intro_editor(true, get_string('intro', 'textanalysis'));
        
        /*
        $showitemsa = array( 'all' => 'Show All', 'forum' => 'Forum', 'blog' => 'Blog', 'journal' => 'Text assignment', 'map' => 'Map');

        $mform->addElement('select', 'showitems', 'Show', $showitemsa);
        $mform->setDefault('showitems', 'all');
        */
        
        $mform->addElement('header', 'general', 'Show');
        $mform->addElement('checkbox', 'show_forum', 'Forum');
        $mform->addElement('checkbox', 'show_blog', 'Blog');
        $mform->addElement('checkbox', 'show_journal', 'Text assignment');
        $mform->addElement('checkbox', 'show_map', 'Map');
        
//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }
}
