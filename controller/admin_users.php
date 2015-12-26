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
      
      $fslog = new fs_log();
      $this->historial = $fslog->all_by('login');
      
      if( isset($_POST['nnick']) )
      {
         $nu = $this->user->get($_POST['nnick']);
         if($nu)
         {
            $this->new_error_msg('El usuario <a href="'.$nu->url().'">ya existe</a>.');
         }
         else if(!$this->user->admin)
         {
            $this->new_error_msg('Solamente un administrador puede crear usuarios.');
         }
         else
         {
            $nu = new fs_user();
            $nu->nick = $_POST['nnick'];
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
               $this->new_error_msg("Solamente un administrador puede eliminar usuarios.");
            }
            else if( $nu->delete() )
            {
               $this->new_message("Usuario ".$nu->nick." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar al usuario!");
         }
         else
            $this->new_error_msg("¡Usuario no encontrado!");
      }
   }
}
