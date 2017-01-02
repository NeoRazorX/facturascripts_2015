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
require_once 'admin_home.php';
require_model('fs_roles.php');
require_model('fs_roles_pages.php');
require_model('fs_roles_users.php');
/**
 * Description of admin_roles
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class admin_roles extends fs_controller {
    public $roles;
    public $roles_cantidad;
    public $roles_usuarios;
    public $roles_paginas;
    public $plugins;
    public $pages;
    public $all_pages;
    public function __construct() {
        parent::__construct(__CLASS__, 'Roles de Usuario', 'admin', TRUE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->pages = new fs_page();
        $this->roles = new fs_roles();
        $this->roles_paginas = new fs_roles_pages();
        $this->roles_usuarios = new fs_roles_users();
        $this->plugins = $this->lista_plugins();

        //Cargamos los complementos
        $this->shared_extensions();
        //Verificamos si este usuario puede borrar Roles
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);

        $accion = filter_input(INPUT_POST, 'accion');
        if($accion == 'agregar'){
            $paginas = false;
            $id = filter_input(INPUT_POST, 'id');
            $descripcion = filter_input(INPUT_POST, 'descripcion');
            $enabled = filter_input(INPUT_POST, 'enabled', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $allow_delete_p = filter_input(INPUT_POST, 'allow_delete', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $allow_delete = ($allow_delete_p)?$allow_delete_p:array();
            $acceso = filter_input(INPUT_POST, 'acceso', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $estado = filter_input(INPUT_POST, 'estado');
            $rol0 = new fs_roles();
            $rol0->id = $id;
            $rol0->descripcion = $this->cleanText($descripcion);
            $rol0->estado = ($estado=='TRUE')?TRUE:FALSE;
            $rol0->fecha_creacion = \Date('d-m-Y H:i:s');
            $rol0->usuario_creacion = $this->user->nick;
            $rol0->fecha_modificacion = \Date('d-m-Y H:i:s');
            $rol0->usuario_modificacion = $this->user->nick;
            $id_rol = $rol0->save();
            if($id_rol){
                /**
                 * Grabamos las páginas a las que vamos a crear el acceso
                 */
                foreach($enabled as $page){
                    $rol_paginas = new fs_roles_pages();
                    $rol_paginas->id = $id_rol;
                    $rol_paginas->name = $page;
                    $rol_paginas->plugin = $this->getPagePlugin($page);
                    $rol_paginas->allow_delete = (in_array($page, $allow_delete))?TRUE:FALSE;
                    $rol_paginas->fecha_creacion = \Date('d-m-Y H:i:s');
                    $rol_paginas->usuario_creacion = $this->user->nick;
                    $rol_paginas->fecha_modificacion = \Date('d-m-Y H:i:s');
                    $rol_paginas->usuario_modificacion = $this->user->nick;
                    if($rol_paginas->save()){
                        $paginas = true;
                    }else{
                        $paginas = false;
                    }
                }

                /**
                 * Insertamos los valores en la tabla de roles_users
                 * Y si todo está correcto tambien actualizamos fs_access
                 */
                $usuario = false;
                if($paginas AND $acceso){
                    foreach($acceso as $usuario){
                        $rol_usuario = new fs_roles_users();
                        $rol_usuario->id = $id_rol;
                        $rol_usuario->nick = trim($usuario);
                        $rol_usuario->fecha_creacion = \Date('d-m-Y H:i:s');
                        $rol_usuario->usuario_creacion = $this->user->nick;
                        $rol_usuario->fecha_modificacion = \Date('d-m-Y H:i:s');
                        $rol_usuario->usuario_modificacion = $this->user->nick;
                        if($rol_usuario->save()){
                            $usuario_ok = true;
                            $lista_paginas = $this->roles_paginas->get_by('rol', $id_rol);
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
        }elseif($accion=='eliminar'){
            $item = $this->roles->get(filter_input(INPUT_POST, 'id'));
            if($item){
                if($item->delete()){
                    $this->new_message('¡Rol eliminado exitosamente!');
                }else{
                    $this->new_error_msg('Ocurrio un error al intentar borrar el rol.');
                }
            }
        }
        //Luego de todo el proceso contamos la cantidad de roles
        $this->roles_cantidad = ($this->roles->all())?count($this->roles->all()):0;
    }

    /**
     * Limpiamos las variables de texto que nos lleguen
     * @param type $str
     * @return type string
     */
    protected function cleanText($str){
        return addslashes(htmlspecialchars(trim($str)));
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
     * Buscamos las páginas a mostrar, estas pueden ser
     * all = todas sin importar si están asignadas o no
     * asignadas = todas las asignadas al rol
     * disponibles = todas las que no están en el rol
     * Agregamos el plugin con el cual vamos a comparar la lista
     * @param type $type
     * @param type $plugin
     * @return array
     */
    public function mostrar_paginas($plugin){
        //Inicializamos la lista
        $lista = array();
        //Sacamos el listado de páginas
        foreach($this->pages->all() as $page){
            if($plugin==$this->getPagePlugin($page->name)){
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
     * Generamos la lista de plugins agregando los que son de la base
     * como un plugin FacturaScripts
     * @return type array
     */
    public function lista_plugins(){
        $lista = array();
        $lista[] = 'FacturaScripts';
        foreach($GLOBALS['plugins'] as $plugin){
            $lista[] = $plugin;
        }
        return $lista;
    }

    protected function shared_extensions(){
        $extensions = array(
            array(
                'name' => 'admin_roles_validate_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'view/js/jquery.validate.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'loader_roles_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'view/js/loader.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'loader_roles_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'view/css/loader.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'wizard_roles_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'view/css/wizard.css"/>',
                'params' => ''
            )
        );

        foreach($extensions as $ext)
        {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }
}