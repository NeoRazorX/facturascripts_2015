<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/// Si estas leyendo esto es porque no tienes PHP instalado !!!!!!!!!!!!!!!!!!!!

if ((float) substr(phpversion(), 0, 3) < 5.3) {
    /// comprobamos la versión de PHP
    die('FacturaScripts necesita PHP 5.3 o superior, y tú tienes PHP ' . phpversion());
} elseif (!file_exists('config.php')) {
    /// si no hay config.php redirigimos al instalador
    header('Location: install.php');
} else {
    /// cargamos las constantes de configuración
    require_once 'config.php';
    require_once 'base/config2.php';
    require_once 'base/fs_functions.php';
    require_once 'base/fs_controller.php';
    require_once 'raintpl/rain.tpl.class.php';

    if (FS_DB_HISTORY) {
        /**
         * Si está activado el historial SQL, registramos además la función para
         * capturar los fatal error. Información importante a la hora de depurar
         * errores.
         */
        register_shutdown_function("fatal_handler");
    }

    /// ¿Qué controlador usar?
    $pagename = '';
    if (filter_input(INPUT_GET, 'page')) {
        $pagename = filter_input(INPUT_GET, 'page');
    } elseif (defined('FS_HOMEPAGE')) {
        $pagename = FS_HOMEPAGE;
    }

    $fsc_error = FALSE;
    if ($pagename != '') {
        /// primero buscamos en los plugins
        $found = FALSE;
        foreach ($GLOBALS['plugins'] as $plugin) {
            if (file_exists('plugins/' . $plugin . '/controller/' . $pagename . '.php')) {
                require_once 'plugins/' . $plugin . '/controller/' . $pagename . '.php';

                try {
                    $fsc = new $pagename();
                } catch (Exception $e) {
                    echo "<h1>Error fatal</h1>"
                    . "<ul>"
                    . "<li><b>Código:</b> " . $e->getCode() . "</li>"
                    . "<li><b>Mensage:</b> " . $e->getMessage() . "</li>"
                    . "</ul>";
                    $fsc_error = TRUE;
                }

                $found = TRUE;
                break;
            }
        }

        /// si no está en los plugins, buscamos en controller/
        if (!$found) {
            if (file_exists('controller/' . $pagename . '.php')) {
                require_once 'controller/' . $pagename . '.php';

                try {
                    $fsc = new $pagename();
                } catch (Exception $e) {
                    echo "<h1>Error fatal</h1>"
                    . "<ul>"
                    . "<li><b>Código:</b> " . $e->getCode() . "</li>"
                    . "<li><b>Mensage:</b> " . $e->getMessage() . "</li>"
                    . "</ul>";
                    $fsc_error = TRUE;
                }
            } else {
                header("HTTP/1.0 404 Not Found");
                $fsc = new fs_controller();
            }
        }
    } else {
        $fsc = new fs_controller();
    }

    if (is_null(filter_input(INPUT_GET, 'page'))) {
        /// redireccionamos a la página definida por el usuario
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
            . '<p>Consulta la <a target="_blank" href="//www.facturascripts.com/comm3/index.php?page=community_item&id=351">documentaci&oacute;n</a>.</p>'
            . '</center>';
            die('<center><iframe src="//www.facturascripts.com/comm3/index.php?page=community_item&id=351" width="90%" height="800"></iframe></center>');
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

    $fsc->close();
}
