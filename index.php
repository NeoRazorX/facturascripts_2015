<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <neorazorx@gmail.com>
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
if ((float) substr(phpversion(), 0, 3) < 5.6) {
    /// comprobamos la versión de PHP
    die('FacturaScripts necesita PHP 5.6 o superior, y usted tiene PHP ' . phpversion());
}

if (!file_exists('config.php')) {
    /// si no hay config.php redirigimos al instalador
    header('Location: install.php');
    die('Redireccionando al instalador...');
}

define('FS_FOLDER', __DIR__);

/// ampliamos el límite de ejecución de PHP a 5 minutos
@set_time_limit(300);

/// cargamos las constantes de configuración
require_once 'config.php';
require_once 'base/config2.php';
require_once 'base/fs_controller.php';
require_once 'base/fs_edit_controller.php';
require_once 'base/fs_list_controller.php';
require_once 'base/fs_log_manager.php';
require_once 'raintpl/rain.tpl.class.php';

/**
 * Registramos la función para capturar los fatal error.
 * Información importante a la hora de depurar errores.
 */
register_shutdown_function("fatal_handler");

/// ¿Qué controlador usar?
$pagename = '';
if (filter_input(INPUT_GET, 'page')) {
    $pagename = filter_input(INPUT_GET, 'page');
} elseif (defined('FS_HOMEPAGE')) {
    $pagename = FS_HOMEPAGE;
}

$fsc_error = FALSE;
if ($pagename == '') {
    $fsc = new fs_controller();
} else {
    $class_path = find_controller($pagename);
    require_once $class_path;

    try {
        /// ¿No se ha encontrado el controlador?
        if ('base/fs_controller.php' === $class_path) {
            header("HTTP/1.0 404 Not Found");
            $fsc = new fs_controller();
        } else {
            $fsc = new $pagename();
        }
    } catch (Exception $exc) {
        echo "<h1>Error fatal</h1>"
        . "<ul>"
        . "<li><b>Código:</b> " . $exc->getCode() . "</li>"
        . "<li><b>Mensage:</b> " . $exc->getMessage() . "</li>"
        . "</ul>";
        $fsc_error = TRUE;
    }
}

/// guardamos los errores en el log
$log_manager = new fs_log_manager();
$log_manager->save();

/// redireccionamos a la página definida por el usuario
if (is_null(filter_input(INPUT_GET, 'page'))) {
    $fsc->select_default_page();
}

if ($fsc_error) {
    die();
}

if ($fsc->template) {
    /// configuramos rain.tpl
    raintpl::configure('base_url', NULL);
    raintpl::configure('tpl_dir', 'view/');
    raintpl::configure('path_replace', FALSE);

    /// ¿Se puede escribir sobre la carpeta temporal?
    if (is_writable('tmp')) {
        raintpl::configure('cache_dir', 'tmp/' . FS_TMP_NAME);
    } else {
        echo '<center>'
        . '<h1>No se puede escribir sobre la carpeta tmp de FacturaScripts</h1>'
        . '<p>Consulta la <a target="_blank" href="//facturascripts.com/comm3/index.php?page=community_item&id=351">documentaci&oacute;n</a>.</p>'
        . '</center>';
        die('<center><iframe src="//facturascripts.com/comm3/index.php?page=community_item&id=351" width="90%" height="800"></iframe></center>');
    }

    $tpl = new RainTPL();
    $tpl->assign('fsc', $fsc);

    if (filter_input(INPUT_POST, 'user')) {
        $tpl->assign('nlogin', filter_input(INPUT_POST, 'user'));
    } elseif (filter_input(INPUT_COOKIE, 'user')) {
        $tpl->assign('nlogin', filter_input(INPUT_COOKIE, 'user'));
    } else {
        $tpl->assign('nlogin', '');
    }

    $tpl->draw($fsc->template);
}

/// guardamos los errores en el log (los producidos durante la carga del template)
$log_manager->save();

/// cerramos las conexiones
$fsc->close();
