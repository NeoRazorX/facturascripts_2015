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
 * Description of fs_list_decoration
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_list_decoration
{

    /**
     *
     * @var array
     */
    public $columns = [];

    /**
     *
     * @var fs_divisa_tools
     */
    protected $divisa_tools;

    /**
     *
     * @var array
     */
    private $model_objects = [];

    /**
     *
     * @var array
     */
    public $options = [];

    /**
     *
     * @var array
     */
    public $urls = [];

    /**
     * 
     * @param fs_list_decoration $old_decoration
     */
    public function __construct($old_decoration = null)
    {
        $this->divisa_tools = new fs_divisa_tools();
        if (!is_null($old_decoration)) {
            $this->columns = $old_decoration->columns;
            $this->options = $old_decoration->options;
            $this->urls = $old_decoration->urls;
        }
    }

    /**
     * 
     * @param string $tab_name
     * @param string $col_name
     * @param string $type
     * @param string $title
     * @param string $class
     * @param string $base_url
     */
    public function add_column($tab_name, $col_name, $type = 'string', $title = '', $class = '', $base_url = '')
    {
        $this->columns[$tab_name][$col_name] = [
            'base_url' => $base_url,
            'class' => $class,
            'type' => $type,
            'title' => empty($title) ? $col_name : $title,
        ];
    }

    /**
     * 
     * @param string $tab_name
     * @param string $col_name
     * @param mixed  $value
     * @param string $class
     */
    public function add_row_option($tab_name, $col_name, $value, $class)
    {
        $this->options[$tab_name][] = [
            'class' => $class,
            'col_name' => $col_name,
            'value' => $value,
        ];
    }

    /**
     * 
     * @param string $tab_name
     * @param string $base_url
     * @param string $col_name
     */
    public function add_row_url($tab_name, $base_url, $col_name)
    {
        $this->urls[$tab_name] = [
            'col_name' => $col_name,
            'base_url' => $base_url,
        ];
    }

    /**
     * 
     * @param string $tab_name
     *
     * @return array
     */
    public function get_columns($tab_name)
    {
        return isset($this->columns[$tab_name]) ? $this->columns[$tab_name] : [];
    }

    /**
     * 
     * @param string $tab_name
     * @param array  $row
     * 
     * @return string
     */
    public function row_class($tab_name, $row)
    {
        $extra = '';
        if (isset($this->urls[$tab_name])) {
            $col_name = $this->urls[$tab_name]['col_name'];
            $extra .= ' clickableRow" href="' . $this->urls[$tab_name]['base_url'] . $row[$col_name] . '"';
        }

        if (!isset($this->options[$tab_name])) {
            return $extra;
        }

        foreach ($this->options[$tab_name] as $option) {
            $col_name = $option['col_name'];
            if ($row[$col_name] == $option['value']) {
                return $option['class'] . $extra;
            }
        }

        return $extra;
    }

    /**
     * 
     * @param string $col_name
     * @param string $col_config
     * @param array  $row
     * @param array  $css_class
     *
     * @return string
     */
    public function show($col_name, $col_config, $row, $css_class = [])
    {
        $value = isset($row[$col_name]) ? $row[$col_name] : '';
        switch ($col_config['type']) {
            case 'bool':
                $final_value = $value ? 'Si' : '';
                break;

            case 'date':
                $final_value = empty($value) ? '-' : date('d-m-Y', strtotime($value));
                break;

            case 'timestamp':
            case 'datetime':
                $final_value = date('d-m-Y H:i:s', strtotime($value));
                break;

            case 'money':
                $final_value = $this->divisa_tools->show_precio((float) $value);
                break;

            case 'number':
                $final_value = $this->divisa_tools->show_numero((float) $value);
                break;

            default:
                $final_value = $value;
        }

        if (!empty($col_config['class'])) {
            $css_class[] = $col_config['class'];
        }

        if (!empty($col_config['base_url'])) {
            $final_value = '<a href="' . $col_config['base_url'] . $value . '" class="cancel_clickable">'
                . $final_value . '</a>';
        }

        return '<td class="' . implode(' ', $css_class) . '">' . $final_value . '</td>';
    }

    /**
     * 
     * @param string $model_class_name
     * @param string $code
     * 
     * @return mixed
     */
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
}
