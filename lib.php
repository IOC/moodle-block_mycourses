<?php

//  My courses block for Moodle
//  Copyright © 2012  Institut Obert de Catalunya
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/admin/tool/siteperf/lib.php');

$overview = optional_param('overview', false, PARAM_BOOL);

if ($overview) {

    $avgtime = get_avg_perf();

    if ($avgtime <= $CFG->block_mycoursesoverview) {
        header('Content-type: application/json; charset=utf-8');

        $strmymoodle = get_string('myhome');

        if (!empty($USER->id)) {
            $userid = $USER->id;  // Owner of the page
            $context = context_user::instance($USER->id);
            $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
            $header = "$SITE->shortname: $strmymoodle";

            $PAGE->set_context($context);
            $courses = enrol_get_my_courses();
            $site = get_site();
            if (array_key_exists($site->id, $courses)) {
                unset($courses[$site->id]);
            }
            foreach ($courses as $c) {
                if (isset($USER->lastcourseaccess[$c->id])) {
                    $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
                } else {
                    $courses[$c->id]->lastaccess = 0;
                }
            }
            if (empty($courses)) {
                $output = $OUTPUT->box(get_string('nocourses','my'), 'center');
            } else {
                $output = print_mycourses_overview($courses, true);
            }
            echo json_encode(array('html' => $output));
        }
    } else {
        echo json_encode(array('overload' => '1'));
    }
}


function print_mycourses_overview($courses, $full=false) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $output = '';
    $visible_courses = array();
    foreach ($courses as $id => $course) {
        if ($course->visible) {
            $visible_courses[$id] = $course;
        }
    }

    $htmlarray = array();
    if ($full) {
        if ($modules = $DB->get_records('modules')) {
            foreach ($modules as $mod) {
                if (file_exists($CFG->dirroot .'/mod/'.$mod->name.'/lib.php')) {
                    include_once($CFG->dirroot .'/mod/'.$mod->name.'/lib.php');
                    $fname = $mod->name.'_print_overview';
                    if (function_exists($fname)) {
                        $fname($visible_courses,$htmlarray);
                    }
                }
            }
        }
    }

    $cat_names = coursecat::make_categories_list();
    $cat_courses = array();
    foreach ($courses as $course) {
        $parent = $course->category;
        if ($aux = coursecat::get($parent, MUST_EXIST, true)->get_parents()) {
            $parent = $aux[0];
        }
        $cat_courses[$parent][$course->id] = $course;
    }

    foreach ($cat_courses as $category => $courses) {
        $output .= html_writer::start_tag('div', array('class' => 'categorybox'));
        $output .= $OUTPUT->heading($cat_names[$category], 3);
        foreach ($courses as $course) {
            $fullname = format_string($course->fullname, true, array('context' => context_course::instance($course->id)));
            $attributes = array('title' => s($fullname));
            if (empty($course->visible)) {
                $attributes['class'] = 'dimmed';
            }
            $show_overview = '';
            if ($course->visible){
                if ($full) {
                    if(array_key_exists($course->id, $htmlarray)) {
                        if (count($htmlarray[$course->id]) > 0) {
                            foreach (array_keys($htmlarray[$course->id]) as $mod) {
                                $modname = get_string('modulenameplural', $mod);
                                $show_overview .= html_writer::start_tag('a', array('title' => $modname,
                                        'id' => 'overview-'. $course->id .'-'.$mod.'-link',
                                        'class' => 'overview-link',
                                        'href' => '#'));
                                $show_overview .= html_writer::empty_tag('img', array('title' => $modname,
                                        'class' => 'icon',
                                        'src' =>  $OUTPUT->image_url('icon', $mod)));
                                $show_overview .= html_writer::end_tag('a');
                            }
                        }
                    }
                }else{
                    $show_overview = '<img class="overview-loading"'
                    . ' src="'. $OUTPUT->image_url('i/ajaxloader').'"'
                    . ' style="display: none" alt="" />';
                }
            }
            $output .= $OUTPUT->box_start('coursebox');
            $output .= $OUTPUT->heading(html_writer::link(
                    new moodle_url('/course/view.php', array('id' => $course->id)), $fullname, $attributes).$show_overview, 3);
            if (array_key_exists($course->id,$htmlarray)) {
                foreach ($htmlarray[$course->id] as $modname => $html) {
                    $output .= html_writer::start_tag('div', array('id' => 'overview-'. $course->id .'-'.$modname,
                            'class' => 'course-overview'));
                    $output .= $html;
                    $output .= html_writer::end_tag('div');
                }
            }
            $output .= $OUTPUT->box_end();
        }
        $output .= html_writer::end_tag('div');
    }
    return $output;
}

function get_avg_perf() {

    if (class_exists('tool_siteperf_stats')) {
        $stats = new tool_siteperf_stats();

        $now = time();
        $params = array(
            'year' => date('o', $now),
            'week' => date('W', $now),
            'day'  => date('w', $now),
            'hour' => date('G', $now)
        );

        $data = $stats->fetch($params['year'], $params['week'], $params['day'], $params['hour']);

        $avgtime = (isset($data->time) ? $data->time : 0);

        return $avgtime;
    }
    return 0;
}
