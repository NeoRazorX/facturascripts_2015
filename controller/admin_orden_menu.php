<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('fs_page.php');

define('AL_ACCION_GRABAR', 2);

/**
 * Description Ordenar menú
 *
 * @author alagoro
 */
class admin_orden_menu extends fs_controller {

    private $folders = [];
    private $paginas = [];

    public function __construct() {
        parent::__construct(__CLASS__, 'Ordenar menú', 'admin', FALSE, TRUE);
    }

    protected function private_core() {

        if (!is_null(filter_input(INPUT_POST, 'accion'))) {
            $accion = filter_input(INPUT_POST, 'accion');
            switch ($accion) {
                case AL_ACCION_GRABAR :
                    $this->guardar_orden();
                    break;

                default:
                    break;
            }
        }

        $mimenu = $this->user->get_menu();
        foreach ($mimenu as $menuitem) {
            if ($menuitem->show_on_menu == 1) {
                if (!in_array($menuitem->folder, $this->folders))
                    $this->folders[] = $menuitem->folder;
                $this->paginas[$menuitem->folder][] = ['name' => $menuitem->name, 'title' => $menuitem->title, 'orden' => $menuitem->orden];
            }
        }
    }

    private function guardar_orden() {
        $this->template = FALSE;
        $resultado = [];
        $elementos = filter_input(INPUT_POST, 'elementos');
        if ($elementos) {
            $elementos = explode(',', $elementos);

            $page = new fs_page();
            foreach ($elementos as $orden => $elemento) {
                $page->save_orden($elemento, $orden);
            }
            $resultado['ERROR'] = 0;
            $resultado['MENSAJE'] = 'Elementos ordenados.';
        } else {
            $resultado['ERROR'] = 1;
            $resultado['MENSAJE'] = 'No hay elementos.';
        }
        echo json_encode($resultado);
    }

    public function get_menu_folders() {
        return $this->folders;
    }

    public function get_menu($folder) {

        return isset($this->paginas[$folder]) ? $this->paginas[$folder] : [];
    }

    public function url() {
        return 'index.php?page=admin_orden_menu';
    }

}
