<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('fs_pages.php');
require_model('fs_roles.php');
require_model('fs_roles_pages.php');
require_model('fs_roles_users.php');
/**
 * Description of admin_rol
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class admin_rol extends fs_controller {
    public $rol;
    public $pages;
    public $pages_total;
    public $users;
    public $users_total;
    public $roles;
    public $id;
    public function __construct() {
        parent::__construct(__CLASS__, 'Rol', 'admin', TRUE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->pages = new fs_page();
        $this->roles = new fs_roles();
        $this->shared_extensions();
        $id_p = filter_input(INPUT_POST, 'id');
        $id_g = filter_input(INPUT_GET, 'id');
        $this->id = ($id_p)?$id_p:$id_g;
        $this->rol = $this->roles->get($this->id);
    }

    /**
     * Buscamos las páginas a mostrar, estas pueden ser
     * all = todas sin importar si están asignadas o no
     * asignadas = todas las asignadas al rol
     * disponibles = todas las que no están en el rol
     * @param type $type
     * @return array
     */
    public function mostrar_paginas($type='all'){
        //Inicializamos la lista
        $lista = array();
        //Sacamos el listado de páginas
        /**
         * @todo Pasar por el foreach las paginas y compararlas y hacer un listado segun el type
         */

        $paginas = array();
        foreach($this->pages->all() as $page){
            if($type=='asignadas'){

            }
            $paginas[$page->name]=$page->title;
        }
        switch ($type){
            case "all":
                $lista = $paginas;
                break;
            case "asignadas":

                break;
            case "disponibles":

                break;
            default:
                break;
        }
        return $lista;
    }


    /**
     * Buscamos los usuarios a mostrar
     * @param type $type
     * @return array
     */
    public function mostrar_usuarios($type='all'){
        $lista = array();

        return $lista;
    }

    protected function shared_extensions(){

    }
}
