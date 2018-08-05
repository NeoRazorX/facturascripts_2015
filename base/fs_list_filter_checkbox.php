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
 * Description of fs_list_filter_checkbox
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_list_filter_checkbox extends fs_list_filter
{

    /**
     * 
     * @return string
     */
    public function get_where()
    {
        return $this->value ? ' AND ' . $this->col_name . ' = true' : '';
    }

    /**
     * 
     * @return string
     */
    public function show()
    {
        $checked = $this->value ? ' checked=""' : '';
        return '<div class="checkbox">'
            . '<label><input type="checkbox" name="' . $this->name() . '" value="TRUE" ' . $checked
            . ' onchange="this.form.submit()">' . $this->label . '</label>'
            . '</div>';
    }
}
