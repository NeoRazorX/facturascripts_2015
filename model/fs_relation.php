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
 * Description of fs_relation
 *
 * @author carlos
 */
class fs_relation extends fs_model
{
   public $id;
   public $table1;
   public $id1;
   public $table2;
   public $id2;
   public $return_url;
   
   public function __construct($r = FALSE)
   {
      parent::__construct('fs_relations');
      if($r)
      {
         $this->id = $this->intval($r['id']);
         $this->table1 = $r['table1'];
         $this->id1 = $r['id1'];
         $this->table2 = $r['table2'];
         $this->id2 = $r['id2'];
         $this->return_url = $r['return_url'];
      }
      else
      {
         $this->id = NULL;
         $this->table1 = NULL;
         $this->id1 = NULL;
         $this->table2 = NULL;
         $this->id2 = NULL;
         $this->return_url = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM fs_relations WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new fs_relation($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM fs_relations WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE fs_relations SET table1 = ".$this->var2str($this->table1)
                 .", id1 = ".$this->var2str($this->id1)
                 .", table2 = ".$this->var2str($this->table2)
                 .", id2 = ".$this->var2str($this->id2)
                 .", return_url = ".$this->var2str($this->return_url)
                 ." WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO fs_relations (table1,id1,table2,id2,return_url) VALUES "
                 . "(".$this->var2str($this->table1)
                 . ",".$this->var2str($this->id1)
                 . ",".$this->var2str($this->table2)
                 . ",".$this->var2str($this->id2)
                 . ",".$this->var2str($this->return_url).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM fs_relations WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_for($table, $id)
   {
      $rlist = array();
      $sql = "SELECT * FROM fs_relations WHERE (table1 = ".$this->var2str($table)
              ." AND id1 = ".$this->var2str($id).") OR (table2 = ".$this->var2str($table)
              ." AND id2 = ".$this->var2str($id).") ORDER BY id DESC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $rlist[] = new fs_relation($d);
      }
      
      return $rlist;
   }
}
