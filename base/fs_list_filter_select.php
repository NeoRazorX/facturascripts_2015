<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
require_once 'base/fs_list_filter.php';

/**
 * Description of fs_list_filter_select
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_list_filter_select extends fs_list_filter
{

    /**
     *
     * @var array
     */
    protected $values;

    /**
     * 
     * @param string $col_name
     * @param string $label
     * @param array  $values
     */
    public function __construct($col_name, $label, $values)
    {
        parent::__construct($col_name, $label);
        $this->values = $values;
    }

    public function get_where()
    {
        /// necesitamos un modelo, el que sea, para llamar a su función var2str()
        $fs_log = new fs_log();
        return empty($this->value) ? '' : ' AND ' . $this->col_name . ' = ' . $fs_log->var2str($this->value);
    }

    /**
     * 
     * @return string
     */
    public function show()
    {
        $html = '<div class="form-group">'
            . '<select class="form-control" name="' . $this->name() . '" onchange="this.form.submit()">'
            . '<option value="">Cualquier ' . $this->label . '</option>'
            . '<option value="">-----</option>';

        foreach ($this->values as $key => $value) {
            if ($key === $this->value) {
                $html .= '<option value="' . $key . '" selected="">' . $value . '</option>';
            } else {
                $html .= '<option value="' . $key . '">' . $value . '</option>';
            }
        }

        $html .= '</select></div>';
        return $html;
    }
}
