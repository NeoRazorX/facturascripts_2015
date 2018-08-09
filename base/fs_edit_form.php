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
 * Description of fs_edit_form
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_edit_form
{

    /**
     *
     * @var array
     */
    public $columns = [];

    /**
     *
     * @var array
     */
    private $model_objects = [];

    /**
     * 
     * @param fs_edit_form $old_decoration
     */
    public function __construct($old_decoration = null)
    {
        if ($old_decoration) {
            $this->columns = $old_decoration->columns;
        }
    }

    /**
     * 
     * @param string $col_name
     * @param string $type
     * @param string $label
     * @param int    $num_cols
     * @param bool   $required
     * @param array  $values
     */
    public function add_column($col_name, $type = 'string', $label = '', $num_cols = 2, $required = false, $values = [])
    {
        $this->columns[$col_name] = [
            'label' => empty($label) ? $col_name : $label,
            'num_cols' => $num_cols,
            'required' => $required,
            'type' => $type,
            'values' => $values,
        ];
    }

    /**
     * 
     * @param string $col_name
     * @param array  $values
     * @param string $label
     * @param int    $num_cols
     * @param bool   $required
     */
    public function add_column_select($col_name, $values, $label = '', $num_cols = 2, $required = false)
    {
        $this->add_column($col_name, 'select', $label, $num_cols, $required, $values);
    }

    /**
     * 
     * @param string            $col_name
     * @param array             $col_config
     * @param fs_extended_model $model
     *
     * @return string
     */
    public function show($col_name, $col_config, $model)
    {
        $html = '<div class="form-group">' . $col_config['label'] . ':';
        $required = $col_config['required'] ? ' required=""' : '';

        switch ($col_config['type']) {
            case 'bool':
                $checked = $model->{$col_name} ? ' checked=""' : '';
                $html = '<div class="checkbox"><label><input type="checkbox" name="' . $col_name
                    . '" value="TRUE"' . $checked . '/> ' . $col_config['label'] . '</label>';
                break;

            case 'date':
                $html .= '<input class="form-control datepicker" type="text" name="' . $col_name
                    . '" value="' . $model->{$col_name} . '" autocomplete="off"' . $required . '/>';
                break;

            case 'money':
            case 'number':
                $html .= '<input class="form-control" type="number" step="any" name="' . $col_name
                    . '" value="' . $model->{$col_name} . '" autocomplete="off"' . $required . '/>';
                break;

            case 'select':
                $html .= $this->show_select($col_name, $col_config, $model);
                break;

            case 'textarea':
                $html .= '<textarea class="form-control" name="' . $col_name . '"' . $required . '>'
                    . $model->{$col_name} . '</textarea>';
                break;

            default:
                $html .= '<input class="form-control" type="text" name="' . $col_name
                    . '" value="' . $model->{$col_name} . '" autocomplete="off"' . $required . '/>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * 
     * @param string            $col_name
     * @param array             $col_config
     * @param fs_extended_model $model
     *
     * @return string
     */
    protected function show_select($col_name, $col_config, $model)
    {
        $required = $col_config['required'] ? ' required=""' : '';
        $html = '<select name="codproveedor" class="form-control"' . $required . '>';

        foreach ($col_config['values'] as $key => $value) {
            if ($model->{$col_name} == $key) {
                $html .= '<option value="' . $key . '" selected="">' . $value . '</option>';
            } else {
                $html .= '<option value="' . $key . '">' . $value . '</option>';
            }
        }

        $html .= '</select>';
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
