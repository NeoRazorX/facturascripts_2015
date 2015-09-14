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
 * El agente/empleado es el que se asocia a un albarán, factura o caja.
 * Cada usuario puede estar asociado a un agente, y un agente puede
 * estar asociado a varios usuarios.
 */
class agente extends fs_model
{
   /**
    * Clave primaria. Varchar (10).
    * @var type
    */
   public $codagente;
   
   /**
    * Identificador fiscal.
    * @var type 
    */
   public $dnicif;
   public $nombre;
   public $apellidos;
   
   /**
    * Todavía sin uso.
    * @var type 
    */
   public $coddepartamento;
   public $email;
   public $fax;
   public $telefono;
   public $codpostal;
   public $codpais;
   public $provincia;
   public $ciudad;
   public $direccion;
   
   public $seg_social;
   public $cargo;
   public $banco;
   public $f_nacimiento;
   public $f_alta;
   public $f_baja;

   /**
    * Porcentaje de comisión del agente. Se utiliza en presupuestos, pedidos, albaranes y facturas.
    * @var type 
    */
   public $porcomision;
   
   /**
    * Todavía sin uso.
    * @var type 
    */
   public $irpf;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('agentes');
      if($a)
      {
         $this->codagente = $a['codagente'];
         $this->nombre = $a['nombre'];
         $this->apellidos = $a['apellidos'];
         $this->dnicif = $a['dnicif'];
         $this->coddepartamento = $a['coddepartamento'];
         $this->email = $a['email'];
         $this->fax = $a['fax'];
         $this->telefono = $a['telefono'];
         $this->codpostal = $a['codpostal'];
         $this->codpais = $a['codpais'];
         $this->provincia = $a['provincia'];
         $this->ciudad = $a['ciudad'];
         $this->direccion = $a['direccion'];
         $this->porcomision = floatval($a['porcomision']);
         $this->irpf = floatval($a['irpf']);
         $this->seg_social = $a['seg_social'];
         $this->banco = $a['banco'];
         $this->cargo =$a['cargo'];
         
         $this->f_alta = NULL;
         if($a['f_alta'] != '')
         {
            $this->f_alta = Date('d-m-Y', strtotime($a['f_alta']));
         }
         
         $this->f_baja = NULL;
         if($a['f_baja'] != '')
         {
            $this->f_baja = Date('d-m-Y', strtotime($a['f_baja']));
         }
         
         $this->f_nacimiento = NULL;
         if($a['f_nacimiento'] != '')
         {
            $this->f_nacimiento = Date('d-m-Y', strtotime($a['f_nacimiento']));
         }
      }
      else
      {
         $this->codagente = NULL;
         $this->nombre = '';
         $this->apellidos = '';
         $this->dnicif = '';
         $this->coddepartamento = NULL;
         $this->email = NULL;
         $this->fax = NULL;
         $this->telefono = NULL;
         $this->codpostal = NULL;
         $this->codpais = NULL;
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->direccion = NULL;
         $this->porcomision = 0;
         $this->irpf = 0;
         $this->seg_social = NULL;
         $this->banco = NULL;
         $this->cargo = NULL;
         $this->f_alta = Date('d-m-Y');
         $this->f_baja = NULL;
         $this->f_nacimiento = Date('d-m-Y');        
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codagente,nombre,apellidos,dnicif)
         VALUES ('1','Paco','Pepe','00000014Z');";
   }
   
   public function get_fullname()
   {
      return $this->nombre." ".$this->apellidos;
   }
   
   public function get_new_codigo()
   {
      $sql = "SELECT MAX(".$this->db->sql_to_int('codagente').") as cod FROM ".$this->table_name.";";
      $cod = $this->db->select($sql);
      if($cod)
      {
         return 1 + intval($cod[0]['cod']);
      }
      else
         return 1;
   }
   
   public function url()
   {
      if( is_null($this->codagente) )
      {
         return "index.php?page=admin_agentes";
      }
      else
         return "index.php?page=admin_agente&cod=".$this->codagente;
   }
   
   public function get($cod)
   {
      $a = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codagente = ".$this->var2str($cod).";");
      if($a)
      {
         return new agente($a[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codagente) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codagente = ".$this->var2str($this->codagente).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codagente = trim($this->codagente);
      $this->nombre = $this->no_html($this->nombre);
      $this->apellidos = $this->no_html($this->apellidos);
      $this->dnicif = $this->no_html($this->dnicif);
      $this->telefono = $this->no_html($this->telefono);
      $this->email = $this->no_html($this->email);
      
      if( strlen($this->codagente) < 1 OR strlen($this->codagente) > 10 )
      {
         $this->new_error_msg("Código de agente no válido. Debe tener entre 1 y 10 caracteres.");
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 50 )
      {
         $this->new_error_msg("El nombre de empleado no puede superar los 50 caracteres.");
      }
      else if( strlen($this->apellidos) < 1 OR strlen($this->apellidos) > 100 )
      {
         $this->new_error_msg("Los apellidos del empleado no pueden superar los 100 caracteres.");
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
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).
                    ", apellidos = ".$this->var2str($this->apellidos).
                    ", dnicif = ".$this->var2str($this->dnicif).
                    ", telefono = ".$this->var2str($this->telefono).
                    ", email = ".$this->var2str($this->email).
                    ", cargo = ".$this->var2str($this->cargo).
                    ", provincia = ".$this->var2str($this->provincia).
                    ", ciudad = ".$this->var2str($this->ciudad).
                    ", direccion = ".$this->var2str($this->direccion).
                    ", f_nacimiento = ".$this->var2str($this->f_nacimiento).
                    ", f_alta = ".$this->var2str($this->f_alta).
                    ", f_baja = ".$this->var2str($this->f_baja).
                    ", seg_social = ".$this->var2str($this->seg_social).
                    ", banco = ".$this->var2str($this->banco).
                    ", porcomision = ".$this->var2str($this->porcomision).
                    "  WHERE codagente = ".$this->var2str($this->codagente).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codagente,nombre,apellidos,dnicif,telefono,
               email,cargo,provincia,ciudad,direccion,f_nacimiento,f_alta,f_baja,seg_social,banco,porcomision)
               VALUES (".$this->var2str($this->codagente).
                    ",".$this->var2str($this->nombre).
                    ",".$this->var2str($this->apellidos).
                    ",".$this->var2str($this->dnicif).
                    ",".$this->var2str($this->telefono).
                    ",".$this->var2str($this->email).
                    ",".$this->var2str($this->cargo).
                    ",".$this->var2str($this->provincia).
                    ",".$this->var2str($this->ciudad).
                    ",".$this->var2str($this->direccion).
                    ",".$this->var2str($this->f_nacimiento).
                    ",".$this->var2str($this->f_alta).
                    ",".$this->var2str($this->f_baja).
                    ",".$this->var2str($this->seg_social).
                    ",".$this->var2str($this->banco).
                    ",".$this->var2str($this->porcomision).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codagente = ".$this->var2str($this->codagente).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_agente_all');
   }
   
   public function all()
   {
      $listagentes = $this->cache->get_array('m_agente_all');
      if(!$listagentes)
      {
         $agentes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
         if($agentes)
         {
            foreach($agentes as $a)
               $listagentes[] = new agente($a);
         }
         $this->cache->set('m_agente_all', $listagentes);
      }
      
      return $listagentes;
   }
}
