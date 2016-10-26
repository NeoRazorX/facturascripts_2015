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

/**
 * Controlador para modificar el perfil del usuario.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_user extends fs_controller
{
   public $agente;
   public $allow_delete;
   public $allow_modify;
   public $user_log;
   public $suser;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Usuario', 'admin', TRUE, FALSE);
   }
   
   public function private_core()
   {
      $this->share_extensions();
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->admin;
      
      /// ¿El usuario tiene permiso para modificar en esta página?
      $this->allow_modify = $this->user->admin;
      
      $this->agente = new agente();
      
      $this->suser = FALSE;
      if( isset($_GET['snick']) )
      {
         $this->suser = $this->user->get($_GET['snick']);
      }
      
      if($this->suser)
      {
         $this->page->title = $this->suser->nick;
         
         /// ¿Estamos modificando nuestro usuario?
         if($this->suser->nick == $this->user->nick)
         {
            $this->allow_modify = TRUE;
            $this->allow_delete = FALSE;
         }
         
         if( isset($_POST['nnombre']) )
         {
            /// Nuevo empleado
            $age0 = new agente();
            $age0->codagente = $age0->get_new_codigo();
            $age0->nombre = $_POST['nnombre'];
            $age0->apellidos = $_POST['napellidos'];
            $age0->dnicif = $_POST['ndnicif'];
            $age0->telefono = $_POST['ntelefono'];
            $age0->email = strtolower($_POST['nemail']);
            
            if(!$this->user->admin)
            {
               $this->new_error_msg('Solamente un administrador puede crear y asignar un empleado desde aquí.');
            }
            else if( $age0->save() )
            {
               $this->new_message("Empleado ".$age0->codagente." guardado correctamente.");
               $this->suser->codagente = $age0->codagente;
               
               if( $this->suser->save() )
               {
                  $this->new_message("Empleado ".$age0->codagente." asignado correctamente.");
               }
               else
                  $this->new_error_msg("¡Imposible asignar el agente!");
            }
            else
               $this->new_error_msg("¡Imposible guardar el agente!");
         }
         else if( isset($_POST['spassword']) OR isset($_POST['scodagente']) OR isset($_POST['sadmin']) )
         {
            $this->modificar_user();
         }
         
         /// ¿Estamos modificando nuestro usuario?
         if($this->suser->nick == $this->user->nick)
         {
            $this->user = $this->suser;
         }
         
         /// si el usuario no tiene acceso a ninguna página, entonces hay que informar del problema.
         if( !$this->suser->admin )
         {
            $sin_paginas = TRUE;
            foreach($this->all_pages() as $p)
            {
               if($p->enabled)
               {
                  $sin_paginas = FALSE;
                  break;
               }
            }
            if($sin_paginas)
            {
               $this->new_advice('No has autorizado a este usuario a acceder a ninguna'
                  . ' página y por tanto no podrá hacer nada. Puedes darle acceso a alguna página'
                  . ' desde la pestaña autorizar.');
            }
         }
         
         $fslog = new fs_log();
         $this->user_log = $fslog->all_from($this->suser->nick);
      }
      else
      {
         $this->new_error_msg("Usuario no encontrado.", 'error', FALSE, FALSE);
      }
   }
   
   public function url()
   {
      if( !isset($this->suser) )
      {
         return parent::url();
      }
      else if($this->suser)
      {
         return $this->suser->url();
      }
      else
         return $this->page->url();
   }
   
   public function all_pages()
   {
      $returnlist = array();
      
      /// Obtenemos la lista de páginas. Todas
      foreach($this->menu as $m)
      {
         $m->enabled = FALSE;
         $m->allow_delete = FALSE;
         $returnlist[] = $m;
      }
      
      /// Completamos con la lista de accesos del usuario
      $access = $this->suser->get_accesses();
      foreach($returnlist as $i => $value)
      {
         foreach($access as $a)
         {
            if($value->name == $a->fs_page)
            {
               $returnlist[$i]->enabled = TRUE;
               $returnlist[$i]->allow_delete = $a->allow_delete;
               break;
            }
         }
      }
      
      /// ordenamos por nombre
      usort($returnlist, function($a, $b) {
         return strcmp($a->name, $b->name);
      });
      
      return $returnlist;
   }
   
   private function share_extensions()
   {
      foreach($this->extensions as $ext)
      {
         if($ext->type == 'css')
         {
            if( !file_exists($ext->text) )
            {
               $ext->delete();
            }
         }
      }
      
      $extensions = array(
          array(
              'name' => 'cosmo',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-cosmo.min.css',
              'params' => ''
          ),
          array(
              'name' => 'darkly',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-darkly.min.css',
              'params' => ''
          ),
          array(
              'name' => 'flatly',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-flatly.min.css',
              'params' => ''
          ),
          array(
              'name' => 'sandstone',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-sandstone.min.css',
              'params' => ''
          ),
          array(
              'name' => 'united',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-united.min.css',
              'params' => ''
          ),
          array(
              'name' => 'yeti',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-yeti.min.css',
              'params' => ''
          ),
          array(
              'name' => 'lumen',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-lumen.min.css',
              'params' => ''
          ),
          array(
              'name' => 'paper',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-paper.min.css',
              'params' => ''
          ),
          array(
              'name' => 'simplex',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-simplex.min.css',
              'params' => ''
          ),
          array(
              'name' => 'spacelab',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-spacelab.min.css',
              'params' => ''
          ),
      );
      foreach($extensions as $ext)
      {
         $fsext = new fs_extension($ext);
         $fsext->save();
      }
   }
   
   private function modificar_user()
   {
      if(FS_DEMO AND $this->user->nick != $this->suser->nick)
      {
         $this->new_error_msg('En el modo <b>demo</b> sólo puedes modificar los datos de TU usuario.
            Esto es así para evitar malas prácticas entre usuarios que prueban la demo.');
      }
      else if(!$this->allow_modify)
      {
         $this->new_error_msg('No tienes permiso para modificar estos datos.');
      }
      else
      {
         $user_no_more_admin = FALSE;
         $error = FALSE;
         if($_POST['spassword'] != '')
         {
            if($_POST['spassword'] == $_POST['spassword2'])
            {
               if( $this->suser->set_password($_POST['spassword']) )
               {
                  $this->new_message('Se ha cambiado la contraseña del usuario '.$this->suser->nick, TRUE, 'login', TRUE);
               }
            }
            else
            {
               $this->new_error_msg('Las contraseñas no coinciden.');
               $error = TRUE;
            }
         }
         
         $this->suser->email = strtolower($_POST['email']);
         
         if( isset($_POST['scodagente']) )
         {
            $this->suser->codagente = NULL;
            if($_POST['scodagente'] != '')
            {
               $this->suser->codagente = $_POST['scodagente'];
            }
         }
         
         /*
          * Propiedad admin: solamente un admin puede cambiarla.
          */
         if($this->user->admin)
         {
            /*
             * El propio usuario no puede decidir dejar de ser administrador.
             */
            if($this->user->nick != $this->suser->nick)
            {
               /*
                * Si un usuario es administrador y deja de serlo, hay que darle acceso
                * a algunas páginas, en caso contrario no podrá continuar
                */
               if($this->suser->admin AND !isset($_POST['sadmin']))
               {
                  $user_no_more_admin = TRUE;
               }
               $this->suser->admin = isset($_POST['sadmin']);
            }
         }
         
         $this->suser->fs_page = NULL;
         if( isset($_POST['udpage']) )
         {
            $this->suser->fs_page = $_POST['udpage'];
         }
         
         if( isset($_POST['css']) )
         {
            $this->suser->css = $_POST['css'];
         }
         
         if($error)
         {
            
         }
         else if( $this->suser->save() )
         {
            if(!$this->user->admin)
            {
               /// si no eres administrador, no puedes cambiar los permisos
            }
            else if(!$this->suser->admin)
            {
               /// para cada página, comprobamos si hay que darle acceso o no
               foreach($this->all_pages() as $p)
               {
                  /**
                   * Creamos un objeto fs_access con los datos del usuario y la página.
                   * Si tiene acceso guardamos, sino eliminamos. Así no tenemos que comprobar uno a uno
                   * si ya estaba en la base de datos. Eso lo hace el modelo.
                   */
                  $a = new fs_access( array('fs_user'=> $this->suser->nick, 'fs_page'=>$p->name, 'allow_delete'=>FALSE) );
                  if( isset($_POST['allow_delete']) )
                  {
                     $a->allow_delete = in_array($p->name, $_POST['allow_delete']);
                  }
                  
                  if($user_no_more_admin)
                  {
                     /*
                      * Si un usuario es administrador y deja de serlo, hay que darle acceso
                      * a algunas páginas, en caso contrario no podrá continuar.
                      */
                     $a->save();
                  }
                  else if( !isset($_POST['enabled']) )
                  {
                     /**
                      * No se ha marcado ningún checkbox de autorizado, así que eliminamos el acceso
                      * a todas las páginas. Una a una.
                      */
                     $a->delete();
                  }
                  else if( in_array($p->name, $_POST['enabled']) )
                  {
                     /// la página ha sido marcada como autorizada.
                     $a->save();
                     
                     if( is_null($this->suser->fs_page) AND $p->show_on_menu )
                     {
                        $this->suser->fs_page = $p->name;
                        $this->suser->save();
                     }
                  }
                  else
                  {
                     /// la página no está marcada como autorizada.
                     $a->delete();
                  }
               }
            }
            
            $this->new_message("Datos modificados correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible modificar los datos!");
      }
   }
}
