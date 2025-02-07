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

namespace tool_category_backup\form;

use cache;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

class form_category_backup extends \moodleform
{
    /**
     * @throws \coding_exception
     */
    protected function definition()
    {
        $mform = $this->_form;

        $categories = \core_course_category::make_categories_list();

        $mform->addElement('autocomplete', 'categorys', get_string('categorys', 'tool_category_backup'), $categories, [
            'multiple' => true,
        ]);
        $mform->setType('categorys', PARAM_SEQUENCE);
        $mform->addRule('categorys', get_string('required'), 'required', null, 'client');


        $cache = cache::make('tool_category_backup', 'session');
        if ($cache->get('courses_backup')) {
            $mform->setDefault('categorys', $cache->get('courses_backup'));
        }

        $url = new moodle_url('/admin/settings.php', ['section' => 'backupgeneralsettings']);
        $formatted_url = $url->out();

        $string = get_string('url_config', 'tool_category_backup');

        $string = str_replace('{a}', $formatted_url, $string);
        $mform->addElement('html', $string);


        $this->add_action_buttons();

    }
}
