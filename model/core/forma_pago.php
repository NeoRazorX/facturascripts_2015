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
 * Forma de pago de una factura, albarán, pedido o presupuesto.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class forma_pago extends \fs_model
{
   /**
    * Clave primaria. Varchar (10).
    * @var type 
    */
   public $codpago;
   public $descripcion;
   
   /**
    * Pagados -> marca las facturas generadas como pagadas.
    * @var type 
    */
   public $genrecibos;
   
   /**
    * Código de la cuenta bancaria asociada.
    * @var type 
    */
   public $codcuenta;
   
   /**
    * Para indicar si hay que mostrar la cuenta bancaria del cliente.
    * @var type 
    */
   public $domiciliado;
   
   /**
    * Sirve para generar la fecha de vencimiento de las facturas.
    * @var type 
    */
   public $vencimiento;
   
   public function __construct($f = FALSE)
   {
      parent::__construct('formaspago');
      if($f)
      {
         $this->codpago = $f['codpago'];
         $this->descripcion = $f['descripcion'];
         $this->genrecibos = $f['genrecibos'];
         $this->codcuenta = $f['codcuenta'];
         $this->domiciliado = $this->str2bool($f['domiciliado']);
         $this->vencimiento = $f['vencimiento'];
      }
      else
      {
         $this->codpago = NULL;
         $this->descripcion = '';
         $this->genrecibos = 'Emitidos';
         $this->codcuenta = '';
         $this->domiciliado = FALSE;
         $this->vencimiento = '+1month';
      }
   }
   
   public function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado,vencimiento)"
              . " VALUES ('CONT','Al contado','Pagados',NULL,FALSE,'+1month')"
              . ",('TRANS','Transferencia bancaria','Emitidos',NULL,FALSE,'+1month')"
              . ",('TARJETA','Tarjeta de crédito','Pagados',NULL,FALSE,'+1week')"
              . ",('PAYPAL','PayPal','Pagados',NULL,FALSE,'+1week');";
   }
   
   /**
    * Devuelve la URL donde ver/modificar los datos
    * @return string
    */
   public function url()
   {
      return 'index.php?page=contabilidad_formas_pago';
   }
   
   /**
    * Devuelve TRUE si esta es la forma de pago predeterminada de la empresa
    * @return type
    */
   public function is_default()
   {
      return ( $this->codpago == $this->default_items->codpago() );
   }
   
   /**
    * Devuelve la forma de pago con codpago = $cod
    * @param type $cod
    * @return \FacturaScripts\model\forma_pago|boolean
    */
   public function get($cod)
   {
      $pago = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = ".$this->var2str($cod).";");
      if($pago)
      {
         return new \forma_pago($pago[0]);
      }
      else
         return FALSE;
   }
   
   /**
    * Devuelve TRUE si la forma de pago existe
    * @return boolean
    */
   public function exists()
   {
      if( is_null($this->codpago) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpago = ".$this->var2str($this->codpago).";");
   }
   
   /**
    * Comprueba la validez de los datos de la forma de pago.
    */
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      /// comprobamos la validez del vencimiento
      $fecha1 = Date('d-m-Y');
      $fecha2 = Date('d-m-Y', strtotime($this->vencimiento));
      if( strtotime($fecha1) > strtotime($fecha2) )
      {
         /// vencimiento no válido, asignamos el predeterminado
         $this->new_error_msg('Vencimiento no válido.');
         $this->vencimiento = '+1month';
      }
   }
   
   /**
    * Guarda los datos en la base de datos
    * @return type
    */
   public function save()
   {
      $this->clean_cache();
      $this->test();
      
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).
                 ", genrecibos = ".$this->var2str($this->genrecibos).
                 ", codcuenta = ".$this->var2str($this->codcuenta).
                 ", domiciliado = ".$this->var2str($this->domiciliado).
                 ", vencimiento = ".$this->var2str($this->vencimiento).
                 "  WHERE codpago = ".$this->var2str($this->codpago).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codpago,descripcion,genrecibos,codcuenta,domiciliado,vencimiento)
                 VALUES (".$this->var2str($this->codpago).
                 ",".$this->var2str($this->descripcion).
                 ",".$this->var2str($this->genrecibos).
                 ",".$this->var2str($this->codcuenta).
                 ",".$this->var2str($this->domiciliado).
                 ",".$this->var2str($this->vencimiento).");";
      }
      
      return $this->db->exec($sql);
   }
   
   /**
    * Elimina la forma de pago
    * @return type
    */
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpago = ".$this->var2str($this->codpago).";");
   }
   
   /**
    * Limpia la caché
    */
   private function clean_cache()
   {
      $this->cache->delete('m_forma_pago_all');
   }
   
   /**
    * Devuelve un array con todas las formas de pago
    * @return \forma_pago
    */
   public function all()
   {
      /// Leemos la lista de la caché
      $listaformas = $this->cache->get_array('m_forma_pago_all');
      if(!$listaformas)
      {
         /// si no está en caché, buscamos en la base de datos
         $formas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY descripcion ASC;");
         if($formas)
         {
            foreach($formas as $f)
            {
               $listaformas[] = new \forma_pago($f);
            }
         }
         
         /// guardamos la lista en caché
         $this->cache->set('m_forma_pago_all', $listaformas);
      }
      
      return $listaformas;
   }
}
