<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
define('FS_FOLDER', __DIR__);

/// cargamos las constantes de configuración
require_once 'config.php';
require_once 'base/config2.php';
require_once 'base/fs_log_manager.php';
require_once 'base/fs_file_manager.php';
require_once 'base/fs_plugin_manager.php';

echo '<h1>TEST ' . file_get_contents('VERSION') . '</h1>';
echo 'FS_FOLDER: ' . FS_FOLDER . '<br/>';
echo 'getcwd(): ' . getcwd() . '<br/>';

$folders = [
    FS_FOLDER . '/plugins',
    getcwd() . '/plugins',
    'plugins'
];

foreach ($folders as $folder) {
    echo '<h2>(1) ' . $folder . '</h2>';
    foreach (fs_file_manager::scan_folder($folder) as $file_name) {
        echo $file_name . '<br/>';
    }
}

$disabled = ['.', '..'];
foreach ($folders as $folder) {
    echo '<h3>(2) ' . $folder . '</h3>';
    foreach (scandir($folder) as $file_name) {
        if (!is_dir($folder . DIRECTORY_SEPARATOR . $file_name) || in_array($file_name, $disabled)) {
            continue;
        }

        echo $file_name . '<br/>';
    }
}

echo '<h2>.htaccess</h2><pre>' . htmlentities(file_get_contents('.htaccess')) . '<pre>';
