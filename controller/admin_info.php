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

/**
 * Controlador de admin -> información del sistema.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_info extends fs_list_controller
{

    public $allow_delete;
    public $b_alerta;
    public $b_controlador;
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

    protected function create_tabs()
    {
        $this->add_tab('logs', 'Historal', 'fs_logs', [
            'fecha' => 'datetime',
            'usuario' => 'text',
            'tipo' => 'text',
            'detalle' => 'text',
            'ip' => 'text',
            'controlador' => 'text',
            ], 'fa-book');
        $this->add_search_columns('logs', ['usuario', 'tipo', 'detalle', 'ip', 'controlador']);
        $this->add_sort_option('logs', ['fecha'], 2);

        /// cargamos una plantilla propia para la parte de arriba
        $this->template_top = 'block/admin_info_top';
    }

    protected function private_core()
    {
        parent::private_core();

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
            fs_file_manager::clear_raintpl_cache();
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
}
