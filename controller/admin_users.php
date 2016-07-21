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
 * Controlador de admin -> users.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_users extends fs_controller
{
   public $agente;
   public $historial;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Usuarios', 'admin', TRUE, TRUE);
   }
   
   protected function private_core()
   {
      $this->agente = new agente();
      
      if( isset($_POST['nnick']) )
      {
         $nu = $this->user->get($_POST['nnick']);
         if($nu)
         {
            $this->new_error_msg('El usuario <a href="'.$nu->url().'">ya existe</a>.');
         }
         else if(!$this->user->admin)
         {
            $this->new_error_msg('Solamente un administrador puede crear usuarios.', TRUE, 'login', TRUE);
         }
         else
         {
            $nu = new fs_user();
            $nu->nick = $_POST['nnick'];
            $nu->email = strtolower($_POST['nemail']);
            
            if( $nu->set_password($_POST['npassword']) )
            {
               $nu->admin = isset($_POST['nadmin']);
               if( isset($_POST['ncodagente']) )
               {
                  if($_POST['ncodagente'] != '')
                  {
                     $nu->codagente = $_POST['ncodagente'];
                  }
               }
               
               if( $nu->save() )
               {
                  $this->new_message('Usuario '.$nu->nick.' creado correctamente.', TRUE, 'login', TRUE);
                  Header('location: index.php?page=admin_user&snick=' . $nu->nick);
               }
               else
                  $this->new_error_msg("¡Imposible guardar el usuario!");
            }
         }
      }
      else if( isset($_GET['delete']) )
      {
         $nu = $this->user->get($_GET['delete']);
         if($nu)
         {
            if(FS_DEMO)
            {
               $this->new_error_msg('En el modo <b>demo</b> no se pueden eliminar usuarios.
                  Esto es así para evitar malas prácticas entre usuarios que prueban la demo.');
            }
            else if(!$this->user->admin)
            {
               $this->new_error_msg("Solamente un administrador puede eliminar usuarios.", 'login', TRUE);
            }
            else if( $nu->delete() )
            {
               $this->new_message("Usuario ".$nu->nick." eliminado correctamente.", TRUE, 'login', TRUE);
            }
            else
               $this->new_error_msg("¡Imposible eliminar al usuario!");
         }
         else
            $this->new_error_msg("¡Usuario no encontrado!");
      }
      
      $fslog = new fs_log();
      $this->historial = $fslog->all_by('login');
   }
   
   public function all_pages()
   {
      $returnlist = array();
      
      /// Obtenemos la lista de páginas. Todas
      foreach($this->menu as $m)
      {
         $m->enabled = FALSE;
         $m->allow_delete = FALSE;
         $m->users = array();
         $returnlist[] = $m;
      }
      
      /// completamos con los permisos de los usuarios
      foreach($this->user->all() as $user)
      {
         if($user->admin)
         {
            foreach($returnlist as $i => $value)
            {
               $returnlist[$i]->users[$user->nick] = array(
                   'modify' => TRUE,
                   'delete' => TRUE,
               );
            }
         }
         else
         {
            foreach($returnlist as $i => $value)
            {
               $returnlist[$i]->users[$user->nick] = array(
                   'modify' => FALSE,
                   'delete' => FALSE,
               );
            }
            
            foreach($user->get_accesses() as $a)
            {
               foreach($returnlist as $i => $value)
               {
                  if($a->fs_page == $value->name)
                  {
                     $returnlist[$i]->users[$user->nick]['modify'] = TRUE;
                     $returnlist[$i]->users[$user->nick]['delete'] = $a->allow_delete;
                     break;
                  }
               }
            }
         }
      }
      
      /// ordenamos por nombre
      usort($returnlist, function($a, $b) {
         return strcmp($a->name, $b->name);
      });
      
      return $returnlist;
   }
}
