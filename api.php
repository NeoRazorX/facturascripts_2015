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
require_once 'base/fs_core_log.php';
require_once 'base/fs_db2.php';
$db = new fs_db2();

require_once 'base/fs_extended_model.php';
require_once 'base/fs_log_manager.php';
require_once 'base/fs_api.php';
require_all_models();

$db->connect();

if ($db->connected()) {
    $api = new fs_api();
    echo $api->run();
} else {
    echo 'ERROR al conectar a la base de datos';
}

/// guardamos los errores en el log
$log_manager = new fs_log_manager();
$log_manager->save();

$db->close();
