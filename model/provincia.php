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
 * Un país, por ejemplo España.
 */
class provincia extends fs_model
{
   /**
    * Clave primaria. Varchar(3).
    * @var type Código alfa-3 del país.
    * http://es.wikipedia.org/wiki/ISO_3166-1
    */
   public $codprovincia;
   
   /**
    * Nombre de la provincia.
    * @var type 
    */
   public $nombre;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('provincias');
      if($p)
      {
         $this->codprovincia = $p['codprovincia'];         
         $this->nombre = $p['nombre'];
         $this->codpais = $p['codpais'];
      }
      else
      {
         $this->codprovincia = NULL;         
         $this->nombre = '';
         $this->codpais = NULL;
      }
   }

   public function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codprovincia,nombre,codpais) VALUES ".
           "(2,'ALBACETE','ESP'),(3,'ALICANTE','ESP'),(4,'ALMERIA','ESP'),(5,'AVILA','ESP'),(6,'BADAJOZ','ESP'),(7,'ILLES BALEARES','ESP'),".
           "(8,'BARCELONA','ESP'),(9,'BURGOS','ESP'),(10,'CACERES','ESP'),(11,'CADIZ','ESP'),".
           "(12,'CASTELLON','ESP'),(13,'CIUDAD REAL','ESP'),(14,'CORDOBA','ESP'),(15,'CORUÑA','ESP'),(16,'CUENCA','ESP'),".
           "(17,'GIRONA','ESP'),(18,'GRANADA','ESP'),(19,'GUADALAJARA','ESP'),(20,'GUIPUZCOA','ESP'),(21,'HUELVA','ESP'),".
           "(22,'HUESCA','ESP'),(23,'JAEN','ESP'),(24,'LEON','ESP'),(25,'LLEIDA','ESP'),(26,'LA RIOJA','ESP'),(27,'LUGO','ESP'),".
           "(28,'MADRID','ESP'),(29,'MALAGA','ESP'),(30,'MURCIA','ESP'),(31,'NAVARRA','ESP'),(32,'OURENSE','ESP'),".
           "(33,'ASTURIAS','ESP'),(34,'PALENCIA','ESP'),(35,'LAS PALMAS','ESP'),(36,'PONTEVEDRA','ESP'),(37,'SALAMANCA','ESP'),".
           "(38,'S.C. TENERIFE','ESP'),(39,'CANTABRIA','ESP'),(40,'SEGOVIA','ESP'),(41,'SEVILLA','ESP'),(42,'SORIA','ESP'),".
           "(43,'TARRAGONA','ESP'),(44,'TERUEL','ESP'),(45,'TOLEDO','ESP'),(46,'VALENCIA','ESP'),(47,'VALLADOLID','ESP'),(48,'VIZCAYA','ESP'),".
           "(49,'ZAMORA','ESP'),(50,'ZARAGOZA','ESP'),(51,'CEUTA','ESP');";
          
   }
   
   public function url()
   {
      if( is_null($this->codprovincia) )
      {
         return 'index.php?page=admin_provincias';
      }
      else
         return 'index.php?page=admin_provincias#'.$this->codprovincia;
   }
   
   public function is_default()
   {
      return ( $this->codprovincia == $this->default_items->codprovincia() );
   }
   
   public function get($cod)
   {
      $codprovincia = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codprovincia = ".$this->var2str($cod).";");
      if($codprovincia)
      {
         return new provincia($codprovincia[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codprovincia) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codprovincia = ".$this->var2str($this->codprovincia).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codprovincia = trim($this->codprovincia);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9]{1,20}$/i", $this->codprovincia) )
      {
         $this->new_error_msg("Código del país no válido: ".$this->codprovincia);
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre del país no válido.");
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codpais = ".$this->var2str($this->codpais).
                    ", nombre = ".$this->var2str($this->nombre).
                    "  WHERE codprovincia = ".$this->var2str($this->codprovincia).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codprovincia,codpais,nombre) VALUES
                     (".$this->var2str($this->codprovincia).
                    ",".$this->var2str($this->codpais).
                    ",".$this->var2str($this->nombre).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codprovincia = ".$this->var2str($this->codprovincia).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_provincia_all');
   }
   
   public function all()
   {
      $listap = $this->cache->get_array('m_provincia_all');
      if( !$listap )
      {
         $provincia = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
         if($provincia)
         {
            foreach($provincia as $p)
               $listap[] = new provincia($p);
         }
         $this->cache->set('m_provincia_all', $listap);
      }
      
      return $listap;
   }
   
   public function search($query, $offset=0)
   {
      $plist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE lower(nombre) LIKE '%".$query."%' OR codprovincia LIKE '%".$query."%' ORDER BY nombre ASC";
      
      $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $plist[] = new provincia($d);
      }
      
      return $plist;
   }
}
