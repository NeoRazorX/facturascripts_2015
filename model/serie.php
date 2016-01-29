<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Una serie de facturación o contabilidad, para tener distinta numeración
 * en cada serie.
 */
class serie extends fs_model
{
   /**
    * Clave primaria. Varchar (2).
    * @var type 
    */
   public $codserie;
   public $descripcion;
   
   /**
    * TRUE -> las facturas asociadas no encluyen IVA.
    * @var type 
    */
   public $siniva;
   
   /**
    * % de retención IRPF de las facturas asociadas.
    * @var type 
    */
   public $irpf;
   
   /**
    * ejercicio para el que asignamos la numeración inicial de la serie.
    * @var type 
    */
   public $codejercicio;
   
   /**
    * numeración inicial para las facturas de esta serie.
    * @var type 
    */
   public $numfactura;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('series');
      if($s)
      {
         $this->codserie = $s['codserie'];
         $this->descripcion = $s['descripcion'];
         $this->siniva = $this->str2bool($s['siniva']);
         $this->irpf = floatval($s['irpf']);
         $this->codejercicio = $s['codejercicio'];
         $this->numfactura = max( array(1, intval($s['numfactura'])) );
      }
      else
      {
         $this->codserie = '';
         $this->descripcion = '';
         $this->siniva = FALSE;
         $this->irpf = 0;
         $this->codejercicio = NULL;
         $this->numfactura = 1;
      }
   }
   
   public function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codserie,descripcion,siniva,irpf) VALUES ('A','SERIE A',FALSE,'0');";
   }
   
   public function url()
   {
      if( is_null($this->codserie) )
      {
         return 'index.php?page=contabilidad_series';
      }
      else
         return 'index.php?page=contabilidad_series#'.$this->codserie;
   }
   
   public function is_default()
   {
      return ( $this->codserie == $this->default_items->codserie() );
   }
   
   /**
    * Devuelve la serie solicitada o false si no la encuentra.
    * @param type $cod
    * @return \serie|boolean
    */
   public function get($cod)
   {
      $serie = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codserie = ".$this->var2str($cod).";");
      if($serie)
      {
         return new serie($serie[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codserie) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codserie = ".$this->var2str($this->codserie).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codserie = trim($this->codserie);
      $this->descripcion = $this->no_html($this->descripcion);
      
      if($this->numfactura < 1)
      {
         $this->numfactura = 1;
      }
      
      if( !preg_match("/^[A-Z0-9]{1,2}$/i", $this->codserie) )
      {
         $this->new_error_msg("Código de serie no válido.");
      }
      else if( strlen($this->descripcion) < 1 OR strlen($this->descripcion) > 100 )
      {
         $this->new_error_msg("Descripción de serie no válida.");
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
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion)
                    .", siniva = ".$this->var2str($this->siniva)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", codejercicio = ".$this->var2str($this->codejercicio)
                    .", numfactura = ".$this->var2str($this->numfactura)
                    ." WHERE codserie = ".$this->var2str($this->codserie).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codserie,descripcion,siniva,irpf,codejercicio,numfactura) VALUES "
                    . "(".$this->var2str($this->codserie)
                    . ",".$this->var2str($this->descripcion)
                    . ",".$this->var2str($this->siniva)
                    . ",".$this->var2str($this->irpf)
                    . ",".$this->var2str($this->codejercicio)
                    . ",".$this->var2str($this->numfactura).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codserie = ".$this->var2str($this->codserie).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_serie_all');
   }
   
   public function all()
   {
      $serielist = $this->cache->get_array('m_serie_all');
      if(!$serielist)
      {
         $series = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codserie ASC;");
         if($series)
         {
            foreach($series as $s)
               $serielist[] = new serie($s);
         }
         $this->cache->set('m_serie_all', $serielist);
      }
      
      return $serielist;
   }
}
