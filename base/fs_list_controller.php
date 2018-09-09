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
require_once 'base/fs_list_decoration.php';
require_once 'base/fs_list_filter_checkbox.php';
require_once 'base/fs_list_filter_date.php';
require_once 'base/fs_list_filter_select.php';

/**
 * Controlador específico para listados.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class fs_list_controller extends fs_controller
{

    /**
     *
     * @var string
     */
    public $active_tab = '';

    /**
     * TRUE si el usuario tiene permisos para eliminar en la página.
     *
     * @var boolean 
     */
    public $allow_delete;

    /**
     *
     * @var fs_list_decoration
     */
    public $decoration;

    /**
     *
     * @var int
     */
    public $offset = 0;

    /**
     *
     * @var string
     */
    public $sort_option = '';

    /**
     *
     * @var array
     */
    public $tabs = [];

    /**
     *
     * @var string
     */
    public $template_bottom = '';

    /**
     *
     * @var string
     */
    public $template_top = '';

    abstract protected function create_tabs();

    /**
     * 
     * @param string $col_name
     *
     * @return array
     */
    public function get_current_tab($col_name)
    {
        if (!isset($this->tabs[$this->active_tab])) {
            return [];
        }

        return $this->tabs[$this->active_tab][$col_name];
    }

    /**
     * 
     * @return array
     */
    public function get_pagination()
    {
        if (!isset($this->tabs[$this->active_tab])) {
            return [];
        }

        $pages = [];
        $i = $num = 0;
        $current = 1;

        /// añadimos todas la página
        while ($num < $this->tabs[$this->active_tab]['count']) {
            $pages[$i] = [
                'active' => ($num == $this->offset),
                'num' => $i + 1,
                'offset' => $i * FS_ITEM_LIMIT,
            ];
            if ($num == $this->offset) {
                $current = $i;
            }
            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach (array_keys($pages) as $j) {
            $enmedio = intval($i / 2);
            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j > 1 && $j < $current - 5 && $j != $enmedio) || ( $j > $current + 5 && $j < $i - 1 && $j != $enmedio)) {
                unset($pages[$j]);
            }
        }

        return (count($pages) > 1) ? $pages : [];
    }

    /**
     * 
     * @param string $tab_name
     * @param string $label
     * @param string $link
     * @param string $icon
     * @param string $class
     * @param string $id
     * @param string $target
     */
    protected function add_button($tab_name, $label, $link = '#', $icon = '', $class = 'btn-default', $id = '', $target = '')
    {
        $this->tabs[$tab_name]['buttons'][] = [
            'class' => $class,
            'icon' => $icon,
            'id' => $id,
            'label' => $label,
            'link' => $link,
            'target' => $target,
        ];
    }

    /**
     * 
     * @param string         $tab_name
     * @param fs_list_filter $filter
     */
    protected function add_filter($tab_name, $filter)
    {
        $this->tabs[$tab_name]['filters'][] = $filter;
    }

    /**
     * 
     * @param string $tab_name
     * @param string $col_name
     * @param string $label
     * @param string $operation
     * @param mixed  $match_value
     */
    protected function add_filter_checkbox($tab_name, $col_name, $label, $operation = '=', $match_value = true)
    {
        $filter = new fs_list_filter_checkbox($col_name, $label, $operation, $match_value);
        $this->add_filter($tab_name, $filter);
    }

    /**
     * 
     * @param string $tab_name
     * @param string $col_name
     * @param string $label
     * @param string $operation
     */
    protected function add_filter_date($tab_name, $col_name, $label, $operation)
    {
        $filter = new fs_list_filter_date($col_name, $label, $operation);
        $this->add_filter($tab_name, $filter);
    }

    /**
     * 
     * @param string $tab_name
     * @param string $col_name
     * @param string $label
     * @param array  $values
     */
    protected function add_filter_select($tab_name, $col_name, $label, $values)
    {
        $filter = new fs_list_filter_select($col_name, $label, $values);
        $this->add_filter($tab_name, $filter);
    }

    /**
     * 
     * @param string $tab_name
     * @param array  $cols
     */
    protected function add_search_columns($tab_name, $cols = [])
    {
        foreach ($cols as $col_name) {
            $this->tabs[$tab_name]['search_columns'][] = $col_name;
        }
    }

    /**
     * 
     * @param string $tab_name
     * @param array  $cols
     * @param int    $default
     *
     * @return bool
     */
    protected function add_sort_option($tab_name, $cols, $default = 0)
    {
        if (!is_array($cols)) {
            $this->new_error_msg('Debe proporcionar un array de columnas para ordenar.');
            return false;
        }

        $option_name = implode('|', $cols);
        $option_desc = implode(', ', $cols);
        $this->tabs[$tab_name]['sort_options'][$option_name . '|asc'] = $option_desc . ' ASC';
        $this->tabs[$tab_name]['sort_options'][$option_name . '|desc'] = $option_desc . ' DESC';

        switch ($default) {
            case 1:
                $this->tabs[$tab_name]['default_sort'] = $option_name . '|asc';
                return true;

            case 2:
                $this->tabs[$tab_name]['default_sort'] = $option_name . '|desc';
                return true;
        }

        return true;
    }

    /**
     * 
     * @param string $tab_name
     * @param string $title
     * @param string $table
     * @param string $icon
     */
    protected function add_tab($tab_name, $title, $table, $icon = 'fa-files-o')
    {
        $this->tabs[$tab_name] = [
            'buttons' => [],
            'count' => 0,
            'cursor' => [],
            'default_sort' => '',
            'filters' => [],
            'icon' => $icon,
            'name' => $tab_name,
            'search_columns' => [],
            'sort_options' => [],
            'table' => $table,
            'title' => $title
        ];
    }

    /**
     * 
     * @param string $action
     */
    protected function exec_after_action($action)
    {
        ;
    }

    /**
     * 
     * @param string $action
     *
     * @return boolean
     */
    protected function exec_previous_action($action)
    {
        return true;
    }

    /**
     * 
     * @param string $tab_name
     *
     * @return bool
     */
    protected function load_data($tab_name)
    {
        /// table exists?
        if (!$this->db->table_exists($this->tabs[$tab_name]['table'])) {
            return false;
        }

        /// count
        $sql1 = "SELECT COUNT(*) as num " . $this->load_data_from_where($tab_name);
        $data = $this->db->select($sql1);
        if ($data) {
            $this->tabs[$tab_name]['count'] = (int) $data[0]['num'];
        }

        /// ¿bad offset?
        if ($tab_name === $this->active_tab && $this->offset > $this->tabs[$tab_name]['count']) {
            $this->offset = 0;
        }

        /// cursor
        if ($tab_name === $this->active_tab) {
            $sql2 = "SELECT * " . $this->load_data_from_where($tab_name) . $this->load_data_order_by();
            $this->tabs[$tab_name]['cursor'] = $this->db->select_limit($sql2, FS_ITEM_LIMIT, $this->offset);
        }

        return true;
    }

    /**
     * 
     * @param string $tab_name
     *
     * @return string
     */
    protected function load_data_from_where($tab_name)
    {
        $sql = "FROM " . $this->tabs[$tab_name]['table'];
        if ($tab_name != $this->active_tab) {
            return $sql;
        }

        $sql .= " WHERE 1 = 1";
        $query = mb_strtolower($this->empresa->no_html($this->query), 'UTF8');
        if (!empty($query)) {
            $sql .= ' AND (1 != 1';
            foreach ($this->tabs[$tab_name]['search_columns'] as $col) {
                $sql .= " OR LOWER(" . $col . ") LIKE '%" . $query . "%'";
            }
            $sql .= ')';
        }

        /// filtros
        foreach ($this->tabs[$tab_name]['filters'] as $filter) {
            $sql .= $filter->get_where();
        }

        return $sql;
    }

    /**
     * 
     * @return string
     */
    protected function load_data_order_by()
    {
        $keys = explode('|', $this->sort_option);
        $option = array_pop($keys);
        $sql = '';
        foreach ($keys as $key) {
            $sql .= empty($sql) ? ' ORDER BY ' . $key . ' ' . $option : ', ' . $key . ' ' . $option;
        }

        return $sql;
    }

    /**
     * 
     */
    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);

        $this->decoration = new fs_list_decoration();
        $this->template = 'master/list_controller';
        $this->offset = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : 0;
        $this->create_tabs();
        $this->set_active_tab();
        $this->set_filter_values();
        $this->set_sort_option();

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if (!$this->exec_previous_action($action)) {
            return;
        }

        foreach ($this->tabs as $tab) {
            $this->load_data($tab['name']);
        }

        $this->exec_after_action($action);
    }

    /**
     * 
     */
    private function set_active_tab()
    {
        foreach ($this->tabs as $key => $value) {
            if (empty($this->active_tab)) {
                $this->active_tab = $key;
            }

            if (isset($_REQUEST['tab']) && $key === $_REQUEST['tab']) {
                $this->active_tab = $key;
            }
        }
    }

    /**
     * 
     */
    protected function set_filter_values()
    {
        if (!isset($this->tabs[$this->active_tab])) {
            return;
        }

        foreach ($this->tabs[$this->active_tab]['filters'] as $key => $filter) {
            $value = isset($_POST[$filter->name()]) ? $_POST[$filter->name()] : $filter->value;
            $this->tabs[$this->active_tab]['filters'][$key]->value = $value;
        }
    }

    /**
     * 
     */
    private function set_sort_option()
    {
        if (!isset($this->tabs[$this->active_tab])) {
            return;
        }

        foreach (array_keys($this->tabs[$this->active_tab]['sort_options']) as $option) {
            if (empty($this->sort_option)) {
                $default = $this->tabs[$this->active_tab]['default_sort'];
                $this->sort_option = empty($default) ? $option : $default;
            }

            if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == $option) {
                $this->sort_option = $option;
            }
        }
    }

    /**
     * 
     * @param string $tabla
     * @param string $columna1
     * @param string $columna2
     *
     * @return array
     */
    protected function sql_distinct($tabla, $columna1, $columna2 = '')
    {
        if (!$this->db->table_exists($tabla)) {
            return [];
        }

        $columna2 = empty($columna2) ? $columna1 : $columna2;
        $final = [];
        $sql = "SELECT DISTINCT " . $columna1 . ", " . $columna2 . " FROM " . $tabla . " ORDER BY " . $columna2 . " ASC;";
        $data = $this->db->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                if ($d[$columna1] != '') {
                    $final[$d[$columna1]] = $d[$columna2];
                }
            }
        }

        return $final;
    }
}
