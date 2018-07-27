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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Controlador de admin -> información del sistema.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_info extends fs_controller
{

    public $allow_delete;
    public $b_alerta;
    public $b_desde;
    public $b_detalle;
    public $b_hasta;
    public $b_ip;
    public $b_tipo;
    public $b_usuario;
    public $db_tables;
    private $fsvar;
    public $modulos_eneboo;
    public $resultados;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Información del sistema', 'admin', TRUE, TRUE);
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->admin;

        /**
         * Cargamos las variables del cron
         */
        $this->fsvar = new fs_var();
        $cron_vars = $this->fsvar->array_get(
            array(
                'cron_exists' => FALSE,
                'cron_lock' => FALSE,
                'cron_error' => FALSE)
        );

        if (isset($_GET['fix'])) {
            $cron_vars['cron_error'] = FALSE;
            $cron_vars['cron_lock'] = FALSE;
            $this->fsvar->array_save($cron_vars);
        } else if (isset($_GET['clean_cache'])) {
            /// borramos los archivos php del directorio tmp
            foreach (scandir(getcwd() . '/tmp/' . FS_TMP_NAME) as $f) {
                if (substr($f, -4) == '.php') {
                    unlink('tmp/' . FS_TMP_NAME . $f);
                }
            }

            if ($this->cache->clean()) {
                $this->new_message("Cache limpiada correctamente.");
            }
        } else if (!$cron_vars['cron_exists']) {
            $this->new_advice('Nunca se ha ejecutado el'
                . ' <a href="https://www.facturascripts.com/documentacion/configuracion/facturascripts-necesita-de-un-proceso-cron-para-ciertas-920.html" target="_blank">cron</a>,'
                . ' te perderás algunas características interesantes de FacturaScripts.');
        } else if ($cron_vars['cron_error']) {
            $this->new_error_msg('Parece que ha habido un error con el cron. Haz clic <a href="' . $this->url()
                . '&fix=TRUE">aquí</a> para corregirlo.');
        } else if ($cron_vars['cron_lock']) {
            $this->new_advice('Se está ejecutando el cron.');
        }

        $this->ini_filters();
        $this->buscar_en_log();
        $this->get_db_tables();
        $this->modulos_eneboo();
    }

    private function ini_filters()
    {
        $this->b_alerta = fs_filter_input_req('b_alerta');
        $this->b_desde = '';
        $this->b_detalle = '';
        $this->b_hasta = '';
        $this->b_ip = '';
        $this->b_tipo = '';
        $this->b_usuario = '';

        if (fs_filter_input_req('b_desde') !== NULL) {
            $this->b_desde = fs_filter_input_req('b_desde');
            $this->b_detalle = fs_filter_input_req('b_detalle');
            $this->b_hasta = fs_filter_input_req('b_hasta');
            $this->b_tipo = fs_filter_input_req('b_tipo');
            $this->b_usuario = fs_filter_input_req('b_usuario');
        }

        if (fs_filter_input_req('b_ip')) {
            $this->b_ip = (string) fs_filter_input_req('b_ip');
        }

        /// forzamos la creación de la tabla, si todavía no existe
        new fs_log();
    }

    public function php_version()
    {
        return phpversion();
    }

    public function cache_version()
    {
        return $this->cache->version();
    }

    public function fs_db_name()
    {
        return FS_DB_NAME;
    }

    public function fs_db_version()
    {
        return $this->db->version();
    }

    public function get_locks()
    {
        return $this->db->get_locks();
    }

    public function get_db_tables()
    {
        $this->db_tables = $this->db->list_tables();
    }

    private function buscar_en_log()
    {
        $this->resultados = [];
        $sql = "SELECT * FROM fs_logs WHERE 1=1";

        if ($this->b_usuario != '') {
            $sql .= ' AND usuario = ' . $this->empresa->var2str($this->b_usuario);
        }

        if ($this->b_tipo != '') {
            $sql .= ' AND tipo = ' . $this->empresa->var2str($this->b_tipo);
        }

        if ($this->b_alerta != '') {
            $sql .= ' AND alerta';
        }

        if ($this->b_detalle != '') {
            $sql .= " AND lower(detalle) LIKE '%" . $this->empresa->no_html(mb_strtolower($this->b_detalle, 'UTF8')) . "%'";
        }

        if ($this->b_ip != '') {
            $sql .= " AND ip LIKE '" . $this->empresa->no_html($this->b_ip) . "%'";
        }

        if ($this->b_desde != '') {
            $sql .= ' AND fecha >= ' . $this->empresa->var2str($this->b_desde);
        }

        if ($this->b_hasta != '') {
            $sql .= ' AND fecha <= ' . $this->empresa->var2str($this->b_hasta);
        }

        $sql .= ' ORDER BY fecha DESC';

        $data = $this->db->select_limit($sql, 500, 0);
        if ($data) {
            foreach ($data as $d) {
                $this->resultados[] = new fs_log($d);
            }
        }
    }

    private function modulos_eneboo()
    {
        $this->modulos_eneboo = [];

        if ($this->db->table_exists('flmodules')) {
            $data = $this->db->select("SELECT * FROM flmodules ORDER BY idarea ASC, descripcion ASC;");
            if ($data) {
                foreach ($data as $d) {
                    $this->modulos_eneboo[] = $d;
                }
            }
        }
    }
}
