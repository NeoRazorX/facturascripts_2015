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

   public function calculavencimiento_2dias($fecha_inicio, $dias, $dia_de_pago, $dia_pago2) {
      
   }

   /**
    * calculavencimiento: calcula un vencimiento si hay un dia concreto de cobro
    * @param type $fecha_inicio : DateTime Fecha inicial
    * @param type $dias         : Int Numero de dias para cobro
    * @param type $dia_de_pago  : Dia de pago de las facturas
    * @return \FacturaScripts\model\DateTime
    */
   public function calculavencimiento($fecha_inicio, $dias, $dia_de_pago) {
      $fecha_inicio = Date('d-m-Y', strtotime($fecha_inicio.$dias));
      $fecha_inicio = new \DateTime($fecha_inicio);
      $tmp_dia = $fecha_inicio->format("d");
      $tmp_mes = $fecha_inicio->format("m");
      $tmp_año = $fecha_inicio->format("Y");
      $dia_de_pago = intval($dia_de_pago);
      if($dia_de_pago <= 0)
         $dia_de_pago = $tmp_dia;
      if($tmp_dia <= $dia_de_pago) {
         // calculamos el dia de cobro para este mes
         $tmp_dia = $dia_de_pago;
      } else {
         // calculamos el dia de cobro para el mes siguiente
         if($tmp_mes == 12) {
            $tmp_mes = 1;
            $tmp_año = $tmp_año + 1;
            $tmp_dia = $dia_de_pago; // No hay que calcular nada, enero tiene 31 dias
         } else {
            $tmp_mes += 1;
            // calculamos el último dia del mes para ver si sobrepasa dia elegido
            $date = new \DateTime($tmp_año . '-' . $tmp_mes . '-1');
            $date->modify('last day of this month');
            $ultimo_dia = $date->format('d');
            if($dia_de_pago > $ultimo_dia) {
               $tmp_dia = $ultimo_dia;
            } else {
               $tmp_dia = $dia_de_pago;
            }
         }
      }
      $fecha = $tmp_dia . '-' . $tmp_mes . '-' . $tmp_año;
      $fecha_inicio = Date('d-m-Y',strtotime($fecha));
      return $fecha_inicio;
   }
}
