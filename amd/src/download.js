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
 * Execution downloads
 *
 * @module      tool_category_backup/search
 * @copyright   2024 Jhon Rangel Ardila <jrangelardila@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';


export const init_process = () => {
    if (data_course) {
        let i = 0;

        const download = setInterval(async function () {

            if (i === data_course.length) {
                clearInterval(download);

                let p = document.createElement('p');
                p.innerHTML = await getString('js_finish', 'tool_category_backup');
                document.getElementById('info_download').append(p);
                return;
            }

            if (data_course[i]['downloadbackup'] !== undefined) {
                window.open(data_course[i]['downloadbackup']);

                let p = document.createElement('p');
                let m1 = await getString('js_m1', 'tool_category_backup');
                let m2 = await getString('js_m2', 'tool_category_backup');
                p.innerHTML = (i + 1).toString() + m1 + data_course[i]['fullname'].toString() + m2 + data_course[i]['filename'] + '</b>';
                document.getElementById('info_download').append(p);

            } else {
                let p = document.createElement('p');
                let m3 = await getString('js_m3', 'tool_category_backup');
                let m4 = await getString('js_m4', 'tool_category_backup');
                p.innerHTML = (i + 1).toString() + m3 + data_course[i]['fullname'].toString() + m4;
                document.getElementById('info_download').append(p);
            }

            i++;

        }, 3000);
    }
};