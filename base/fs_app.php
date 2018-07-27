<?php
/*
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

require_once 'base/fs_cache.php';
require_once 'base/fs_core_log.php';
require_once 'base/fs_file_manager.php';
require_once 'base/fs_functions.php';

/**
 * Description of fs_app
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_app
{

    /**
     * Este objeto permite interactuar con memcache
     * @var fs_cache
     */
    protected $cache;

    /**
     * Este objeto contiene los mensajes, errores y consejos volcados por controladores,
     * modelos y base de datos.
     * @var fs_core_log 
     */
    protected $core_log;

    /**
     * Permite calcular cuanto tarda en procesarse la página.
     * @var string 
     */
    private $uptime;

    /**
     * 
     * @param string $controller_name
     */
    public function __construct($controller_name = '')
    {
        $tiempo = explode(' ', microtime());
        $this->uptime = $tiempo[1] + $tiempo[0];

        $this->cache = new fs_cache();
        $this->core_log = new fs_core_log($controller_name);
    }

    /**
     * He detectado que algunos navegadores, en algunos casos, envían varias veces la
     * misma petición del formulario. En consecuencia se crean varios modelos (asientos,
     * albaranes, etc...) con los mismos datos, es decir, duplicados.
     * Para solucionarlo añado al formulario un campo petition_id con una cadena
     * de texto aleatoria. Al llamar a esta función se comprueba si esa cadena
     * ya ha sido almacenada, de ser así devuelve TRUE, así no hay que gabar los datos,
     * si no, se almacena el ID y se devuelve FALSE.
     * @param string $pid el identificador de la petición
     * @return boolean TRUE si la petición está duplicada
     */
    protected function duplicated_petition($pid)
    {
        $ids = $this->cache->get_array('petition_ids');
        if (in_array($pid, $ids)) {
            return TRUE;
        }

        $ids[] = $pid;
        $this->cache->set('petition_ids', $ids, 300);
        return FALSE;
    }

    /**
     * Devuelve la duración de la ejecución de la página
     * @return string
     */
    public function duration()
    {
        $tiempo = explode(" ", microtime());
        return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
    }

    /**
     * Devuelve la lista de consejos
     * @return array lista de consejos
     */
    public function get_advices()
    {
        return $this->core_log->get_advices();
    }

    /**
     * Devuelve el listado de consultas SQL que se han ejecutados
     * @return array lista de consultas SQL
     */
    public function get_db_history()
    {
        return $this->core_log->get_sql_history();
    }

    /**
     * Devuelve la lista de errores
     * @return array lista de errores
     */
    public function get_errors()
    {
        return $this->core_log->get_errors();
    }

    /**
     * Busca en la lista de plugins activos, en orden inverso de prioridad
     * (el último plugin activo tiene más prioridad que el primero)
     * y nos devuelve la ruta del archivo javascript que le solicitamos.
     * Así usamos el archivo del plugin con mayor prioridad.
     * @param string $filename
     * @return string
     */
    public function get_js_location($filename)
    {
        /// necesitamos un id que se cambie al limpiar la caché
        $idcache = $this->cache->get('fs_idcache');
        if (!$idcache) {
            $idcache = $this->random_string(10);
            $this->cache->set('fs_idcache', $idcache, 86400);
        }

        foreach ($GLOBALS['plugins'] as $plugin) {
            if (file_exists('plugins/' . $plugin . '/view/js/' . $filename)) {
                return FS_PATH . 'plugins/' . $plugin . '/view/js/' . $filename . '?idcache=' . $idcache;
            }
        }

        /// si no está en los plugins estará en el núcleo
        return FS_PATH . 'view/js/' . $filename . '?idcache=' . $idcache;
    }

    /**
     * Devuelve el tamaño máximo permitido para subir archivos.
     * @return integer
     */
    public function get_max_file_upload()
    {
        return fs_get_max_file_upload();
    }

    /**
     * Devuelve la lista de mensajes
     * @return array lista de mensajes
     */
    public function get_messages()
    {
        return $this->core_log->get_messages();
    }

    /**
     * Devuelve la hora actual
     * @return string la hora en formato hora:minutos:segundos
     */
    public function hour()
    {
        return Date('H:i:s');
    }

    /**
     * Devuelve un string aleatorio de longitud $length
     * @param integer $length la longitud del string
     * @return string la cadena aleatoria
     */
    public function random_string($length = 30)
    {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    /**
     * Devuelve la fecha actual
     * @return string la fecha en formato día-mes-año
     */
    public function today()
    {
        return date('d-m-Y');
    }

    /**
     * Devuelve la versión de FacturaScripts
     * @return string versión de FacturaScripts
     */
    public function version()
    {
        return file_exists('VERSION') ? trim(file_get_contents('VERSION')) : '0';
    }
}
