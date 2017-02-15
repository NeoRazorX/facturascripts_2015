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
require_model('fs_roles.php');
/**
 * Description of fs_roles_pages
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class fs_roles_pages extends fs_model{
    /**
     *
     * @var type integer
     */
    public $id;
    /**
     *
     * @var type varchar(30)
     */
    public $name;
    /**
     *
     * @var type varchar(64)
     */
    public $plugin;
    /**
     *
     * @var type boolean
     */
    public $allow_delete;
    /**
     *
     * @var type timestamp with out time zone
     */
    public $fecha_creacion;
    /**
     *
     * @var type timestamp with out time zone
     */
    public $fecha_modificacion;
    /**
     *
     * @var type varchar(12)
     */
    public $usuario_creacion;
    /**
     *
     * @var type varchar(12)
     */
    public $usuario_modificacion;

    public function __construct($t = FALSE) {
        parent::__construct('fs_roles_pages');
        if($t){
            $this->id = $t['id'];
            $this->name = $t['name'];
            $this->plugin = $t['plugin'];
            $this->allow_delete = $this->str2bool($t['allow_delete']);
            $this->fecha_creacion = \date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_modificacion = \date('d-m-Y H:i:s', strtotime($t['fecha_modificacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
        }else{
            $this->id = NULL;
            $this->name = NULL;
            $this->plugin = NULL;
            $this->allow_delete = FALSE;
            $this->fecha_creacion = \date('d-m-Y H:i:s');
            $this->usuario_creacion = NULL;
            $this->fecha_modificacion = NULL;
            $this->usuario_modificacion = NULL;
        }
    }

    protected function install() {
        return '';
    }

    public function all(){
        $sql = "select * from ".$this->table_name." ORDER BY id";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach ($data as $d){
                $linea = new fs_roles_pages($d);
                $lista[] = $linea;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     * Obtenemos la información de un item
     * @param type $id
     * @return type object or false
     */
    public function get($id,$name,$plugin){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id)." AND name = ".$this->var2str($name)." AND plugin = ".$this->var2str($plugin).";";
        $data = $this->db->select($sql);
        if($data){
            return new fs_roles_pages($data[0]);
        }else{
            return false;
        }
    }

    public function get_by($type,$value){
        switch ($type){
            case "rol":
                $where = " WHERE id = ".$this->intval($value);
                $order = "id,plugin,name";
                break;
            case "page":
                $where = " WHERE name = ".$this->var2str($value);
                $order = "plugin,name,id";
                break;
            case "plugin":
                $where = " WHERE plugin = ".$this->var2str($value);
                $order = "plugin,name,id";
                break;
            default :
                $where  = "";
                $order = "plugin,name,id";
                break;
        }
        $sql = "SELECT * FROM ".$this->table_name.$where." ORDER BY ".$order.";";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach ($data as $d){
                $linea = new fs_roles_pages($d);
                $lista[] = $linea;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    public function exists() {
        if(is_null($this->id) AND is_null($this->name) AND is_null($this->plugin)){
            return false;
        }else{
            return $this->get($this->id,$this->name,$this->plugin);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                "allow_delete = ".$this->var2str($this->allow_delete).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion)." ".
                " WHERE id = ".$this->intval($this->id).
                " AND name = ".$this->var2str($this->name).
                " AND plugin = ".$this->var2str($this->plugin).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id, name, plugin, allow_delete, fecha_creacion, usuario_creacion) VALUES (".
                $this->intval($this->id).",".
                $this->var2str($this->name).",".
                $this->var2str($this->plugin).",".
                $this->var2str($this->allow_delete).",".
                $this->var2str($this->fecha_creacion).",".
                $this->var2str($this->usuario_creacion).");";
        }
        return $this->db->exec($sql);
    }

    /**
     * No borramos la página del rol, la desactivamos para poder tener el control de quien y cuando se borro
     * @return type boolean
     */
    public function delete() {
        $sql = "DELETE FROM ".$this->table_name.
            " WHERE id = ".$this->intval($this->id).
            " AND name = ".$this->var2str($this->name).
            " AND plugin = ".$this->var2str($this->plugin).";";
        return $this->db->exec($sql);
    }
}
