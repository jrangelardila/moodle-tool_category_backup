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

use backup_controller;
use backup_plan;
use backup_plan_exception;

defined('MOODLE_INTERNAL') || die;

class backup_plan_category_backup extends backup_plan
{
    public function __construct($controller)
    {
        parent::__construct($controller);
        if (!$controller instanceof backup_controller) {
            throw new backup_plan_exception('wrong_backup_controller_specified');
        }
        $backuptempdir = make_backup_temp_directory($controller->get_backupid());
        $this->controller = $controller;
        $this->basepath = $backuptempdir . '/' . $controller->get_backupid();
        $this->excludingdactivities = false;
    }
}