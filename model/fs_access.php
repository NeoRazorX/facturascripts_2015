<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Define que un usuario tiene acceso a una página concreta
 * y si tiene permisos de eliminación en esa página.
 */
class fs_access extends fs_model
{
   /**
    * Nick del usuario.
    * @var type 
    */
   public $fs_user;
   
   /**
    * Nombre de la página (nombre del controlador).
    * @var type 
    */
   public $fs_page;
   
   /**
    * Otorga permisos al usuario a eliminar elementos en la página.
    * @var type 
    */
   public $allow_delete;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('fs_access');
      if($a)
      {
         $this->fs_user = $a['fs_user'];
         $this->fs_page = $a['fs_page'];
         $this->allow_delete = $this->str2bool($a['allow_delete']);
      }
      else
      {
         $this->fs_user = NULL;
         $this->fs_page = NULL;
         $this->allow_delete = FALSE;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->fs_page) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE fs_user = ".$this->var2str($this->fs_user).
                 " AND fs_page = ".$this->var2str($this->fs_page).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET allow_delete = ".$this->var2str($this->allow_delete)."
            WHERE fs_user = ".$this->var2str($this->fs_user)." AND fs_page = ".$this->var2str($this->fs_page).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (fs_user,fs_page,allow_delete) VALUES
            (".$this->var2str($this->fs_user).",".$this->var2str($this->fs_page).",".$this->var2str($this->allow_delete).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE fs_user = ".$this->var2str($this->fs_user).
              " AND fs_page = ".$this->var2str($this->fs_page).";");
   }
   
   public function all_from_nick($n='')
   {
      $accesslist = array();
      
      $access = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fs_user = ".$this->var2str($n).";");
      if($access)
      {
         foreach($access as $a)
            $accesslist[] = new fs_access($a);
      }
      
      return $accesslist;
   }
}
