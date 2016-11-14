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

/**
 * Description of fs_roles
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class fs_roles extends fs_model{
    /**
     *
     * @var type serial
     */
    public $id;
    /**
     *
     * @var type varchar(100)
     */
    public $descripcion;
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
        parent::__construct('fs_roles');
        if($t){
            $this->id = $t['id'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
            $this->fecha_creacion = \date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_modificacion = \date('d-m-Y H:i:s', strtotime($t['fecha_modificacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
        }else{
            $this->id = NULL;
            $this->descripcion = NULL;
            $this->estado = FALSE;
            $this->fecha_creacion = \date('d-m-Y H:i:s');
            $this->usuario_creacion = NULL;
            $this->fecha_modificacion = NULL;
            $this->usuario_modificacion = NULL;
        }
    }

    public function url(){
        if(!empty($this->id)){
            return FS_PATH.'index.php?page=admin_roles&type=detalle&id='.$this->id;
        }else{
            return FS_PATH.'index.php?page=admin_roles';
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
                $linea = new fs_roles($d);
                $lista[] = $linea;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     * Obtenemos la información de un Rol
     * @param type $id
     * @return type object or false
     */
    public function get($id){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id).";";
        $data = $this->db->select($sql);
        return ($data)?new fs_roles($data[0]):false;
    }

    /**
     *
     * @return boolean
     */
    public function exists() {
        if(is_null($this->id)){
            return false;
        }else{
            return $this->get($this->id);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                "descripcion = ".$this->var2str($this->descripcion).", ".
                "estado = ".$this->var2str($this->estado).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion)." ".
                " WHERE id = ".$this->intval($this->id).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (descripcion, estado, fecha_creacion, usuario_creacion) VALUES (".
                $this->var2str($this->descripcion).",".
                $this->var2str($this->estado).",".
                $this->var2str($this->fecha_creacion).",".
                $this->var2str($this->usuario_creacion).");";
            if($this->db->exec($sql)){
                return $this->db->lastval();
            }else{
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->id).";";
        return $this->db->exec($sql);
    }

    /**
     * Obtenemos la cantidad de usuarios que están asignados a este rol
     * @return int
     */
    public function asignaciones(){
        $sql = "SELECT count(*) as cantidad FROM fs_roles_users WHERE id = ".$this->intval($this->id).";";
        $data = $this->db->select($sql);
        $cantidad = ($data)?$data[0]['cantidad']:0;
        return $cantidad;
    }

    /**
     * Obtenemos la cantidad de páginas que están agrupadas dentro de este rol
     * @return int
     */
    public function accesos(){
        $sql = "SELECT count(*) as cantidad FROM fs_roles_pages WHERE id = ".$this->intval($this->id).";";
        $data = $this->db->select($sql);
        $cantidad = ($data)?$data[0]['cantidad']:0;
        return $cantidad;
    }


}
