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
$string['pluginname'] = 'Copias de seguridad de una categoría';
$string['backup_category:execute'] = 'Ejecutar copias de seguridad de una categoría';
$string['nopermissions'] = 'Usted no tiene permisos para ver esta página';
$string['back'] = 'Regresar';
$string['categorys'] = 'Categorías';
$string['number'] = 'Número';
$string['size'] = 'Tamaño';
$string['name_backup'] = 'Nombre de la copia de seguridad';
$string['date_backup'] = 'Fecha de ejecución';
$string['visible'] = 'Visible';
$string['generate_task'] = 'Generar tareas';
$string['generate_task_success'] = 'Tareas generadas correctamente<br>';
$string['url_config'] = "<hr>
La configuración de las copias se regirá por lo indicado en la configuración de Moodle en
        <b><a href='{a}' target='_blank'>Configuración por defecto de la copia de seguridad</a></b>
        <hr>";
$string['js_finish'] = '<b><br><br> Proceso finalizado....</b>';
$string['js_m1'] = '. Descarga finalizada para el curso: <b>';
$string['js_m2'] = '</b>, backup llamado <b>';
$string['js_m3'] = '. El curso: <b>';
$string['js_m4'] = '</b> no cuenta con backup';
$string['download_backups'] = 'Descargar copias de seguridad';