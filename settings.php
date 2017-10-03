<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/textanalysis/lib.php');

    $settings->add(new admin_setting_configtext('textanalysis_textcompare',
            get_string('config_textcompare', 'textanalysis'), get_string('config_textcompare_descr', 'textanalysis'), 5, PARAM_INT));
    // Converting url
    $settings->add(new admin_setting_configtext('textanalysis_textword',
            get_string('config_textword', 'textanalysis'), get_string('config_textword_descr', 'textanalysis'), 0, PARAM_INT));
}
