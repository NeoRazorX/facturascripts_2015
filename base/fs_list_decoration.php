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

    public function __construct()
    {
        $this->divisa_tools = new fs_divisa_tools();
    }

    /**
     * 
     * @param string $col_name
     * @param string $col_type
     * @param array  $row
     * @param array  $css_class
     * @return string
     */
    public function show($col_name, $col_type, $row, $css_class = [])
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
                $final_value = $this->divisa_tools->show_precio($final_value);
                break;

            case 'number':
                $final_value = $this->divisa_tools->show_numero($final_value);
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
}
