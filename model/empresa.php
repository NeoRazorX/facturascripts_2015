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
 * Esta clase almacena los principales datos de la empresa.
 */
class empresa extends fs_model
{
   /**
    * Clave primaria.
    * @var type 
    */
   public $id;
   
   public $stockpedidos;
   
   /**
    * TRUE -> activa la contabilidad integrada. Se genera el asiento correspondiente
    * cada vez que se crea/modifica una factura.
    * @var type 
    */
   public $contintegrada;
   
   /**
    * TRUE -> activa el uso de recargo de equivalencia en los albaranes y facturas de compra.
    * @var type 
    */
   public $recequivalencia;
   
   /**
    * Código de la serie por defecto.
    * @var type 
    */
   public $codserie;
   
   /**
    * Código del almacén predeterminado.
    * @var type 
    */
   public $codalmacen;
   
   /**
    * Código de la forma de pago predeterminada.
    * @var type 
    */
   public $codpago;
   
   /**
    * Código de la divisa predeterminada.
    * @var type 
    */
   public $coddivisa;
   
   /**
    * Código del ejercicio predeterminado.
    * @var type 
    */
   public $codejercicio;
   
   public $web;
   public $email;
   
   /**
    * @deprecated since version 2015.053
    * @var type 
    */
   public $email_firma;
   
   /**
    * @deprecated since version 2015.053
    * @var type 
    */
   public $email_password;
   
   public $fax;
   public $telefono;
   public $codpais;
   public $apartado;
   public $provincia;
   public $ciudad;
   public $codpostal;
   public $direccion;
   
   /**
    * Nombre del administrador de la empresa.
    * @var type 
    */
   public $administrador;
   
   /**
    * Actualmente sin uso.
    * @var type 
    */
   public $codedi;
   
   public $cifnif;
   
   public $nombre;
   
   /**
    *
    * @var type Nombre a mostrar en el menú de facturaScripts.
    */
   public $nombrecorto;
   
   public $lema;
   
   public $horario;
   
   /**
    * Texto al pié de las facturas de venta.
    * @var type 
    */
   public $pie_factura;
   
   public $email_config;
   
