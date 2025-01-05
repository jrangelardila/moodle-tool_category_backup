<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     tool_category_backup
 * @copyright   2024 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use tool_category_backup\form\form_category_backup;

require_once '../../../config.php';
require 'lib.php';
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/category_backup/index.php'));
$PAGE->set_title(get_string('pluginname', 'tool_category_backup'));
$PAGE->set_heading(get_string('pluginname', 'tool_category_backup'));
$PAGE->set_pagelayout('standard');

$PAGE->requires->jquery();
$PAGE->navbar->add(get_string('pluginname', 'tool_category_backup'), new moodle_url('/admin/tool/category_backup/index.php'));


require_login();
$sitecontext = context_system::instance();
if (!has_capability('tool/backup_category:execute', $sitecontext)) {
    print_error('nopermissions', 'tool_category_backup');
}

echo $OUTPUT->header();

$form = new form_category_backup();
$form->display();
$courses = tool_category_backup_get_courses($form->get_data()->categorys);
if (optional_param("execute", '', PARAM_BOOL)) {
    foreach ($SESSION->user_filtering['courses_backup'] as $course) {
        tool_category_backup_create_backup($course->id);
    }
    echo get_string('generate_task_success', 'tool_category_backup');
    echo html_writer::tag("a", get_string('back', 'tool_category_backup'),
        array("class" => "btn btn-primary m-3", "href" => "index.php"));

    $SESSION->user_filtering['courses_backup'] = null;
} else if (optional_param("download", '', PARAM_BOOL)) {

    echo "<div id='info_download'></div>";

    $json_data = json_encode($courses);
    echo "<script>let data_course=$json_data;
    document.getElementById('table_data').style.display='none';
</script> ";
    echo html_writer::tag("a", get_string('back', 'tool_category_backup'), array("class" => "btn btn-primary m-3", "href" => "index.php"));
    $PAGE->requires->js_call_amd('tool_category_backup/download', 'init_process');

} else {
    if ($courses) {
        echo html_writer::tag("a", get_string('generate_task', 'tool_category_backup'), array("class" => "btn btn-primary m-3", "href" => "index.php?execute=true"));
        echo html_writer::tag("a", get_string('download_backups', 'tool_category_backup'), array("class" => "btn btn-primary m-3", "href" => "index.php?download=true"));
        echo html_writer::tag("a", get_string('back', 'tool_category_backup'), array("class" => "btn btn-primary m-3", "href" => "index.php"));
    }
}
echo $OUTPUT->footer();

