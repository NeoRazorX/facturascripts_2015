<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'config.php';

function recurse_copy($src, $dst)
{
   $dir = opendir($src);
   @mkdir($dst);
   while(false !== ( $file = readdir($dir)) )
   {
      if (( $file != '.' ) && ( $file != '..' ))
      {
         if ( is_dir($src . '/' . $file) )
         {
            recurse_copy($src . '/' . $file,$dst . '/' . $file);
         }
         else
         {
            copy($src . '/' . $file,$dst . '/' . $file);
         }
      }
   }
   closedir($dir);
}

function delTree($dir)
{
   $files = array_diff(scandir($dir), array('.','..'));
   foreach ($files as $file)
   {
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
   }
   return rmdir($dir);
}

function __getAllSubDirectories($directory, $directory_seperator=DIRECTORY_SEPARATOR)
{
   $dirs = array_map( function($item)use($directory_seperator){ return $item . $directory_seperator;}, array_filter( glob( $directory . '*' ), 'is_dir') );
   
   foreach($dirs AS $dir)
	{
      if( strcmp($dir, "..".$directory_seperator) != 0 )
      {
         $dirs = array_merge($dirs, __getAllSubDirectories($dir, $directory_seperator) );
      }
   }
   
   return $dirs;
}

function __areWritable($dirlist)
{
   $notwritable = array();
   
   foreach($dirlist as $dir)
   {
      if( !is_writable($dir) && strcmp($dir, "../") != 0)
      {
         $notwritable[] = $dir;
      }
	}
   
   return $notwritable;
}

function check_for_plugin_updates()
{
   $plugins = array();
   
   foreach( scandir(getcwd().'/plugins') as $f)
   {
      if( is_dir('plugins/'.$f) AND $f != '.' AND $f != '..')
      {
         $plugin = array(
             'name' => $f,
             'description' => 'Sin descripción.',
             'compatible' => FALSE,
             'enabled' => FALSE,
             'version' => 0,
             'require' => '',
             'update_url' => '',
             'version_url' => '',
             'new_version' => 0
         );
         
         if( file_exists('plugins/'.$f.'/facturascripts.ini') )
         {
            $plugin['compatible'] = TRUE;
            $plugin['enabled'] = file_exists('tmp/enabled_plugins/'.$f);
            
            if( file_exists('plugins/'.$f.'/description') )
            {
               $plugin['description'] = file_get_contents('plugins/'.$f.'/description');
            }
            
            $ini_file = parse_ini_file('plugins/'.$f.'/facturascripts.ini');
            if( isset($ini_file['version']) )
            {
               $plugin['version'] = intval($ini_file['version']);
            }
            
            if( isset($ini_file['require']) )
            {
               $plugin['require'] = $ini_file['require'];
            }
            
            if( isset($ini_file['update_url']) )
            {
               $plugin['update_url'] = $ini_file['update_url'];
            }
            
            if( isset($ini_file['version_url']) )
            {
               $plugin['version_url'] = $ini_file['version_url'];
            }
            
            if($plugin['version_url'] != '' AND $plugin['update_url'] != '')
            {
               $internet_ini = parse_ini_string( file_get_contents($plugin['version_url']) );
               if( $plugin['version'] < intval($internet_ini['version']) )
               {
                  $plugin['new_version'] = intval($internet_ini['version']);
                  $plugins[] = $plugin;
               }
            }
         }
      }
   }
   
   return $plugins;
}

