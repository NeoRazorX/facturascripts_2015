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
    public $users_total;
    public $roles;
    public $roles_users;
    public $roles_pages;
    public $id;
    public $plugins;
    public function __construct() {
        parent::__construct(__CLASS__, 'Rol', 'admin', TRUE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->pages = new fs_page();
        $this->roles = new fs_roles();
        $this->roles_users = new fs_roles_users();
        $this->roles_pages = new fs_roles_pages();
        $this->plugins = $GLOBALS['plugins'];

        $this->shared_extensions();

        $id_p = filter_input(INPUT_POST, 'id');
        $id_g = filter_input(INPUT_GET, 'id');
        $this->id = ($id_p)?$id_p:$id_g;

        $accion = filter_input(INPUT_POST, 'accion');
        if($accion=='agregar_pagina' OR $accion=='eliminar_pagina'){
            $this->tratar_paginas($accion);
        }elseif($accion=='agregar_usuario' OR $accion=='eliminar_usuario'){
            $this->tratar_usuarios($accion);
        }elseif($accion=='actualizar_pagina'){
            $this->tratar_pagina();
        }elseif($accion=='actualizar_usuario'){
            $this->tratar_usuario();
        }

        //Una vez realizados todos los tratamientos cargamos las páginas y usuarios
        $this->rol = $this->roles->get($this->id);
    }

    /**
     * Tratamos las paginas procesadas ya sea para agregarlas o eliminarlas
     */
    public function tratar_paginas($accion){
        $enabled = filter_input(INPUT_POST, 'enabled', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $allow_delete_p = filter_input(INPUT_POST, 'allow_delete', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $allow_delete = ($allow_delete_p)?$allow_delete_p:array();
        if($accion=='agregar_pagina'){
            $mensaje = 'Páginas agregadas ';
            /**
            * Grabamos las páginas a las que vamos a crear el acceso
            */
            $lista_errores = array();
            foreach($enabled as $key=>$name){
                $rol_paginas = new fs_roles_pages();
                $rol_paginas->id = $this->id;
                $rol_paginas->name = $name;
                $rol_paginas->plugin = $this->getPagePlugin($name);
                $rol_paginas->allow_delete = (in_array($name, $allow_delete))?TRUE:FALSE;
                $rol_paginas->estado = TRUE;
                $rol_paginas->fecha_creacion = \Date('d-m-Y H:i:s');
                $rol_paginas->usuario_creacion = $this->user->nick;
                $rol_paginas->fecha_modificacion = \Date('d-m-Y H:i:s');
                $rol_paginas->usuario_modificacion = $this->user->nick;
                if($rol_paginas->save()){
                    $paginas = true;
                }else{
                    $paginas = false;
                    $lista_errores[]=$name;
                    unset($enabled[$key]);
                }
            }

            /**
            * Si todo está correcto tambien actualizamos fs_access
             * para los usuarios del rol actual
            */
           if($paginas){
               $this->agregar_paginas_usuarios($enabled);
           }

        }

        $this->new_message('¡'.$mensaje.'con exito y aplicada a los usuarios!');
    }

    /**
     * Agregamos los usuarios seleccionados al rol y luego actualizamos su fs_page
     * @param type $accion
     */
    public function tratar_usuarios($accion){
        $acceso = filter_input(INPUT_POST, 'acceso', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if($accion=='agregar_usuario'){
            foreach($acceso as $usuario){
                $rol_usuario = new fs_roles_users();
                $rol_usuario->id = $this->id;
                $rol_usuario->nick = trim($usuario);
                $rol_usuario->estado = TRUE;
                $rol_usuario->fecha_creacion = \Date('d-m-Y H:i:s');
                $rol_usuario->usuario_creacion = $this->user->nick;
                $rol_usuario->fecha_modificacion = \Date('d-m-Y H:i:s');
                $rol_usuario->usuario_modificacion = $this->user->nick;
                if($rol_usuario->save()){
                    $usuario_ok = true;
                    $lista_paginas = $this->roles_pages->get_by('rol', $this->id);
                    foreach($lista_paginas as $p){
                        $a = new fs_access( array('fs_user'=> trim($usuario), 'fs_page'=>$p->name, 'allow_delete'=>$p->allow_delete) );
                        $a->save();
                    }
                }else{
                    $usuario_ok = false;
                }
            }
        }
    }

    /**
     * Agregamos a cada usuario las páginas a las que va tener acceso
     */
    public function agregar_paginas_usuarios(){
        $lista_paginas = $this->roles_pages->get_by('rol', $this->id);
        $acceso = $this->roles_users->get_by('rol',$this->id);
        $lista_errores = array();
        foreach($acceso as $usuario){
            foreach($lista_paginas as $p){
                $a = new fs_access( array('fs_user'=> $usuario->nick, 'fs_page'=>$p->name, 'allow_delete'=>$p->allow_delete) );
                if($a->save()){
                    $resultado = true;
                }else{
                    $resultado = false;
                    $lista_errores[]="¡Error al aplicar Usuario: $usuario - Pagina: {$p->name}!.<br />";
                }
            }
        }
        if(!empty($lista_errores)){
            foreach($lista_errores as $error){
                $this->new_error_msg($error);
            }
        }
        if($resultado){
            $this->new_message('¡Páginas agregadas a los usuarios de este rol con exito!');
        }
    }

    /**
     * Buscamos las páginas a mostrar, estas pueden ser
     * all = todas sin importar si están asignadas o no
     * asignadas = todas las asignadas al rol
     * disponibles = todas las que no están en el rol
     * Agregamos el plugin con el cual vamos a comparar la lista
     * @param type $type
     * @param type $plugin
     * @return array
     */
    public function mostrar_paginas($type='all',$plugin){
        //Inicializamos la lista
        $lista = array();
        //Sacamos el listado de páginas
        /**
         * @todo Pasar por el foreach las paginas y compararlas y hacer un listado segun el type
         */
        foreach($this->pages->all() as $page){
            if($type=='asignadas' and $this->rol->get_page($this->id,$page->name,$this->getPagePlugin($page->name)) and ($plugin==$this->getPagePlugin($page->name))){
                $lista[] = $this->data_pagina($page);
            }elseif($type=='disponibles' and !$this->rol->get_page($this->id,$page->name,$this->getPagePlugin($page->name)) and ($plugin==$this->getPagePlugin($page->name))){
                $lista[] = $this->data_pagina($page);
            }elseif($type=='all' and ($plugin==$this->getPagePlugin($page->name))){
                $lista[] = $this->data_pagina($page);
            }
        }
        return $lista;
    }

    /**
     * Una vez pasado por el foreach de la funcion mostrar_paginas generamos la información a ser utilizada en el listado de Plugins
     * @param type $page
     * @return \stdClass
     */
    public function data_pagina($page){
        $item = new stdClass();
        $item->name=$page->name;
        $item->folder=$page->folder;
        $item->title=$page->title;
        $item->plugin=$this->getPagePlugin($page->name);
        return $item;
    }

    /**
     * Obtenemos a que plugin pertenece una página buscando en el listado de plugins
     * @param type $page string
     */
    protected function getPagePlugin($page){
        $plugin = "";
        $found = false;
        foreach($GLOBALS['plugins'] as $name)
        {
            if( file_exists(getcwd().'/plugins/'.$name.'/controller') )
            {
                foreach( scandir(getcwd().'/plugins/'.$name.'/controller') as $f )
                {
                    if((substr($f, -4) == '.php') AND (substr($f, 0, -4) == $page))
                    {
                        $found = true;
                        $plugin = $name;
                    }
                }
            }
        }
        if(!$found){
            /// Buscamos las páginas que están en el directorio controller
            foreach( scandir(getcwd().'/controller') as $f)
            {
                if((substr($f, -4) == '.php') AND (substr($f, 0, -4) == $page)){
                    $plugin = 'FacturaScripts';
                }
            }
        }
        return $plugin;
    }


    /**
     * Buscamos los usuarios a mostrar
     * @param type $type
     * @return array
     */
    public function mostrar_usuarios($type='all'){
        $lista = array();
        $lista_usuarios = $this->roles_users->get_by('rol',$this->id);
        if($type=='all'){
            $lista = $this->user->all();
        }elseif($type=='disponibles'){
            foreach($this->user->all() as $user){
                if(!$this->roles_users->get($this->id, $user->nick)){
                    $lista[] = $user;
                }
            }
        }elseif($type=='asignados'){
            foreach($this->user->all() as $user){
                if($this->roles_users->get($this->id, $user->nick)){
                    $lista[] = $user;
                }
            }
        }
        return $lista;
    }
    protected function shared_extensions(){

    }
}
