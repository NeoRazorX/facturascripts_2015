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

require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_model('almacen.php');
require_model('cuenta_banco.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('serie.php');
require_model('pais.php');

class admin_empresa extends fs_controller
{
   public $almacen;
   public $cuenta_banco;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $impresion;
   public $mail;
   public $serie;
   public $pais;
   
   public $logo;
   public $facturacion_base;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Empresa', 'admin', TRUE, TRUE);
   }
   
   protected function private_core()
   {
      $this->almacen = new almacen();
      $this->cuenta_banco = new cuenta_banco();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->serie = new serie();
      $this->pais = new pais();
      
      $fsvar = new fs_var();
      
      /// obtenemos los datos de configuración del email
      $this->mail = array(
          'mail_host' => 'smtp.gmail.com',
          'mail_port' => '465',
          'mail_enc' => 'ssl',
          'mail_user' => '',
          'mail_low_security' => FALSE
      );
      $this->mail = $fsvar->array_get($this->mail, FALSE);
      
      /// obtenemos los datos de configuración de impresión
      $this->impresion = array(
          'print_ref' => '1',
          'print_dto' => '1',
          'print_alb' => '0'
      );
      $this->impresion = $fsvar->array_get($this->impresion, FALSE);
      
      if( isset($_POST['cifnif']) )
      {
         /// guardamos los datos de la empresa
         $this->empresa->nombre = $_POST['nombre'];
         $this->empresa->nombrecorto = $_POST['nombrecorto'];
         $this->empresa->cifnif = $_POST['cifnif'];
         $this->empresa->administrador = $_POST['administrador'];
         $this->empresa->codpais = $_POST['codpais'];
         $this->empresa->provincia = $_POST['provincia'];
         $this->empresa->ciudad = $_POST['ciudad'];
         $this->empresa->direccion = $_POST['direccion'];
         $this->empresa->codpostal = $_POST['codpostal'];
         $this->empresa->telefono = $_POST['telefono'];
         $this->empresa->fax = $_POST['fax'];
         $this->empresa->web = $_POST['web'];
         $this->empresa->email = $_POST['email'];
         $this->empresa->email_firma = $_POST['email_firma'];
         $this->empresa->email_password = $_POST['email_password'];
         $this->empresa->lema = $_POST['lema'];
         $this->empresa->horario = $_POST['horario'];
         $this->empresa->contintegrada = isset($_POST['contintegrada']);
         $this->empresa->codejercicio = $_POST['codejercicio'];
         $this->empresa->codserie = $_POST['codserie'];
         $this->empresa->coddivisa = $_POST['coddivisa'];
         $this->empresa->codpago = $_POST['codpago'];
         $this->empresa->codalmacen = $_POST['codalmacen'];
         $this->empresa->pie_factura = $_POST['pie_factura'];
         $this->empresa->recequivalencia = isset($_POST['recequivalencia']);
         
         if( $this->empresa->save() )
         {
            /// guardamos las opciones por defecto de almacén y forma de pago
            $this->save_codalmacen($_POST['codalmacen']);
            $this->save_codpago($_POST['codpago']);
            
            $this->new_message('Datos guardados correctamente.');
            if(!$this->empresa->contintegrada)
            {
               $this->new_message('¿Quieres activar la <b>contabilidad integrada</b>?'
                       . ' Haz clic en la sección <a href="#facturacion">facturación</a>.');
            }
            
            $step = $fsvar->simple_get('install_step');
            if($step == 2)
            {
               $step = 3;
               $fsvar->simple_save('install_step', $step);
            }
            if($step == 3 AND $this->empresa->contintegrada)
            {
               $this->new_message('Recuerda que tienes que <a href="index.php?page=contabilidad_ejercicio&cod='.
                       $this->empresa->codejercicio.'">importar los datos del ejercicio</a>.');
            }
         }
         else
            $this->new_error_msg ('Error al guardar los datos.');
         
         /// guardamos los datos del email
         if( isset($_POST['mail_host']) )
         {
            $this->mail['mail_host'] = 'smtp.gmail.com';
            if($_POST['mail_host'] != '')
            {
               $this->mail['mail_host'] = $_POST['mail_host'];
            }
            
            $this->mail['mail_port'] = '465';
            if($_POST['mail_port'] != '')
            {
               $this->mail['mail_port'] = $_POST['mail_port'];
            }
            
            $this->mail['mail_enc'] = strtolower($_POST['mail_enc']);
            $this->mail['mail_user'] = $_POST['mail_user'];
            $this->mail['mail_low_security'] = isset($_POST['mail_low_security']);
            $fsvar->array_save($this->mail);
            $this->mail_test();
         }
         
         /// guardamos los datos de impresión
         $this->impresion['print_ref'] = ( isset($_POST['print_ref']) ? 1 : 0 );
         $this->impresion['print_dto'] = ( isset($_POST['print_dto']) ? 1 : 0 );
         $this->impresion['print_alb'] = ( isset($_POST['print_alb']) ? 1 : 0 );
         $fsvar->array_save($this->impresion);
      }
      else if( isset($_POST['nombre']) )
      {
         /// guardamos solamente lo básico, ya que facturacion_base no está activado
         $this->empresa->nombre = $_POST['nombre'];
         $this->empresa->nombrecorto = $_POST['nombrecorto'];
         $this->empresa->web = $_POST['web'];
         $this->empresa->email = $_POST['email'];
         $this->empresa->email_firma = $_POST['email_firma'];
         $this->empresa->email_password = $_POST['email_password'];
         
         if( $this->empresa->save() )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
            $this->new_error_msg ('Error al guardar los datos.');
         
         /// guardamos los datos del email
         if( isset($_POST['mail_host']) )
         {
            $this->mail['mail_host'] = 'smtp.gmail.com';
            if($_POST['mail_host'] != '')
            {
               $this->mail['mail_host'] = $_POST['mail_host'];
            }
            
            $this->mail['mail_port'] = '465';
            if($_POST['mail_port'] != '')
            {
               $this->mail['mail_port'] = $_POST['mail_port'];
            }
            
            $this->mail['mail_enc'] = strtolower($_POST['mail_enc']);
            $this->mail['mail_user'] = $_POST['mail_user'];
            $this->mail['mail_low_security'] = isset($_POST['mail_low_security']);
            $fsvar->array_save($this->mail);
            $this->mail_test();
         }
      }
      else if( isset($_POST['logo']) )
      {
         if( is_uploaded_file($_FILES['fimagen']['tmp_name']) )
         {
            $this->delete_logo();
            
            if( substr( strtolower($_FILES['fimagen']['name']), -3) == 'png' )
            {
               copy($_FILES['fimagen']['tmp_name'], "tmp/".FS_TMP_NAME."logo.png");
            }
            else
            {
               copy($_FILES['fimagen']['tmp_name'], "tmp/".FS_TMP_NAME."logo.jpg");
            }
            
            $this->new_message('Logotipo guardado correctamente.');
         }
      }
      else if( isset($_GET['delete_logo']) )
      {
         $this->delete_logo();
      }
      else if( isset($_GET['delete_cuenta']) ) /// eliminar cuenta bancaria
      {
         $cuenta = $this->cuenta_banco->get($_GET['delete_cuenta']);
         if($cuenta)
         {
            if( $cuenta->delete() )
            {
               $this->new_message('Cuenta bancaria eliminada correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar la cuenta bancaria.');
         }
         else
            $this->new_error_msg('Cuenta bancaria no encontrada.');
      }
      else if( isset($_POST['iban']) ) /// añadir/modificar cuenta bancaria
      {
         if( isset($_POST['codcuenta']) )
         {
            $cuentab = $this->cuenta_banco->get($_POST['codcuenta']);
         }
         else
         {
            $cuentab = new cuenta_banco();
         }
         $cuentab->descripcion = $_POST['descripcion'];
         
         if($_POST['ciban'] != '')
         {
            $cuentab->iban = $cuentab->calcular_iban($_POST['ciban']);
         }
         else
            $cuentab->iban = $_POST['iban'];
         
         $cuentab->swift = $_POST['swift'];
         
         if( $cuentab->save() )
         {
            $this->new_message('Cuenta bancaria guardada correctamente.');
         }
         else
            $this->new_error_msg('Imposible guardar la cuenta bancaria.');
      }
      
      $this->facturacion_base = in_array('facturacion_base', $GLOBALS['plugins']);
      
      $this->logo = FALSE;
      if( file_exists('tmp/'.FS_TMP_NAME.'logo.png') )
      {
         $this->logo = 'tmp/'.FS_TMP_NAME.'logo.png';
      }
      else if( file_exists('tmp/'.FS_TMP_NAME.'logo.jpg') )
      {
         $this->logo = 'tmp/'.FS_TMP_NAME.'logo.jpg';
      }
   }
   
   private function mail_test()
   {
      if( $this->empresa->can_send_mail() )
      {
         /// Es imprescindible OpenSSL para enviar emails con los principales proveedores
         if( extension_loaded('openssl') )
         {
            $mail = new PHPMailer();
            $mail->Timeout = 3;
            $mail->IsSMTP();
            $mail->SMTPAuth = TRUE;
            $mail->SMTPSecure = $this->mail['mail_enc'];
            $mail->Host = $this->mail['mail_host'];
            $mail->Port = intval($this->mail['mail_port']);
            $mail->Username = $this->empresa->email;
            if($this->mail['mail_user'] != '')
            {
               $mail->Username = $this->mail['mail_user'];
            }
            
            $mail->Password = $this->empresa->email_password;
            $mail->From = $this->empresa->email;
            $mail->FromName = $this->user->nick;
            $mail->CharSet = 'UTF-8';
            
            $mail->Subject = 'TEST';
            $mail->AltBody = 'TEST';
            $mail->WordWrap = 50;
            $mail->MsgHTML('TEST');
            $mail->IsHTML(TRUE);
            
            $SMTPOptions = array();
            if($this->mail['mail_low_security'])
            {
               $SMTPOptions = array(
                   'ssl' => array(
                       'verify_peer' => false,
                       'verify_peer_name' => false,
                       'allow_self_signed' => true
                   )
               );
            }
            
            if( !$mail->SmtpConnect($SMTPOptions) )
            {
               $this->new_error_msg('No se ha podido conectar por email. ¿La contraseña es correcta?');
               
               if($mail->Host == 'smtp.gmail.com')
               {
                  $this->new_error_msg('Aunque la contraseña de gmail sea correcta, en ciertas '
                          . 'situaciones los servidores de gmail bloquean la conexión. '
                          . 'Para superar esta situación debes crear y usar una '
                          . '<a href="https://support.google.com/accounts/answer/185833?hl=es" '
                          . 'target="_blank">contraseña de aplicación</a>');
               }
               else
               {
                  $this->new_error_msg("¿<a href='https://www.facturascripts.com/comm3/index.php?page=community_item&id=74'"
                          . " target='_blank'>Necesitas ayuda</a>?");
               }
            }
         }
         else
         {
            $this->new_error_msg('No se encuentra la extensión OpenSSL,'
                    . ' imprescindible para enviar emails.');
         }
      }
   }
   
   private function delete_logo()
   {
      if( file_exists('tmp/'.FS_TMP_NAME.'logo.png') )
      {
         unlink('tmp/'.FS_TMP_NAME.'logo.png');
         $this->new_message('Logotipo borrado correctamente.');
      }
      else if( file_exists('tmp/'.FS_TMP_NAME.'logo.jpg') )
      {
         unlink('tmp/'.FS_TMP_NAME.'logo.jpg');
         $this->new_message('Logotipo borrado correctamente.');
      }
   }
}
