<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_mycoursesperpage', get_string('mycoursesperpage', 'block_mycourses'),
                   '', 21, PARAM_INT));
}
