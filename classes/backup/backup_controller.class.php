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
namespace tool_category_backup\backup;

use backup;
use backup_check;
use backup_controller as backup_controller_parent;
use backup_controller_dbops;
use backup_factory;
use output_controller;

defined('MOODLE_INTERNAL') || die;

class backup_controller extends backup_controller_parent
{

    /**
     * @param $type
     * @param $id
     * @param $format
     * @param $interactive
     * @param $mode
     * @param $userid
     * @param $releasesession
     * @throws \backup_controller_exception
     */
    public function __construct($type, $id, $format, $interactive, $mode, $userid, $releasesession = backup::RELEASESESSION_NO)
    {
        $this->type = $type;
        $this->id   = $id;
        $this->courseid = backup_controller_dbops::get_courseid_from_type_id($this->type, $this->id);
        $this->format = $format;
        $this->interactive = $interactive;
        $this->mode = $mode;
        $this->userid = $userid;
        $this->releasesession = $releasesession;

        // Apply some defaults
        $this->operation = backup::OPERATION_BACKUP;
        $this->executiontime = 0;
        $this->checksum = '';

        // Set execution based on backup mode.
        if ($mode == backup::MODE_ASYNC || $mode == backup::MODE_COPY) {
            $this->execution = backup::EXECUTION_DELAYED;
        } else {
            $this->execution = backup::EXECUTION_INMEDIATE;
        }

        // Apply current backup version and release if necessary
        backup_controller_dbops::apply_version_and_release();

        // Check format and type are correct
        backup_check::check_format_and_type($this->format, $this->type);

        // Check id is correct
        backup_check::check_id($this->type, $this->id);

        // Check user is correct
        backup_check::check_user($this->userid);

        // Calculate unique $backupid
        $this->calculate_backupid();

        // Default logger chain (based on interactive/execution)
        $this->logger = backup_factory::get_logger_chain($this->interactive, $this->execution, $this->backupid);

        // By default there is no progress reporter. Interfaces that wish to
        // display progress must set it.
        $this->progress = new \core\progress\none();

        // Instantiate the output_controller singleton and active it if interactive and immediate.
        $oc = output_controller::get_instance();
        if ($this->interactive == backup::INTERACTIVE_YES && $this->execution == backup::EXECUTION_INMEDIATE) {
            $oc->set_active(true);
        }

        $this->log('instantiating backup controller', backup::LOG_INFO, $this->backupid);

        // Default destination chain (based on type/mode/execution)
        $this->destination = backup_factory::get_destination_chain($this->type, $this->id, $this->mode, $this->execution);

        // Set initial status
        $this->set_status(backup::STATUS_CREATED);

        // Load plan (based on type/format)
        $this->load_plan();

        // Apply all default settings (based on type/format/mode)
        $this->apply_defaults();

        // Perform all initial security checks and apply (2nd param) them to settings automatically
        backup_check::check_security($this, true);

        // Set status based on interactivity
        if ($this->interactive == backup::INTERACTIVE_YES) {
            $this->set_status(backup::STATUS_SETTING_UI);
        } else {
            $this->set_status(backup::STATUS_AWAITING);
        }
    }

    /**
     * @return void
     * @throws \backup_plan_exception
     */
    protected function load_plan() {
        $this->log('loading controller plan', backup::LOG_DEBUG);
        $this->plan = new backup_plan_category_backup($this);
        $this->plan->build(); // Build plan for this controller
        $this->set_status(backup::STATUS_PLANNED);
    }


}