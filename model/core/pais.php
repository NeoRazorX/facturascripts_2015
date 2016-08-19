<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\model;

/**
 * Un país, por ejemplo España.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class pais extends \fs_model
{
   /**
    * Clave primaria. Varchar(3).
    * @var type Código alfa-3 del país.
    * http://es.wikipedia.org/wiki/ISO_3166-1
    */
   public $codpais;
   
   /**
    * Código alfa-2 del país.
    * http://es.wikipedia.org/wiki/ISO_3166-1
    * @var type 
    */
   public $codiso;
   
   /**
    * Nombre del pais.
    * @var type 
    */
   public $nombre;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('paises');
      if($p)
      {
         $this->codpais = $p['codpais'];
         
         $this->codiso = $p['codiso'];
         if($p['codiso'] == '')
         {
            /// si no se ha rellenado codiso, intentamos usar esta lista
            $codigos = array(
                'ESP' => 'ES',
                'ARG' => 'AR',
                'CHL' => 'CL',
                'COL' => 'CO',
                'ECU' => 'EC',
                'MEX' => 'MX',
                'PAN' => 'PA',
                'PER' => 'PE',
                'VEN' => 'VE',
            );
            
            if( isset($codigos[$this->codpais]) )
            {
               $this->codiso = $codigos[$this->codpais];
            }
         }
         
         $this->nombre = $p['nombre'];
      }
      else
      {
         $this->codpais = '';
         $this->codiso = NULL;
         $this->nombre = '';
      }
   }

   public function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codpais,codiso,nombre)"
              . " VALUES ('ESP','ES','España'),"
              . " ('AND','AD','Andorra'),"
              . " ('ARG','AR','Argentina'),"
              . " ('BOL','BO','Bolivia'),"
              . " ('CHL','CL','Chile'),"
              . " ('COL','CO','Colombia'),"
              . " ('CUB','CU','Cuba'),"
              . " ('CRI','CR','Costa Rica'),"
              . " ('DOM','DO','República Dominicana'),"
              . " ('ECU','EC','Ecuador'),"
              . " ('GNQ','GQ','Guinea Ecuatorial'),"
              . " ('SLV','SV','El Salvador'),"
              . " ('GTM','GT','Guatemala'),"
              . " ('HND','HN','Honduras'),"
              . " ('MEX','MX','México'),"
              . " ('NIC','NI','Nicaragua'),"
              . " ('PAN','PA','Panamá'),"
              . " ('PER','PE','Perú'),"
              . " ('PRI','PR','Puerto Rico'),"
              . " ('PRY','PY','Paraguay'),"
              . " ('URY','UY','Uruguay'),"
              . " ('USA','US','Estados Unidos'),"
              . " ('VEN','VE','Venezuela');";
   }
   
   /**
    * Devuelve la URL donde ver/modificar los datos
    * @return string
    */
   public function url()
   {
      if( is_null($this->codpais) )
      {
         return 'index.php?page=admin_paises';
      }
      else
         return 'index.php?page=admin_paises#'.$this->codpais;
   }
   
   /**
    * Devuelve TRUE si el pais es el predeterminado de la empresa
    * @return type
    */
   public function is_default()
   {
      return ( $this->codpais == $this->default_items->codpais() );
   }
   
   /**
    * Devuelve el pais con codpais = $cod
    * @param type $cod
    * @return boolean|\FacturaScripts\model\pais
    */
   public function get($cod)
   {
      $pais = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpais = ".$this->var2str($cod).";");
      if($pais)
      {
         return new \pais($pais[0]);
      }
      else
         return FALSE;
   }
   
   /**
    * Devuelve el pais con codido = $cod
    * @param type $cod
    * @return \pais|boolean
    */
   public function get_by_iso($cod)
   {
      $pais = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codiso = ".$this->var2str($cod).";");
      if($pais)
      {
         return new \pais($pais[0]);
      }
      else
         return FALSE;
   }
   
   /**
    * Devuelve TRUE si el pais existe
    * @return boolean
    */
   public function exists()
   {
      if( is_null($this->codpais) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpais = ".$this->var2str($this->codpais).";");
   }
   
   /**
    * Comprueba los datos del pais, devuelve TRUE si son correctos
    * @return boolean
    */
   public function test()
   {
      $status = FALSE;
      
      $this->codpais = trim($this->codpais);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9]{1,20}$/i", $this->codpais) )
      {
         $this->new_error_msg("Código del país no válido: ".$this->codpais);
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre del país no válido.");
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   /**
    * Guarda los datos en la base de datos
    * @return boolean
    */
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codiso = ".$this->var2str($this->codiso).
                    ", nombre = ".$this->var2str($this->nombre).
                    "  WHERE codpais = ".$this->var2str($this->codpais).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codpais,codiso,nombre) VALUES
                     (".$this->var2str($this->codpais).
                    ",".$this->var2str($this->codiso).
                    ",".$this->var2str($this->nombre).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   /**
    * Elimina el pais (de la base de datos ... por ahora)
    * @return type
    */
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpais = ".$this->var2str($this->codpais).";");
   }
   
   /**
    * Limpia la caché
    */
   private function clean_cache()
   {
      $this->cache->delete('m_pais_all');
   }
   
   /**
    * Devuelve un array con todos los paises
    * @return \pais
    */
   public function all()
   {
      /// Leemos la lista de la caché
      $listap = $this->cache->get_array('m_pais_all');
      if(!$listap)
      {
         /// si no encontramos los datos en caché, leemos de la base de datos
         $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codpais ASC;");
         if($data)
         {
            foreach($data as $p)
            {
               $listap[] = new \pais($p);
            }
         }
         
         /// guardamos la lista en caché
         $this->cache->set('m_pais_all', $listap);
      }
      
      return $listap;
   }
}
