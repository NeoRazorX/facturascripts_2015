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
 * Description of fs_roles_users
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class fs_roles_users extends fs_model{
    /**
     *
     * @var type integer
     */
    public $id;
    /**
     *
     * @var type varchar(12)
     */
    public $nick;
    /**
     *
     * @var type boolean
     */
    public $estado;
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
        parent::__construct('fs_roles_users');
        if($t){
            $this->id = $t['id'];
            $this->nick = $t['nick'];
            $this->estado = $this->str2bool($t['estado']);
            $this->fecha_creacion = \date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_modificacion = \date('d-m-Y H:i:s', strtotime($t['fecha_modificacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
        }else{
            $this->id = NULL;
            $this->nick = NULL;
            $this->estado = FALSE;
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
                $linea = new fs_roles_users($d);
                $lista[] = $linea;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     * Obtenemos la información de un rol y un usuario
     * @param type $id
     * @return type object or false
     */
    public function get($id,$nick){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id)." AND nick = ".$this->var2str($nick).";";
        $data = $this->db->select($sql);
        return ($data)?new fs_roles_users($data[0]):false;
    }

    /**
     * Funcion para obtener listado de información por rol o usuario
     * @param type $type rol|user
     * @param type $value integer|string
     * @return boolean|\fs_roles_users
     */
    public function get_by($type,$value){
        switch ($type){
            case "rol":
                $where = " WHERE id = ".$this->intval($value);
                $order = "nick,estado";
                break;
            case "user":
                $where = " WHERE nick = ".$this->var2str($value);
                $order = "nick,estado,id";
                break;
            default :
                $where  = "";
                $order = "id,estado,nick";
                break;
        }
        $sql = "SELECT * FROM ".$this->table_name.$where." ORDER BY ".$order.";";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach ($data as $d){
                $linea = new fs_roles_users($d);
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
        if(is_null($this->id) AND is_null($this->nick)){
            return false;
        }else{
            return $this->get($this->id,$this->nick);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                "estado = ".$this->var2str($this->estado).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion)." ".
                " WHERE id = ".$this->intval($this->id).
                " AND nick = ".$this->var2str($this->nick).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id, nick, estado, fecha_creacion, usuario_creacion) VALUES (".
                $this->intval($this->id).",".
                $this->var2str($this->nick).",".
                $this->var2str($this->estado).",".
                $this->var2str($this->fecha_creacion).",".
                $this->var2str($this->usuario_creacion).");";
        }
        return $this->db->exec($sql);
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name.
            " WHERE id = ".$this->intval($this->id).
            " AND nick = ".$this->var2str($this->nick).";";
        return $this->db->exec($sql);
    }
}
