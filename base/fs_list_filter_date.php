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
 * Description of fs_list_filter_date
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_list_filter_date extends fs_list_filter
{

    /**
     *
     * @var string
     */
    protected $operation;

    public function __construct($col_name, $label, $operation = '>=')
    {
        parent::__construct($col_name, $label);
        $this->operation = $operation;
    }

    /**
     * 
     * @return string
     */
    public function get_where()
    {
        /// necesitamos un modelo, el que sea, para llamar a su función var2str()
        $fs_log = new fs_log();
        return $this->value ? ' AND ' . $this->col_name . ' ' . $this->operation . ' ' . $fs_log->var2str($this->value) : '';
    }

    public function name()
    {
        switch ($this->operation) {
            case '>':
            case '>=':
                return parent::name() . '_gt';

            case '<':
            case '<=':
                return parent::name() . '_lt';

            default:
                return parent::name();
        }
    }

    /**
     * 
     * @return string
     */
    public function show()
    {
        return '<div class="form-group"><input type="text" name="' . $this->name()
            . '" value="' . $this->value . '" class="form-control datepicker" placeholder="' . $this->label . '" autocomplete="off" onchange="this.form.submit()">'
            . '</div>';
    }
}
