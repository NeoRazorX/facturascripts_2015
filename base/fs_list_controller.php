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
     *
     * @var array
     */
    private $model_objects = [];

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
    
    public $template_top = '';

    abstract protected function create_tabs();

    public function get_current_tab($col_name)
    {
        return $this->tabs[$this->active_tab][$col_name];
    }

    public function get_decoration($col_name, $col_type, $row, $css_class = [])
    {
        $final_value = $row[$col_name];
        switch ($col_type) {
            case 'date':
                $final_value = date('d-m-Y', strtotime($final_value));
                break;

            case 'timestamp':
            case 'datetime':
                $final_value = date('d-m-Y H:i:s', strtotime($final_value));
                break;

            case 'money':
                $final_value = $this->show_precio($final_value);
                break;

            case 'number':
                $final_value = $this->show_numero($final_value);
                break;
        }

        if (in_array($col_type, ['money', 'number'])) {
            $css_class[] = 'text-right';
            if ($final_value <= 0) {
                $css_class[] = 'warning';
            }
        }

        return '<td class="' . implode(' ', $css_class) . '">' . $final_value . '</td>';
    }

    public function get_pagination()
    {
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

    protected function add_search_columns($tab_name, $cols = [])
    {
        foreach ($cols as $col_name) {
            $this->tabs[$tab_name]['search_columns'][] = $col_name;
        }
    }

    protected function add_sort_option($tab_name, $cols, $default = 0)
    {
        if (!is_array($cols)) {
            $this->new_error_msg('Debe proporcionar un array de columnas para ordenar.');
            return;
        }

        $option_name = implode('|', $cols);
        $option_desc = implode(', ', $cols);
        $this->tabs[$tab_name]['sort_options'][$option_name . '|asc'] = $option_desc . ' ASC';
        $this->tabs[$tab_name]['sort_options'][$option_name . '|desc'] = $option_desc . ' DESC';

        switch ($default) {
            case 1:
                $this->tabs[$tab_name]['default_sort'] = $option_name . '|asc';
                break;

            case 2:
                $this->tabs[$tab_name]['default_sort'] = $option_name . '|desc';
                break;
        }
    }

    protected function add_tab($tab_name, $title, $table, $columns = [], $icon = 'fa-files-o')
    {
        $this->tabs[$tab_name] = [
            'columns' => $columns,
            'count' => 0,
            'cursor' => [],
            'default_sort' => '',
            'icon' => $icon,
            'name' => $tab_name,
            'search_columns' => [],
            'sort_options' => [],
            'table' => $table,
            'title' => $title
        ];
    }

    protected function get_model_object($model_class_name, $code)
    {
        if (isset($this->model_objects[$model_class_name][$code])) {
            return $this->model_objects[$model_class_name][$code];
        }

        $model = new $model_class_name();
        $object = $model->get($code);
        if ($object) {
            $this->model_objects[$model_class_name][$code] = $object;
            return $object;
        }

        return $model;
    }

    protected function load_data($tab_name)
    {
        /// count
        $sql1 = "SELECT COUNT(*) as num " . $this->load_data_from_where($tab_name);
        $data = $this->db->select($sql1);
        if ($data) {
            $this->tabs[$tab_name]['count'] = (int) $data[0]['num'];
        }

        /// cursor
        if ($tab_name == $this->active_tab) {
            $sql2 = "SELECT * " . $this->load_data_from_where($tab_name) . $this->load_data_order_by();
            $this->tabs[$tab_name]['cursor'] = $this->db->select_limit($sql2, FS_ITEM_LIMIT, $this->offset);
        }
    }

    protected function load_data_from_where($tab_name)
    {
        $query = mb_strtolower($this->empresa->no_html($this->query), 'UTF8');
        $sql = "FROM " . $this->tabs[$tab_name]['table'];

        if ($tab_name == $this->active_tab && !empty($query)) {
            $sql .= " WHERE 1 != 1";
            foreach ($this->tabs[$tab_name]['search_columns'] as $col) {
                $sql .= " OR LOWER(" . $col . ") LIKE '%" . $query . "%'";
            }
        }

        return $sql;
    }

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

    protected function private_core()
    {
        $this->template = 'master/list_controller';
        $this->offset = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : 0;
        $this->create_tabs();
        $this->set_active_tab();
        $this->set_sort_option();

        foreach ($this->tabs as $tab) {
            $this->load_data($tab['name']);
        }
    }

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

    private function set_sort_option()
    {
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
}
