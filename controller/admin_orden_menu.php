<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description Ordenar menú
 *
 * @author alagoro
 */
class admin_orden_menu extends fs_controller
{

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Ordenar menú', 'admin', FALSE, TRUE);
    }

    protected function private_core()
    {
        if (filter_input(INPUT_POST, 'guardar')) {
            $this->guardar_orden();
        }
    }

    private function guardar_orden()
    {
        foreach ($this->folders() as $folder) {
            $orden = 0;
            foreach (filter_input_array(INPUT_POST) as $key => $value) {
                if (strlen($key) > $folder) {
                    if (substr($key, 0, strlen($folder)) == $folder) {
                        $page = $this->page->get($value);
                        $page->orden = $orden;
                        if ($page->save()) {
                            $orden++;
                        }
                    }
                }
            }
        }

        $this->new_message('Datos guardados.');
        $this->menu = $this->user->get_menu(TRUE);
    }
}
