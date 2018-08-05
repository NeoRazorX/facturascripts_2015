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

/**
 * Description of fs_list_filter
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class fs_list_filter
{

    /**
     *
     * @var string
     */
    public $col_name;

    /**
     *
     * @var mixed
     */
    public $value;

    abstract public function get_where();

    abstract public function show();

    /**
     * 
     * @param string $col_name
     */
    public function __construct($col_name)
    {
        $this->col_name = $col_name;
    }

    /**
     * 
     * @return string
     */
    public function name()
    {
        return 'filter_' . $this->col_name;
    }
}
