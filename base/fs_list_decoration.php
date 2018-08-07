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
    protected $options = [];

    /**
     *
     * @var array
     */
    protected $urls = [];

    public function __construct()
    {
        $this->divisa_tools = new fs_divisa_tools();
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
     * @param string $col_type
     * @param array  $row
     * @param array  $css_class
     *
     * @return string
     */
    public function show($col_name, $col_type, $row, $css_class = [])
    {
        $final_value = isset($row[$col_name]) ? $row[$col_name] : '';
        switch ($col_type) {
            case 'date':
                $final_value = empty($final_value) ? '-' : date('d-m-Y', strtotime($final_value));
                break;

            case 'timestamp':
            case 'datetime':
                $final_value = date('d-m-Y H:i:s', strtotime($final_value));
                break;

            case 'money':
                $final_value = $this->divisa_tools->show_precio((float) $final_value);
                break;

            case 'number':
                $final_value = $this->divisa_tools->show_numero((float) $final_value);
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
