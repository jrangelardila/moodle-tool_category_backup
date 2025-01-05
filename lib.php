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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
defined('MOODLE_INTERNAL') || die();

/**
 * Crear la tarea para las copias de seguridad
 *
 * @param $courseid
 * @return void
 */
function tool_category_backup_create_backup($courseid)
{
    global $USER, $DB;
    //Realizar el backup
    $user_doing_the_backup = $USER->id; // Set this to the id of your admin account
    /*$bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_COPY,  $user_doing_the_backup);*/
    $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_ASYNC, $user_doing_the_backup);
    //$bc->execute_plan();

    //$asynctask = new \core\task\asynchronous_backup_task();
    $asynctask = new  tool_category_backup\asynchronous_category_backup_class();
    $asynctask->set_blocking(false);
    $asynctask->set_custom_data(array('backupid' => $bc->get_backupid()));
    $asynctask->set_userid($USER->id);
    \core\task\manager::queue_adhoc_task($asynctask);

    $bc->save_controller();
}

/**
 * Retornar tabla con la información
 *
 * @param $categories
 * @return array|void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function tool_category_backup_get_courses($categories)
{
    global $DB, $SESSION;

    if ($categories == null) {
        if ($SESSION->user_filtering['categorys'] != null) {
            $categories = $SESSION->user_filtering['categorys'];
        } else {
            return;
        }
    }

    $SESSION->user_filtering['categorys'] = $categories;

    $sql = "SELECT id,shortname,category,fullname,summary,visible FROM {course} WHERE ";
    $count = false;
    foreach ($categories as $category) {
        if (!$count) {
            $sql = $sql . "  category='" . $category . "'";
            $count = true;
        } else {
            $sql = $sql . " OR category='" . $category . "'";
        }
    }

    $sql = $sql . " ORDER BY fullname";

    $courses = $DB->get_records_sql($sql);

    $table = new html_table();
    $table->id = 'table_data';
    $table->head = [
        get_string('number', 'tool_category_backup'),
        get_string('shortname'),
        get_string('fullname'),
        get_string('category'),
        get_string('visible', 'tool_category_backup'),
        get_string('size', 'tool_category_backup'),
        get_string('name_backup', 'tool_category_backup'),
        get_string('date_backup', 'tool_category_backup')
    ];
    $table->class = "w-100";

    $count = 1;
    $courses_validate = [];
    foreach ($courses as $course) {
        $row = new html_table_row();

        $row->cells[] = $count;

        $url = new moodle_url('/course/view.php?id=' . $course->id);
        $row->cells[] = "<a href='$url' target='_blank'>$course->shortname</a>";
        $row->cells[] = $course->fullname;
        $url = new moodle_url('/course/index.php?categoryid=' . $course->category);
        $row->cells[] = "<a href='$url' target='_blank'>" . $DB->get_record('course_categories', ['id' => $course->category], 'name')->name . "</a>";
        $row->cells[] = $course->visible == 1 ? 'Visible' : 'Oculto';

        $file = tool_category_backup_get_file_backup($course);
        if ($file) {
            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename(),
                true                     // Do not force download of the file.
            );
            $course->downloadbackup = $url . "";
            $course->filename = $file->get_filename();

            $cell = new html_table_cell("<a class='a' target='_blank' href='$url'>" . $file->get_filename() . "</a>");
            $row->cells[] = $cell;
            $row->cells[] = userdate($file->get_timemodified(), get_string('strftimedatetime', 'core_langconfig'));
            $tam_file = round($file->get_filesize() / 1024, 2);
            $info_size_file = $tam_file . " KB";
            if ($tam_file > 1024) {
                $tam_file = round($tam_file / 1024, 2);
                $info_size_file = $tam_file . " MB";
            } else if ($tam_file > 1024) {
                $tam_file = round($tam_file / 1024, 2);
                $info_size_file = $tam_file . " GB";
            }
            $row->cells[] = $info_size_file;
        } else {
            $row->cells[] = "";
            $row->cells[] = "";
            $row->cells[] = "";
        }

        $count++;
        $table->data[] = $row;
        $courses_validate[] = $course;
    }

    echo "<div class='container'>";
    echo html_writer::table($table);
    echo "</div>";

    $SESSION->user_filtering['courses_backup'] = $courses_validate;

    return $courses_validate;
}

/**
 * Retornar el archivo de copia de seguridad del curso
 *
 * @param $course
 * @return bool|stored_file
 */
function tool_category_backup_get_file_backup($course)
{
    try {
        global $DB;
        //Obtener el controller
        $context = $DB->get_record_sql("SELECT * FROM {context} WHERE contextlevel='50' AND
        instanceid= '$course->id';");

        $filename_v = "$course->shortname.mbz";
        $file_verified = $DB->get_record_sql("SELECT * FROM  {files}  WHERE  filearea='course' AND 	contextid=$context->id AND filename!='.' AND mimetype='application/vnd.moodle.backup'
                  AND  filename='$filename_v';");

        $fs = get_file_storage();

        // Prepare file record object
        $fileinfo = $file_verified; // any filename

        // Get file
        $file_get = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea,
            $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);

        return $file_get;
    } catch (Exception $e) {
        return null;
    }
}