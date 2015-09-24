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

/// accedemos al directorio de FacturaScripts
chdir(__DIR__);

/// cargamos las constantes de configuración
require_once 'config.php';
require_once 'base/config2.php';

$tiempo = explode(' ', microtime());
$uptime = $tiempo[1] + $tiempo[0];

require_once 'base/fs_db2.php';
$db = new fs_db2();

require_once 'base/fs_default_items.php';

require_once 'base/fs_model.php';
require_model('empresa.php');
require_model('fs_var.php');

if( $db->connect() )
{
   $fsvar = new fs_var();
   $cron_vars = $fsvar->array_get( array('cron_exists' => FALSE, 'cron_lock' => FALSE, 'cron_error' => FALSE) );
   
   if($cron_vars['cron_lock'])
   {
      echo "ERROR: Ya hay un cron en ejecución. Si crees que es un error,"
      . " ve a Admin > Información del sistema para solucionar el problema.";
      
      /// marcamos el error en el cron
      $cron_vars['cron_error'] = 'TRUE';
   }
   else
   {
      /**
       * He detectado que a veces, con el plugin kiwimaru,
       * el proceso cron tarda más de una hora, y por tanto se encadenan varios
       * procesos a la vez. Para evitar esto, uso la entrada cron_lock.
       * Además uso la entrada cron_exists para marcar que alguna vez se ha ejecutado el cron,
       * y cron_error por si hubiese algún fallo.
       */
      $cron_vars['cron_lock'] = 'TRUE';
      $cron_vars['cron_exists'] = 'TRUE';
      
      /// guardamos las variables
      $fsvar->array_save($cron_vars);
      
      /// establecemos los elementos por defecto
      $fs_default_items = new fs_default_items();
      $empresa = new empresa();
      $fs_default_items->set_codalmacen( $empresa->codalmacen );
      $fs_default_items->set_coddivisa( $empresa->coddivisa );
      $fs_default_items->set_codejercicio( $empresa->codejercicio );
      $fs_default_items->set_codpago( $empresa->codpago );
      $fs_default_items->set_codpais( $empresa->codpais );
      $fs_default_items->set_codserie( $empresa->codserie );
      
      /*
       * Ahora ejecutamos el cron de cada plugin que tenga cron y esté activado
       */
      foreach($GLOBALS['plugins'] as $plugin)
      {
         if( file_exists('plugins/'.$plugin.'/cron.php') )
         {
            echo "\n***********************\nEjecutamos el cron.php del plugin ".$plugin."\n";
            
            include 'plugins/'.$plugin.'/cron.php';
            
            echo "\n***********************";
         }
      }
      
      /// Eliminamos la variable cron_lock puesto que ya hemos terminado
      $cron_vars['cron_lock'] = FALSE;
   }
   
   /// guardamos las variables
   $fsvar->array_save($cron_vars);
   
   $db->close();
}
else
{
   echo "¡Imposible conectar a la base de datos!\n";
   
   foreach($db->get_errors() as $err)
   {
      echo $err."\n";
   }
}

$tiempo = explode(' ', microtime());
echo "\nTiempo de ejecución: ".number_format($tiempo[1] + $tiempo[0] - $uptime, 3)." s\n";