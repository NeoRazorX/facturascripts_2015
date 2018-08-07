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
 * Description of fs_edit_decoration
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_edit_decoration
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
     * @param array  $row
     * 
     * @return string
     */
    public function row_class($tab_name, $row)
    {
        if (!isset($this->options[$tab_name])) {
            return '';
        }

        foreach ($this->options[$tab_name] as $option) {
            $col_name = $option['col_name'];
            if ($row[$col_name] == $option['value']) {
                return $option['class'];
            }
        }

        return '';
    }

    /**
     * 
     * @param string            $col_name
     * @param array             $col_config
     * @param fs_model_extended $model
     *
     * @return string
     */
    public function show($col_name, $col_config, $model)
    {
        $html = '<div class="form-group">' . $col_config['label'] . ':';

        switch ($col_config['type']) {
            case 'money':
            case 'number':
                $html .= '<input class="form-control" type="number" step="any" name="' . $col_name . '" value="' . $model->{$col_name} . '" autocomplete="off">';
                break;

            case 'textarea':
                $html .= '<textarea class="form-control" name="' . $col_name . '">' . $model->{$col_name} . '</textarea>';
                break;

            default:
                $html .= '<input class="form-control" type="text" name="' . $col_name . '" value="' . $model->{$col_name} . '" autocomplete="off">';
        }

        $html .= '</div>';
        return $html;
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