if( isset($_COOKIE['user']) AND isset($_COOKIE['logkey']) )
{
   $mensajes = '';
   $errores = '';
   
   if( !isset($_GET['update']) AND !isset($_GET['reinstall']) AND !isset($_GET['plugin']) )
   {
      foreach(__areWritable(__getAllSubDirectories('.')) as $dir)
      {
         $errores .= 'No se puede escribir sobre el directorio '.$dir.'<br/>';
      }
   }
   
   if($errores != '')
   {
      $errores .= 'Tienes que corregir estos errores antes de continuar.';
   }
   else if( isset($_GET['update']) OR isset($_GET['reinstall']) )
   {
      if( file_put_contents('update.zip', file_get_contents('https://github.com/NeoRazorX/facturascripts_2015/archive/master.zip')) )
      {
         $zip = new ZipArchive();
         if( $zip->open('update.zip') )
         {
            $zip->extractTo('.');
            $zip->close();
            unlink('update.zip');
            
            /// eliminamos archivos antiguos
            delTree('base/');
            delTree('controller/');
            delTree('extras/');
            delTree('model/');
            delTree('raintpl/');
            delTree('view/');
            
            /// borramos los archivos temporales del motor de plantillas
            foreach( scandir(getcwd().'/tmp') as $f )
				{
               if( substr($f, -4) == '.php' )
               {
                  unlink('tmp/'.$f);
               }
				}
            
            /// ahora hay que copiar todos los archivos de facturascripts-master a . y borrar
            recurse_copy('facturascripts_2015-master/', '.');
            delTree('facturascripts_2015-master/');
            
            $mensajes = 'Actualizado correctamente. <a href="index.php?page=admin_home&updated=TRUE">Que lo disfrutes</a>.';
         }
         else
            $errores = 'Archivo update.zip no encontrado.';
      }
      else
         $errores = 'Error al descargar el archivo zip.';
   }
   else if( isset($_GET['plugin']) )
   {
      /// leemos el ini del plugin
      $plugin_ini = parse_ini_file('plugins/'.$_GET['plugin'].'/facturascripts.ini');
      if($plugin_ini)
      {
         /// descargamos el zip
         if( file_put_contents('update.zip', file_get_contents($plugin_ini['update_url'])) )
         {
            $zip = new ZipArchive();
            if( $zip->open('update.zip') )
            {
               /// eliminamos los archivos antiguos
               delTree('plugins/'.$_GET['plugin']);
               
               /// descomprimimos
               $zip->extractTo('plugins/');
               $zip->close();
               unlink('update.zip');
               
               /// renombramos el directorio
               rename('plugins/'.$_GET['plugin'].'-master', 'plugins/'.$_GET['plugin']);
               
               /// borramos los archivos temporales del motor de plantillas
               foreach( scandir(getcwd().'/tmp') as $f )
         		{
                  if( substr($f, -4) == '.php' )
                  {
                     unlink('tmp/'.$f);
                  }
      			}
               
               $mensajes = 'Plugin actualizado correctamente. <a href="index.php?page=admin_home&updated=TRUE">Que lo disfrutes</a>.';
            }
            else
               $errores = 'Archivo update.zip no encontrado.';
         }
         else
            $errores = 'Error al descargar el archivo zip.';
      }
   }
   
   $actualizar = FALSE;
   $version_actual = file_get_contents('VERSION');
   $nueva_version = file_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/VERSION');
   if( $version_actual != $nueva_version )
   {
      $actualizar = TRUE;
   }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>Actualizador de FacturaScripts</title>
   <meta name="description" content="Script de actualización de FacturaScripts." />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="shortcut icon" href="view/img/favicon.ico" />
   <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
   <script type="text/javascript" src="view/js/jquery-2.1.1.min.js"></script>
   <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
</head>
<body>
   <?php
   if($errores != '')
   {
      echo '<div class="alert alert-danger" style="margin-bottom: 0px;">'.$errores.'</div>';
   }
   else if($mensajes != '')
   {
      echo '<div class="alert alert-info" style="margin-bottom: 0px;">'.$mensajes.'</div>';
   }
   ?>
   <div class="container-fluid">
      <div class="row">
         <div class="col-md-12">
            <div class="page-header">
               <h1>¡Bienvenido al actualizador de FacturaScripts!</h1>
            </div>
         </div>
      </div>
      <div class="row">
         <div class="col-md-9">
            <p>
               Siéntate y ponte cómodo mientras este software de alta tecnolgía arruina el trabajo
               de decenas de empresas de software ancladas en los años 80.
            </p>
            <p>
               Tienes instalada la versión <mark><?php echo $version_actual; ?></mark>
               y en Internet está disponible la versión <mark><?php echo $nueva_version; ?></mark>
            </p>
         </div>
         <div class="col-md-3 text-right">
         <?php
         if($errores != '')
         {
            
         }
         else if($actualizar)
         {
            ?>
            <a class="btn btn-primary btn-lg" href="updater.php?update=TRUE" role="button">
               <span class="glyphicon glyphicon-upload" aria-hidden="true"></span> &nbsp; Actualizar
            </a>
            <?php
         }
         else
         {
            ?>
            <a class="btn btn-primary btn-lg" href="updater.php?reinstall=TRUE" role="button">
               <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> &nbsp; Reinstalar
            </a>
            <?php
         }
         ?>
         </div>
      </div>
      <div class="row">
         <div class="col-md-12">
            <ul class="nav nav-tabs">
               <li role="presentation" class="active"><a href="#">Plugins</a></li>
            </ul>
            <div class="table-responsive">
               <table class="table table-hover">
                  <thead>
                     <tr>
                        <th class="text-left">Nombre</th>
                        <th class="text-left">Descripción</th>
                        <th class="text-right">Versión</th>
                        <th class="text-right">Nueva versión</th>
                        <th></th>
                     </tr>
                  </thead>
                  <?php
                  if($errores == '')
                  {
                     $plugins_for_update = FALSE;
                     foreach(check_for_plugin_updates() as $plugin)
                     {
                        echo '<tr><td>'.$plugin['name'].'</td><td>'.$plugin['description'].'</td>'
                                . '<td class="text-right">'.$plugin['version'].'</td><td class="text-right">'.$plugin['new_version'].'</td>'
                                . '<td class="text-right"><a href="updater.php?plugin='.$plugin['name'].'" class="btn btn-xs btn-primary">Actualizar</a></td></tr>';
                        $plugins_for_update = TRUE;
                     }
                     
                     if(!$plugins_for_update)
                     {
                        echo '<tr class="bg-success"><td colspan="5">No hay actualizaciones de plugins.</td></tr>';
                     }
                  }
                  ?>
               </table>
            </div>
         </div>
      </div>
   </div>
</body>
</html>
<?php
}
else
{
   echo 'Debes <a href="index.php">iniciar sesi&oacute;n</a>.';
}