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

if( defined('FS_NO_UPDATE') )
{
   echo "Las actualizaciones est&aacute;n desactivadas.";
}
else if( isset($_COOKIE['user']) AND isset($_COOKIE['logkey']) )
{
   $mensajes = '';
   $errores = '';
   if( !is_writable('.') )
   {
      $errores = 'No tienes permisos de escritura sobre la carpeta de FacturaScripts.';
   }
   else if( isset($_GET['update']) OR isset($_GET['reinstall']) )
   {
      if( file_put_contents('update.zip', file_get_contents('https://github.com/NeoRazorX/facturascripts/archive/master.zip')) )
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
            delTree('model/');
            delTree('view/');
            
            /// ahora hay que copiar todos los archivos de facturascripts-master a . y borrar
            recurse_copy('facturascripts-master/', '.');
            delTree('facturascripts-master/');
            
            $mensajes = 'Actualizado correctamente. <a href="index.php">Que lo disfrutes</a>.';
         }
         else
            $errores = 'Archivo update.zip no encontrado.';
      }
      else
         $errores = 'Error al descargar el archivo zip.';
   }
   
   $actualizar = FALSE;
   $version_actual = file_get_contents('VERSION');
   $nueva_version = file_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts/master/VERSION');
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
      echo '<div class="alert alert-danger">'.$errores.'</div>';
   }
   else if($mensajes != '')
   {
      echo '<div class="alert alert-info">'.$mensajes.'</div>';
   }
   ?>
   <div class="jumbotron">
      <h1>¡Bienvenido al actualizador de FacturaScripts!</h1>
      <p>
         Siéntate y ponte cómodo mientras este software de alta tecnolgía arruina el trabajo
         de decenas de empresas de software ancladas en los años 80.
      </p>
      <p>
         Tienes instalada la versión <mark><?php echo $version_actual; ?></mark>
         y en Internet está disponible la versión <mark><?php echo $nueva_version; ?></mark>
      </p>
      <p>
         <?php
         if($actualizar)
         {
            ?>
            <a class="btn btn-primary btn-lg" href="updater.php?update=TRUE" role="button">Actualizar</a>
            <?php
         }
         else
         {
            ?>
            <a class="btn btn-primary btn-lg" href="updater.php?reinstall=TRUE" role="button">Reinstalar</a>
            <?php
         }
         ?>
      </p>
   </div>
   <div class="text-center" style="margin-bottom: 20px;">
      <hr/>
      <small>Creado con <a target="_blank" href="//www.facturascripts.com">FacturaScripts</a></small>
   </div>
</body>
</html>
<?php
}
else
{
   echo 'Debes <a href="index.php">iniciar sesi&oacute;n</a>.';
}