   public function __construct()
   {
      parent::__construct('empresa');
      
      /// leemos los datos de la empresa de memcache o de la base de datos
      $e = $this->cache->get_array('empresa');
      if( !$e )
      {
         $e = $this->db->select("SELECT * FROM ".$this->table_name.";");
         $this->cache->set('empresa', $e);
      }
      
      if($e)
      {
         $this->id = $this->intval($e[0]['id']);
         $this->stockpedidos = $this->str2bool($e[0]['stockpedidos']);
         $this->contintegrada = $this->str2bool($e[0]['contintegrada']);
         $this->recequivalencia = $this->str2bool($e[0]['recequivalencia']);
         $this->codserie = $e[0]['codserie'];
         $this->codalmacen = $e[0]['codalmacen'];
         $this->codpago = $e[0]['codpago'];
         $this->coddivisa = $e[0]['coddivisa'];
         $this->codejercicio = $e[0]['codejercicio'];
         $this->web = $e[0]['web'];
         $this->email = $e[0]['email'];
         $this->fax = $e[0]['fax'];
         $this->telefono = $e[0]['telefono'];
         $this->codpais = $e[0]['codpais'];
         $this->apartado = $e[0]['apartado'];
         $this->provincia = $e[0]['provincia'];
         $this->ciudad = $e[0]['ciudad'];
         $this->codpostal = $e[0]['codpostal'];
         $this->direccion = $e[0]['direccion'];
         $this->administrador = $e[0]['administrador'];
         $this->codedi = $e[0]['codedi'];
         $this->cifnif = $e[0]['cifnif'];
         $this->nombre = $e[0]['nombre'];
         $this->nombrecorto = $e[0]['nombrecorto'];
         $this->lema = $e[0]['lema'];
         $this->horario = $e[0]['horario'];
         $this->pie_factura = $e[0]['pie_factura'];
         
         /// cargamos las opciones de email por defecto
         $this->email_config = array(
             'mail_password' => '',
             'mail_bcc' => '',
             'mail_firma' => "\n---\nEnviado con FacturaScripts",
             'mail_host' => 'smtp.gmail.com',
             'mail_port' => '465',
             'mail_enc' => 'ssl',
             'mail_user' => '',
             'mail_low_security' => FALSE,
         );
         
         /// añadimos compatibilidad hacia atrás
         if( isset($e[0]['email_password']) )
         {
            $this->email_password = $this->email_config['mail_password'] = $e[0]['email_password'];
         }
         if( isset($e[0]['email_firma']) )
         {
            $this->email_firma = $this->email_config['mail_firma'] = $e[0]['email_firma'];
         }
         
         $fsvar = new fs_var();
         $this->email_config = $fsvar->array_get($this->email_config, FALSE);
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      $e = mt_rand(1, 9999);
      return "INSERT INTO ".$this->table_name." (stockpedidos,contintegrada,recequivalencia,codserie,"
              ."codalmacen,codpago,coddivisa,codejercicio,web,email,fax,telefono,codpais,apartado,provincia,"
              ."ciudad,codpostal,direccion,administrador,codedi,cifnif,nombre,nombrecorto,lema,horario)"
              ."VALUES (NULL,FALSE,NULL,'A','ALG','CONT','EUR','0001','https://www.facturascripts.com',"
              ."NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','',NULL,'00000014Z','Empresa ".$e." S.L.',"
              ."'E-".$e."','','');";
   }
   
   public function url()
   {
      return 'index.php?page=admin_empresa';
   }
   
   public function can_send_mail()
   {
      if($this->email AND $this->email_config['mail_password'])
      {
         return TRUE;
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->nombre = $this->no_html($this->nombre);
      $this->nombrecorto = $this->no_html($this->nombrecorto);
      $this->administrador = $this->no_html($this->administrador);
      $this->apartado = $this->no_html($this->apartado);
      $this->cifnif = $this->no_html($this->cifnif);
      $this->ciudad = $this->no_html($this->ciudad);
      $this->codpostal = $this->no_html($this->codpostal);
      $this->direccion = $this->no_html($this->direccion);
      $this->email = $this->no_html($this->email);
      $this->fax = $this->no_html($this->fax);
      $this->horario = $this->no_html($this->horario);
      $this->lema = $this->no_html($this->lema);
      $this->pie_factura = $this->no_html($this->pie_factura);
      $this->provincia = $this->no_html($this->provincia);
      $this->telefono = $this->no_html($this->telefono);
      $this->web = $this->no_html($this->web);
      
      if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre de empresa no válido.");
      }
      else if( strlen($this->nombre) < strlen($this->nombrecorto) )
      {
         $this->new_error_msg("El Nombre Corto debe ser más corto que el Nombre.");
      }
      else
      {
         $status = TRUE;
      }
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         /// guardamos la configuración de email
         $fsvar = new fs_var();
         $fsvar->array_save($this->email_config);
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre)
                 .", nombrecorto = ".$this->var2str($this->nombrecorto)
                 .", cifnif = ".$this->var2str($this->cifnif)
                 .", codedi = ".$this->var2str($this->codedi)
                 .", administrador = ".$this->var2str($this->administrador)
                 .", direccion = ".$this->var2str($this->direccion)
                 .", codpostal = ".$this->var2str($this->codpostal)
                 .", ciudad = ".$this->var2str($this->ciudad)
                 .", provincia = ".$this->var2str($this->provincia)
                 .", apartado = ".$this->var2str($this->apartado)
                 .", codpais = ".$this->var2str($this->codpais)
                 .", telefono = ".$this->var2str($this->telefono)
                 .", fax = ".$this->var2str($this->fax)
                 .", email = ".$this->var2str($this->email)
                 .", web = ".$this->var2str($this->web)
                 .", codejercicio = ".$this->var2str($this->codejercicio)
                 .", coddivisa = ".$this->var2str($this->coddivisa)
                 .", codpago = ".$this->var2str($this->codpago)
                 .", codalmacen = ".$this->var2str($this->codalmacen)
                 .", codserie = ".$this->var2str($this->codserie)
                 .", recequivalencia = ".$this->var2str($this->recequivalencia)
                 .", contintegrada = ".$this->var2str($this->contintegrada)
                 .", stockpedidos = ".$this->var2str($this->stockpedidos)
                 .", lema = ".$this->var2str($this->lema)
                 .", horario = ".$this->var2str($this->horario)
                 .", pie_factura = ".$this->var2str($this->pie_factura)
                 ."  WHERE id = ".$this->var2str($this->id).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (stockpedidos,contintegrada,recequivalencia,codserie,
               codalmacen,codpago,coddivisa,codejercicio,web,email,fax,telefono,
               codpais,apartado,provincia,ciudad,codpostal,direccion,administrador,codedi,cifnif,nombre,
               nombrecorto,lema,horario,pie_factura) VALUES (".$this->var2str($this->stockpedidos)
                    .",".$this->var2str($this->contintegrada)
                    .",".$this->var2str($this->recequivalencia)
                    .",".$this->var2str($this->codserie)
                    .",".$this->var2str($this->codalmacen)
                    .",".$this->var2str($this->codpago)
                    .",".$this->var2str($this->coddivisa)
                    .",".$this->var2str($this->codejercicio)
                    .",".$this->var2str($this->web)
                    .",".$this->var2str($this->email)
                    .",".$this->var2str($this->fax)
                    .",".$this->var2str($this->telefono)
                    .",".$this->var2str($this->codpais)
                    .",".$this->var2str($this->apartado)
                    .",".$this->var2str($this->provincia)
                    .",".$this->var2str($this->ciudad)
                    .",".$this->var2str($this->codpostal)
                    .",".$this->var2str($this->direccion)
                    .",".$this->var2str($this->administrador)
                    .",".$this->var2str($this->codedi)
                    .",".$this->var2str($this->cifnif)
                    .",".$this->var2str($this->nombre)
                    .",".$this->var2str($this->nombrecorto)
                    .",".$this->var2str($this->lema)
                    .",".$this->var2str($this->horario)
                    .",".$this->var2str($this->pie_factura).");";
            
            if( $this->db->exec($sql) )
            {
               $this->id = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
      {
         return FALSE;
      }
   }
   
   public function delete()
   {
      /// no se puede borrar la empresa
      return FALSE;
   }
   
   public function clean_cache()
   {
      $this->cache->delete('empresa');
   }
}
