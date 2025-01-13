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
namespace tool_category_backup\local;


use async_helper;
use core\task\asynchronous_backup_task;
use Exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

class asynchronous_category_backup extends asynchronous_backup_task
{

    /**
     * Run the adhoc task and preform the backup.
     */
    public function execute()
    {
        global $DB;

        try {
            $started = time();

            $backupid = $this->get_custom_data()->backupid;
            $backuprecord = $DB->get_record('backup_controllers', array('backupid' => $backupid), 'id, controller', MUST_EXIST);
            mtrace('Processing asynchronous backup for backup: ' . $backupid);


            // Get the backup controller by backup id. If controller is invalid, this task can never complete.
            if ($backuprecord->controller === '') {
                mtrace('Bad backup controller status, invalid controller, ending backup execution.');
                return;
            }
            $bc = \backup_controller::load_controller($backupid);
            $bc->set_progress(new \core\progress\db_updater($backuprecord->id, 'backup_controllers', 'progress'));

            // Do some preflight checks on the backup.
            $status = $bc->get_status();
            $execution = $bc->get_execution();

            // Check that the backup is in the correct status and
            // that is set for asynchronous execution.
            if ($status == \backup::STATUS_AWAITING && $execution == \backup::EXECUTION_DELAYED) {
                // Execute the backup.
                $bc->execute_plan();

                // Send message to user if enabled.
                $messageenabled = (bool)get_config('backup', 'backup_async_message_users');
                if ($messageenabled && $bc->get_status() == \backup::STATUS_FINISHED_OK) {
                    $asynchelper = new async_helper('backup', $backupid);
                    $asynchelper->send_message();
                }

            } else {
                // If status isn't 700, it means the process has failed.
                // Retrying isn't going to fix it, so marked operation as failed.
                $bc->set_status(\backup::STATUS_FINISHED_ERR);
                mtrace('Bad backup controller status, is: ' . $status . ' should be 700, marking job as failed.');

            }

            // Cleanup.
            $bc->destroy();

            $duration = time() - $started;
            mtrace('Backup completed in: ' . $duration . ' seconds');

            $backuprecord = $DB->get_record('backup_controllers', array('backupid' => $backupid));
            //Renombrar el archivo
            $this->tool_category_backup_update_file($backuprecord->itemid);
        } catch (Exception $e) {
            mtrace($e->getMessage());
        }

    }


    /**
     * Actualizar y borrar el archivo anterior si es necesario
     *
     * @param $courseid
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     */
    function tool_category_backup_update_file($courseid)
    {
        global $DB;

        $course = $DB->get_record("course", array("id" => $courseid));


        //Obtener el controller
        $context = $DB->get_record_sql("SELECT * FROM {context} WHERE contextlevel='50' AND
        instanceid= '$courseid';");

        $filename_v = "$course->shortname.mbz";
        $file_verified = $DB->get_record_sql(
            "SELECT * FROM  {files}  WHERE  filearea='course' AND 	contextid=$context->id AND filename!='.' AND mimetype='application/vnd.moodle.backup'
                  AND  filename='$filename_v';");

        $fs = get_file_storage();
        // Prepare file record object
        $fileinfo = $file_verified; // any filename

        // Get file
        $file_get = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea,
            $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);

        // Si el archivo existe eliminarlo
        //mtrace($file_get->get_timecreated());
        if ($file_get) {
            mtrace('Deleting the previous backup....');
            $file_get->delete();
        } else {
            // file doesn't exist - do something
            mtrace('No previous backup exists....');
        }

        //Renombrar el archivo

        //El filename, debe ser el referenciado, que es al momento de crear las copias en backup/moodle2/backup_root_task.class.php
        $file = $DB->get_record_sql("SELECT * FROM  {files}  WHERE filename='backup.mbz'
    AND filearea='course' AND 	contextid=$context->id AND filename!='.' AND  mimetype='application/vnd.moodle.backup';");
        $fs = get_file_storage();
        $fileinfo = $file;
        $file_get = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea,
            $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);
        $file_get->rename($fileinfo->filepath, $filename_v);
    }
}