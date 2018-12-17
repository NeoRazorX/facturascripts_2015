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

    private $fsvar;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Información del sistema', 'admin', TRUE, TRUE);
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

    public function php_version()
    {
        return phpversion();
    }

    protected function create_tabs()
    {
        /// pestaña historial
        $this->add_tab('logs', 'Historal', 'fs_logs', 'fa-book');
        $this->add_search_columns('logs', ['usuario', 'tipo', 'detalle', 'ip', 'controlador']);
        $this->add_sort_option('logs', ['fecha'], 2);
        $this->add_button('logs', 'Borrar', $this->url() . '&action=remove-all', 'fa-trash', 'btn-danger');

        /// filtros
        $tipos = $this->sql_distinct('fs_logs', 'tipo');
        $this->add_filter_select('logs', 'tipo', 'tipo', $tipos);
        $this->add_filter_date('logs', 'fecha', 'desde', '>=');
        $this->add_filter_date('logs', 'fecha', 'hasta', '<=');
        $this->add_filter_checkbox('logs', 'alerta', 'alerta');

        /// decoración
        $this->decoration->add_column('logs', 'fecha', 'datetime');
        $this->decoration->add_column('logs', 'alerta', 'bool');
        $this->decoration->add_column('logs', 'usuario');
        $this->decoration->add_column('logs', 'tipo');
        $this->decoration->add_column('logs', 'detalle');
        $this->decoration->add_column('logs', 'ip');
        $this->decoration->add_column('logs', 'controlador', 'string', 'página', 'text-right', 'index.php?page=');
        $this->decoration->add_row_option('logs', 'alerta', true, 'danger');
        $this->decoration->add_row_option('logs', 'tipo', 'error', 'danger');
        $this->decoration->add_row_option('logs', 'tipo', 'msg', 'success');

        /// cargamos una plantilla propia para la parte de arriba
        $this->template_top = 'block/admin_info_top';
    }

    protected function exec_previous_action($action)
    {
        switch ($action) {
            case 'remove-all':
                return $this->remove_all_action();

            default:
                return parent::exec_previous_action($action);
        }
    }

    protected function private_core()
    {
        parent::private_core();

        /**
         * Cargamos las variables del cron
         */
        $this->fsvar = new fs_var();
        $cron_vars = $this->fsvar->array_get(
            [
                'cron_exists' => FALSE,
                'cron_lock' => FALSE,
                'cron_error' => FALSE
            ]
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
                . ' <a href="https://beta.facturascripts.com/doc/2/configuracion/en-cron" target="_blank">cron</a>,'
                . ' te perderás algunas características interesantes de FacturaScripts.');
        } else if ($cron_vars['cron_error']) {
            $this->new_error_msg('Parece que ha habido un error con el cron. Haz clic <a href="' . $this->url()
                . '&fix=TRUE">aquí</a> para corregirlo.');
        } else if ($cron_vars['cron_lock']) {
            $this->new_advice('Se está ejecutando el cron.');
        }
    }

    protected function remove_all_action()
    {
        $sql = "DELETE FROM fs_logs;";
        if ($this->db->exec($sql)) {
            $this->new_message('Historial borrado correctamente.', true);
        }

        return true;
    }
}
