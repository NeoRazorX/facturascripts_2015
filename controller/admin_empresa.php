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

require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_model('almacen.php');
require_model('cuenta_banco.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('pais.php');
require_model('serie.php');

/**
 * Controlador de admin -> empresa.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_empresa extends fs_controller
{
   public $almacen;
   public $cuenta_banco;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $impresion;
   public $serie;
   public $pais;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Empresa / web', 'admin', TRUE, TRUE);
   }
   
   protected function private_core()
   {
      /// inicializamos para que se creen las tablas, aunque no vayamos a configurarlo aquí
      $this->almacen = new almacen();
      $this->cuenta_banco = new cuenta_banco();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->serie = new serie();
      $this->pais = new pais();
      
      if( isset($_POST['nombre']) )
      {
         /// guardamos solamente lo básico, ya que facturacion_base no está activado
         $this->empresa->nombre = $_POST['nombre'];
         $this->empresa->nombrecorto = $_POST['nombrecorto'];
         $this->empresa->web = $_POST['web'];
         $this->empresa->email = $_POST['email'];
         
         /// configuración de email
         $this->empresa->email_config['mail_password'] = $_POST['mail_password'];
         $this->empresa->email_config['mail_bcc'] = $_POST['mail_bcc'];
         $this->empresa->email_config['mail_firma'] = $_POST['mail_firma'];
         $this->empresa->email_config['mail_mailer'] = $_POST['mail_mailer'];
         $this->empresa->email_config['mail_host'] = $_POST['mail_host'];
         $this->empresa->email_config['mail_port'] = intval($_POST['mail_port']);
         $this->empresa->email_config['mail_enc'] = strtolower($_POST['mail_enc']);
         $this->empresa->email_config['mail_user'] = $_POST['mail_user'];
         $this->empresa->email_config['mail_low_security'] = isset($_POST['mail_low_security']);
         
         if( $this->empresa->save() )
         {
            $this->new_message('Datos guardados correctamente.');
            $this->mail_test();
         }
         else
            $this->new_error_msg ('Error al guardar los datos.');
      }
   }
   
   private function mail_test()
   {
      if( $this->empresa->can_send_mail() )
      {
         /// Es imprescindible OpenSSL para enviar emails con los principales proveedores
         if( extension_loaded('openssl') )
         {
            $mail = $this->empresa->new_mail();
            $mail->Timeout = 3;
            $mail->FromName = $this->user->nick;
            
            $mail->Subject = 'TEST';
            $mail->AltBody = 'TEST';
            $mail->msgHTML('TEST');
            $mail->isHTML(TRUE);
            
            if( !$this->empresa->mail_connect($mail) )
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
   
   public function encriptaciones()
   {
      return array(
          'ssl' => 'SSL',
          'tls' => 'TLS',
          '' => 'Ninguna'
      );
   }
   
   public function mailers()
   {
      return array(
          'mail' => 'Mail',
          'sendmail' => 'SendMail',
          'smtp' => 'SMTP'
      );
   }
}
