<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

if( !file_exists('config.php') )
{
   die('Archivo config.php no encontrado. No puedes actualizar sin instalar.');
}

require_once 'config.php';
require_once 'base/fs_cache.php';

/**
 * Controlador del actualizador de FacturaScripts.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_updater
{
   public $btn_fin;
   public $errores;
   public $mensajes;
   public $plugins;
   public $tr_options;
   public $tr_updates;
   public $updates;
   public $version;
   public $xid;
   
   private $cache;
   private $download_list2;
   private $plugin_updates;
   private $uptime;
   
   public function __construct()
   {
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      
      $this->btn_fin = FALSE;
      $this->cache = new fs_cache();
      $this->errores = '';
      $this->mensajes = '';
      $this->plugins = array();
      $this->tr_options = '';
      $this->tr_updates = '';
      $this->version = '';
      $this->xid();
      
      if( isset($_COOKIE['user']) AND isset($_COOKIE['logkey']) )
      {
         /// solamente comprobamos si no hay que hacer nada
         if( !isset($_GET['update']) AND !isset($_GET['reinstall']) AND !isset($_GET['plugin']) AND !isset($_GET['idplugin']) )
         {
            /// ¿Están todos los permisos correctos?
            foreach($this->__are_writable($this->__get_all_sub_directories('.')) as $dir)
            {
               $this->errores .= 'No se puede escribir sobre el directorio ' . $dir . '<br/>';
            }
         }
         
         if($this->errores != '')
         {
            $this->errores .= 'Tienes que corregir estos errores antes de continuar.';
         }
         else if( isset($_GET['update']) OR isset($_GET['reinstall']) )
         {
            $this->actualizar_nucleo();
         }
         else if( isset($_GET['plugin']) )
         {
            $this->actualizar_plugin();
         }
         else if( isset($_GET['idplugin']) AND isset($_GET['name']) AND isset($_GET['key']) )
         {
            $this->actualizar_plugin_pago();
         }
         else if( isset($_GET['idplugin']) AND isset($_GET['name']) AND isset($_POST['key']) )
         {
            $private_key = $_POST['key'];
            if( file_put_contents('tmp/' . FS_TMP_NAME . 'private_keys/' . $_GET['idplugin'], $private_key) )
            {
               $this->mensajes = 'Clave añadida correctamente.';
            }
            else
               $this->errores = 'Error al guardar la clave.';
         }
         
         if($this->errores == '')
         {
            $this->comprobar_actualizaciones();
         }
         else
         {
            $this->tr_updates = '<tr class="warning"><td colspan="5">Aplazada la comprobación'
                    . ' de plugins hasta que resuelvas los problemas.</td></tr>';
         }
      }
      else
         $this->errores = '<a href="index.php">Debes iniciar sesi&oacute;n</a>';
   }
   
   private function comprobar_actualizaciones()
   {
      if( !isset($this->updates) )
      {
         /// comprobamos la lista de actualizaciones de cache
         $this->updates = $this->cache->get('updater_lista');
         if($this->updates)
         {
            $this->plugin_updates = $this->updates['plugins'];
         }
         else
         {
            /// si no está en cache, nos toca comprobar todo
            $this->updates = array(
                'version' => '',
                'core' => FALSE,
                'plugins' => array(),
            );
            
            $version_actual = file_get_contents('VERSION');
            $this->updates['version'] = $version_actual;
            $nueva_version = @$this->curl_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/VERSION');
            if( floatval($version_actual) < floatval($nueva_version) )
            {
               $this->updates['core'] = $nueva_version;
            }
            else
            {
               /// comprobamos los plugins
               foreach($this->check_for_plugin_updates() as $plugin)
               {
                  $this->updates['plugins'][] = $plugin;
               }
            }
            
            /// guardamos la lista de actualizaciones en cache
            $this->cache->set('updater_lista', $this->updates);
         }
      }
      
      $this->version = $this->updates['version'];
      
      if($this->updates['core'])
      {
         $this->tr_updates = '<tr>'
                 . '<td><b>Núcleo</b></td>'
                 . '<td>Núcleo de FacturaScripts.</td>'
                 . '<td class="text-right">'.$this->version.'</td>'
                 . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                 . $this->updates['core'].'" target="_blank">'.$this->updates['core'].'</a></td>'
                 . '<td class="text-right">
                    <a class="btn btn-sm btn-primary" href="updater.php?update=TRUE" role="button">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar
                    </a></td>'
                 . '</tr>';
      }
      else
      {
         $this->tr_options = '<tr>'
                 . '<td><b>Núcleo</b></td>'
                 . '<td>Núcleo de FacturaScripts.</td>'
                 . '<td class="text-right">'.$this->version.'</td>'
                 . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                 . $this->version.'" target="_blank">'.$this->version.'</a></td>'
                 . '<td class="text-right">
                    <a class="btn btn-xs btn-default" href="updater.php?reinstall=TRUE" role="button">
                        <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>&nbsp; Reinstalar
                    </a></td>'
                 . '</tr>';
         
         foreach($this->updates['plugins'] as $plugin)
         {
            if($plugin['depago'])
            {
               if(!$this->xid)
               {
                  /// nada
               }
               else if($plugin['private_key'])
               {
                  $this->tr_updates .= '<tr>'
                          . '<td>'.$plugin['name'].'</td>'
                          . '<td>'.$plugin['description'].'<br/>'
                          . '<a href="#" data-toggle="modal" data-target="#modal_key_'.$plugin['name'].'">'
                          . '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Cambiar la clave'
                          . '</a>'
                          . '</td>'
                          . '<td class="text-right">'.$plugin['version'].'</td>'
                          . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                          . $plugin['new_version'].'&plugin='.$plugin['name'].'" target="_blank">'.$plugin['new_version'].'</a></td>'
                          . '<td class="text-right">'
                          . '<div class="btn-group">'
                          . '<a href="updater.php?idplugin='.$plugin['idplugin'].'&name='.$plugin['name'].'&key='.$plugin['private_key']
                          . '" class="btn btn-xs btn-primary">'
                          . '<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar'
                          . '</a>'
                          . '</div>'
                          . '</td></tr>';
               }
               else
               {
                  $this->tr_updates .= '<tr>'
                          . '<td>'.$plugin['name'].'</td>'
                          . '<td>'.$plugin['description'].'</td>'
                          . '<td class="text-right">'.$plugin['version'].'</td>'
                          . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                          . $plugin['new_version'].'&plugin='.$plugin['name'].'" target="_blank">'.$plugin['new_version'].'</a></td>'
                          . '<td class="text-right">'
                          . '<div class="btn-group">'
                          . '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modal_key_'.$plugin['name'].'">'
                          . '<i class="fa fa-key" aria-hidden="true"></i>&nbsp; Añadir clave'
                          . '</a>'
                          . '</div>'
                          . '</td></tr>';
               }
            }
            else
            {
               $this->tr_updates .= '<tr>'
                       . '<td>'.$plugin['name'].'</td>'
                       . '<td>'.$plugin['description'].'</td>'
                       . '<td class="text-right">'.$plugin['version'].'</td>'
                       . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                       . $plugin['new_version'].'&plugin='.$plugin['name'].'" target="_blank">'.$plugin['new_version'].'</a></td>'
                       . '<td class="text-right">'
                       . '<a href="updater.php?plugin='.$plugin['name'].'" class="btn btn-xs btn-primary">'
                       . '<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar'
                       . '</a>'
                       . '</td></tr>';
            }
         }
         
         if($this->tr_updates == '')
         {
            $this->tr_updates = '<tr class="success"><td colspan="5">El sistema está actualizado.'
                    . ' <a href="index.php?page=admin_home&updated=TRUE">Volver</a></td></tr>';
            $this->btn_fin = TRUE;
         }
      }
   }
   
   /**
    * Elimina la actualización de la lista de pendientes.
    * @param type $plugin
    */
   private function actualizacion_correcta($plugin = FALSE)
   {
      if( !isset($this->updates) )
      {
         /// comprobamos la lista de actualizaciones de cache
         $this->updates = $this->cache->get('updater_lista');
      }
      
      if($this->updates)
      {
         if($plugin)
         {
            foreach($this->updates['plugins'] as $i => $pl)
            {
               if($pl['name'] == $plugin)
               {
                  unset($this->updates['plugins'][$i]);
                  break;
               }
            }
         }
         else
         {
            /// hemos actualizado el core
            $this->updates['core'] = FALSE;
         }
         
         /// guardamos la lista de actualizaciones en cache
         $this->cache->set('updater_lista', $this->updates);
      }
   }
   
   private function actualizar_nucleo()
   {
      $urls = array(
          'https://github.com/NeoRazorX/facturascripts_2015/archive/master.zip',
          'https://codeload.github.com/NeoRazorX/facturascripts_2015/zip/master'
      );
      
      foreach($urls as $url)
      {
         if( @file_put_contents('update.zip', $this->curl_get_contents($url)) )
         {
            $zip = new ZipArchive();
            $zip_status = $zip->open('update.zip', ZipArchive::CHECKCONS);
            
            if($zip_status !== TRUE)
            {
               $this->errores = 'Ha habido un error con el archivo update.zip. Código: '.$zip_status;
            }
            else if( !$this->test_zip_nucleo($zip) )
            {
               $this->errores = 'Ha habido un error con el archivo update.zip';
            }
            else
            {
               $zip->extractTo('.');
               $zip->close();
               unlink('update.zip');
               
               /// eliminamos archivos antiguos
               $this->del_tree('base/');
               $this->del_tree('controller/');
               $this->del_tree('extras/');
               $this->del_tree('model/');
               $this->del_tree('raintpl/');
               $this->del_tree('view/');
               
               /// ahora hay que copiar todos los archivos de facturascripts-master a . y borrar
               $this->recurse_copy('facturascripts_2015-master/', '.');
               $this->del_tree('facturascripts_2015-master/');
               
               $this->mensajes = 'Actualizado correctamente.';
               $this->actualizacion_correcta();
               break;
            }
         }
         else
            $this->errores = 'Error al descargar el archivo zip.';
      }
   }
   
   /**
    * Comprueba la integridad del zip del núcleo.
    * @param ZipArchive $zip
    */
   private function test_zip_nucleo(&$zip)
   {
      $ok = FALSE;
      for($i = 0; $i < $zip->numFiles; $i++)
      {
         $data = $zip->statIndex($i);
         if( basename($data['name']) == 'updater.php' )
         {
            $ok = TRUE;
            break;
         }
      }
      
      return $ok;
   }
   
   private function actualizar_plugin()
   {
      /// leemos el ini del plugin
      $plugin_ini = parse_ini_file('plugins/' . $_GET['plugin'] . '/facturascripts.ini');
      if($plugin_ini)
      {
         /// descargamos el zip
         if( @file_put_contents('update.zip', $this->curl_get_contents($plugin_ini['update_url'])) )
         {
            $zip = new ZipArchive();
            $zip_status = $zip->open('update.zip', ZipArchive::CHECKCONS);
            
            if($zip_status !== TRUE)
            {
               $this->errores = 'Ha habido un error con el archivo update.zip. Código: '.$zip_status;
            }
            else if( !$this->test_zip_plugin($zip) )
            {
               $this->errores = 'Ha habido un error con el archivo update.zip';
            }
            else
            {
               /// nos guardamos la lista previa de plugins
               $plugins_list = scandir(getcwd().'/plugins');
               
               /// eliminamos los archivos antiguos
               $this->del_tree('plugins/' . $_GET['plugin']);
               
               /// descomprimimos
               $zip->extractTo('plugins/');
               $zip->close();
               unlink('update.zip');
               
               /// renombramos si es necesario
               foreach( scandir(getcwd().'/plugins') as $f)
               {
                  if( $f != '.' AND $f != '..' AND is_dir('plugins/'.$f) )
                  {
                     $encontrado2 = FALSE;
                     foreach($plugins_list as $f2)
                     {
                        if($f == $f2)
                        {
                           $encontrado2 = TRUE;
                           break;
                        }
                     }
                     
                     if(!$encontrado2)
                     {
                        rename('plugins/'.$f, 'plugins/'.$_GET['plugin']);
                        break;
                     }
                  }
               }
               
               $this->mensajes = 'Plugin actualizado correctamente.';
               $this->actualizacion_correcta($_GET['plugin']);
            }
         }
         else
            $this->errores = 'Error al descargar el archivo zip.';
      }
      else
         $this->errores = 'Error al leer el archivo plugins/' . $_GET['plugin'] . '/facturascripts.ini';
   }
   
   /**
    * Comprueba la integridad del zip de un plugin.
    * @param ZipArchive $zip
    * @return boolean
    */
   private function test_zip_plugin(&$zip)
   {
      $ok = FALSE;
      for($i = 0; $i < $zip->numFiles; $i++)
      {
         $data = $zip->statIndex($i);
         if( basename($data['name']) == 'facturascripts.ini' )
         {
            $ok = TRUE;
            break;
         }
      }
      
      return $ok;
   }
   
   private function actualizar_plugin_pago()
   {
      $url = 'https://www.facturascripts.com/comm3/index.php?page=community_edit_plugin&id='.
              $_GET['idplugin'].'&xid='.$this->xid.'&key='.$_GET['key'];
      
      /// descargamos el zip
      if( @file_put_contents('update.zip', $this->curl_get_contents($url)) )
      {
         $zip = new ZipArchive();
         $zip_status = $zip->open('update.zip', ZipArchive::CHECKCONS);
         
         if($zip_status !== TRUE)
         {
            $this->errores = 'Ha habido un error con el archivo update.zip. Código: '.$zip_status;
         }
         else if( !$this->test_zip_plugin($zip) )
         {
            $this->errores = 'Ha habido un error con el archivo update.zip';
         }
         else
         {
            /// eliminamos los archivos antiguos
            $this->del_tree('plugins/' . $_GET['name']);
            
            /// descomprimimos
            $zip->extractTo('plugins/');
            $zip->close();
            unlink('update.zip');
            
            if( file_exists('plugins/' . $_GET['name'] . '-master') )
            {
               /// renombramos el directorio
               rename('plugins/' . $_GET['name'] . '-master', 'plugins/' . $_GET['name']);
            }
            
            $this->mensajes = 'Plugin actualizado correctamente.';
            $this->actualizacion_correcta($_GET['name']);
         }
      }
      else
         $this->errores = 'Error al descargar el archivo zip. <a href="updater.php?idplugin='.
              $_GET['idplugin'].'&name='.$_GET['name'].'">¿Clave incorrecta?</a>';
   }

   private function recurse_copy($src, $dst)
   {
      $dir = opendir($src);
      @mkdir($dst);
      while(false !== ( $file = readdir($dir)))
      {
         if(( $file != '.' ) && ( $file != '..' ))
         {
            if( is_dir($src . '/' . $file) )
            {
               $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
            }
            else
            {
               copy($src . '/' . $file, $dst . '/' . $file);
            }
         }
      }
      closedir($dir);
   }

   private function del_tree($dir)
   {
      $files = array_diff(scandir($dir), array('.', '..'));
      foreach($files as $file)
      {
         (is_dir("$dir/$file")) ? $this->del_tree("$dir/$file") : unlink("$dir/$file");
      }
      return rmdir($dir);
   }

   private function __get_all_sub_directories($base_dir)
   {
      $directories = array();

      foreach(scandir($base_dir) as $file)
      {
         if($file == '.' || $file == '..')
         {
            continue;
         }

         $dir = $base_dir . DIRECTORY_SEPARATOR . $file;
         if( is_dir($dir) )
         {
            $directories[] = $dir;
            $directories = array_merge($directories, $this->__get_all_sub_directories($dir));
         }
      }

      return $directories;
   }

   private function __are_writable($dirlist)
   {
      $notwritable = array();

      foreach($dirlist as $dir)
      {
         if( !is_writable($dir) )
         {
            $notwritable[] = $dir;
         }
      }
      
      return $notwritable;
   }

   /**
    * Descarga el contenido con curl o file_get_contents
    * @param type $url
    * @param type $timeout
    * @return type
    */
   private function curl_get_contents($url)
   {
      if( function_exists('curl_init') )
      {
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
         if( defined('FS_PROXY_TYPE') )
         {
            curl_setopt($ch, CURLOPT_PROXYTYPE, FS_PROXY_TYPE);
            curl_setopt($ch, CURLOPT_PROXY, FS_PROXY_HOST);
            curl_setopt($ch, CURLOPT_PROXYPORT, FS_PROXY_PORT);
         }
         $data = curl_exec($ch);
         $info = curl_getinfo($ch);
         
         if($info['http_code'] == 301 OR $info['http_code'] == 302)
         {
            $redirs = 0;
            return $this->curl_redirect_exec($ch, $redirs);
         }
         else
         {
            curl_close($ch);
            return $data;
         }
      }
      else
         return file_get_contents($url);
   }
   
   /**
    * Función alternativa para cuando el followlocation falla.
    * @param type $ch
    * @param type $redirects
    * @param type $curlopt_header
    * @return type
    */
   private function curl_redirect_exec($ch, &$redirects, $curlopt_header = false)
   {
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if( defined('FS_PROXY_TYPE') )
      {
         curl_setopt($ch, CURLOPT_PROXYTYPE, FS_PROXY_TYPE);
         curl_setopt($ch, CURLOPT_PROXY, FS_PROXY_HOST);
         curl_setopt($ch, CURLOPT_PROXYPORT, FS_PROXY_PORT);
      }
      $data = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
      if($http_code == 301 || $http_code == 302)
      {
         list($header) = explode("\r\n\r\n", $data, 2);
         $matches = array();
         preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
         $url = trim(str_replace($matches[1], "", $matches[0]));
         $url_parsed = parse_url($url);
         if( isset($url_parsed) )
         {
            curl_setopt($ch, CURLOPT_URL, $url);
            $redirects++;
            return $this->curl_redirect_exec($ch, $redirects, $curlopt_header);
         }
      }
      
      if($curlopt_header)
      {
         curl_close($ch);
         return $data;
      }
      else
      {
         list(, $body) = explode("\r\n\r\n", $data, 2);
         curl_close($ch);
         return $body;
      }
   }
   
   public function check_for_plugin_updates()
   {
      if( !isset($this->plugin_updates) )
      {
         $this->plugin_updates = array();
         foreach( scandir(getcwd() . '/plugins') as $f )
         {
            if( $f != '.' AND $f != '..' AND is_dir('plugins/' . $f) )
            {
               $plugin = array(
                   'name' => $f,
                   'description' => 'Sin descripción.',
                   'version' => 0,
                   'update_url' => '',
                   'version_url' => '',
                   'new_version' => 0,
                   'depago' => FALSE,
                   'idplugin' => NULL,
                   'private_key' => FALSE
               );
               
               $this->plugins[] = $plugin['name'];
               
               if( file_exists('plugins/' . $f . '/facturascripts.ini') )
               {
                  if( file_exists('plugins/' . $f . '/description') )
                  {
                     $plugin['description'] = file_get_contents('plugins/' . $f . '/description');
                  }
                  
                  $ini_file = parse_ini_file('plugins/' . $f . '/facturascripts.ini');
                  if( isset($ini_file['version']) )
                  {
                     $plugin['version'] = intval($ini_file['version']);
                  }
                  
                  if( isset($ini_file['update_url']) )
                  {
                     $plugin['update_url'] = $ini_file['update_url'];
                  }
                  
                  if( isset($ini_file['version_url']) )
                  {
                     $plugin['version_url'] = $ini_file['version_url'];
                  }
                  
                  if( isset($ini_file['idplugin']) )
                  {
                     $plugin['idplugin'] = $ini_file['idplugin'];
                  }
                  
                  if($plugin['version_url'] != '' AND $plugin['update_url'] != '')
                  {
                     /// plugin con descarga gratuita
                     $internet_ini = @parse_ini_string($this->curl_get_contents($plugin['version_url']));
                     if($internet_ini)
                     {
                        if( $plugin['version'] < intval($internet_ini['version']) )
                        {
                           $plugin['new_version'] = intval($internet_ini['version']);
                           $this->plugin_updates[] = $plugin;
                        }
                     }
                  }
                  else if($plugin['idplugin'])
                  {
                     /// plugin de pago/oculto
                     
                     foreach($this->download_list2() as $ditem)
                     {
                        if($ditem->id == $plugin['idplugin'])
                        {
                           if( intval($ditem->version) > $plugin['version'] )
                           {
                              $plugin['new_version'] = intval($ditem->version);
                              $plugin['depago'] = TRUE;
                              
                              if( file_exists('tmp/'.FS_TMP_NAME.'private_keys/'.$plugin['idplugin']) )
                              {
                                 $plugin['private_key'] = trim( @file_get_contents('tmp/'.FS_TMP_NAME.'private_keys/'.$plugin['idplugin']) );
                              }
                              else if( !file_exists('tmp/'.FS_TMP_NAME.'private_keys/') )
                              {
                                 if( mkdir('tmp/'.FS_TMP_NAME.'private_keys/') )
                                 {
                                    file_put_contents('tmp/'.FS_TMP_NAME.'private_keys/.htaccess', 'Deny from all');
                                 }
                              }
                              
                              $this->plugin_updates[] = $plugin;
                           }
                           break;
                        }
                     }
                  }
               }
            }
         }
      }
      
      return $this->plugin_updates;
   }
   
   private function download_list2()
   {
      if( !isset($this->download_list2) )
      {
         $cache = new fs_cache();
         
         /**
          * Download_list2 es la lista de plugins de la comunidad, se descarga de Internet.
          */
         $this->download_list2 = $cache->get('download_list2');
         if(!$this->download_list2)
         {
            $json = @$this->curl_get_contents('https://www.facturascripts.com/comm3/index.php?page=community_plugins&json2=TRUE', 5);
            if($json)
            {
               $this->download_list2 = json_decode($json);
               $cache->set('download_list2', $this->download_list2);
            }
            else
            {
               $this->download_list2 = array();
            }
         }
      }
      
      return $this->download_list2;
   }
   
   private function xid()
   {
      $this->xid = '';
      $e = $this->cache->get_array('empresa');
      if($e)
      {
         $this->xid = $e[0]['xid'];
         if( !isset($_COOKIE['uxid']) )
         {
            setcookie('uxid', $this->xid, 10800);
         }
      }
      else if( isset($_COOKIE['uxid']) )
      {
         $this->xid = $_COOKIE['uxid'];
      }
   }
   
   /**
    * Devuelve la duración de la ejecución de la página
    * @return type un string con la duración de la ejecución
    */
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
}

$updater = new fs_updater();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Actualizador de FacturaScripts</title>
      <meta name="description" content="Script de actualización de FacturaScripts." />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="generator" content="FacturaScripts" />
      <link rel="shortcut icon" href="view/img/favicon.ico" />
      <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
      <link rel="stylesheet" href="view/css/font-awesome.min.css" />
      <script type="text/javascript" src="view/js/jquery.min.js"></script>
      <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
   </head>
   <body>
      <br/>
      <div class="container-fluid">
         <div class="row">
            <div class="col-sm-12">
               <a href="index.php?page=admin_home&updated=TRUE" class="btn btn-sm btn-default">
                  <span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
                  <span class="hidden-xs">&nbsp; Panel de control</span>
               </a>
               <a href="https://www.facturascripts.com/comm3/index.php?page=community_tus_plugins" target="_blank" class="btn btn-sm btn-default">
                  <i class="fa fa-key" aria-hidden="true"></i>
                  <span class="hidden-xs">&nbsp; Claves</span>
               </a>
               <div class="page-header">
                  <h1>
                     <span class="glyphicon glyphicon-upload" aria-hidden="true"></span> Actualizador de FacturaScripts
                  </h1>
               </div>
               <?php
               if($updater->errores != '')
               {
                  echo '<div class="alert alert-danger">'.$updater->errores.'</div>';
               }
               else if($updater->mensajes != '')
               {
                  echo '<div class="alert alert-info">'.$updater->mensajes.'</div>';
                  
                  if($updater->btn_fin)
                  {
                     echo '<a href="index.php?page=admin_home&updated=TRUE" class="btn btn-sm btn-info">'
                             . '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> &nbsp; Finalizar'
                           . '</a></br/></br/>';
                  }
               }
               ?>
            </div>
         </div>
         <div class="row">
            <div class="col-sm-9">
               <p class="help-block">
                  Este actualizador permite actualizar <b>tanto el núcleo</b> de FacturaScripts
                  <b>como sus plugins</b>, incluso los de pago y los privados.
                  Si hay una actualización del núcleo tendrás que actualizar antes de poder ver si
                  también hay actualizaciones de plugins.
               </p>
               <br/>
               <ul class="nav nav-tabs" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#actualizaciones" aria-controls="actualizaciones" role="tab" data-toggle="tab">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
                        <span class="hidden-xs">&nbsp; Actualizaciones</span>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#opciones" aria-controls="opciones" role="tab" data-toggle="tab">
                        <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                        <span class="hidden-xs">&nbsp; Opciones</span>
                     </a>
                  </li>
               </ul>
               <div class="tab-content">
                  <div role="tabpanel" class="tab-pane active" id="actualizaciones">
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
                           <?php echo $updater->tr_updates; ?>
                        </table>
                     </div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="opciones">
                     <div class="table-responsive">
                        <table class="table table-hover">
                           <thead>
                              <tr>
                                 <th class="text-left">Opción</th>
                                 <th></th>
                              </tr>
                           </thead>
                           <?php echo $updater->tr_options; ?>
                        </table>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-sm-1"></div>
            <div class="col-sm-2">
               <a href="https://www.facturascripts.com/promo" target="_blank" class="thumbnail" style="margin-bottom: 0px;">
                  <img src='https://i.imgur.com/5XRa2Cm.png' alt="adminlte"/>
               </a>
               <p class="help-block">
                  Hemos trabajado en un <b>nuevo diseño</b> para dar un aspecto más moderno a FacturaScripts,
                  con un nuevo menú lateral, buscador integrado, más visibilidad del usuario,
                  más y mejores iconos y un largo etcétera. Este es el resultado.
               </p>
               <br/>
               <a href="https://www.facturascripts.com/ideas#mejoras" target="_blank" class="btn btn-block btn-default">
                  <i class="fa fa-trophy fa-3x" aria-hidden="true"></i>
                  <br/>Mejoras
               </a>
               <p class="help-block">
                  Tenemos una serie de mejoras en el horno que te pueden interesar,
                  pero necesitan financiación ¿Te apuntas? Sólo necesitas <b>5 €</b>.
               </p>
            </div>
         </div>
      </div>
      <?php
      if( !isset($updater->updates) )
      {
         /// nada
      }
      else if($updater->updates['plugins'])
      {
         foreach($updater->check_for_plugin_updates() as $plug)
         {
            if($plug['depago'])
            {
               ?>
         <form action="updater.php?idplugin=<?php echo $plug['idplugin'].'&name='.$plug['name']; ?>" method="post" class="form">
            <div class="modal" id="modal_key_<?php echo $plug['name']; ?>" tabindex="-1" role="dialog">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">
                           <i class="fa fa-key" aria-hidden="true"></i> Añadir clave de actualización
                        </h4>
                        <p class="help-block">Imprescindible para actualizar el plugin <b><?php echo $plug['name']; ?></b>.</p>
                     </div>
                     <div class="modal-body">
                        <div class="row">
                           <div class="col-xs-12">
                              <div class="form-group">
                                 Clave:
                                 <input type="text" name="key" class="form-control" autocomplete="off" autofocus=""/>
                                 <p class="help-block">
                                    ¿No sabes cual es tu clave? Puedes consultarla pulsando el botón
                                    <b>ver mis claves</b>.
                                 </p>
                              </div>
                           </div>
                        </div>
                        <div class="row">
                           <div class="col-xs-6">
                              <a href="https://www.facturascripts.com/comm3/index.php?page=community_tus_plugins" target="_blank" class="btn btn-sm btn-warning">
                                 <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                                 <span class="hidden-xs">&nbsp; Ver mis claves</span>
                              </a>
                           </div>
                           <div class="col-xs-6 text-right">
                              <button type="submit" class="btn btn-sm btn-primary">
                                 <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                 <span class="hidden-xs">&nbsp; Añadir</span>
                              </button>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </form>
            <?php
            }
         }
      }
      ?>
      <br/><br/>
      <div class="container-fluid">
         <div class="row">
            <div class="col-sm-12">
               <hr/>
            </div>
         </div>
         <div class="row">
            <div class="col-xs-6">
               <small>
                  Creado con <a target="_blank" href="https://www.facturascripts.com">FacturaScripts</a>.
               </small>
            </div>
            <div class="col-xs-6 text-right">
               <span class="label label-default">
                  <span class="glyphicon glyphicon-time" aria-hidden="true"></span>
                  &nbsp; <?php echo $updater->duration(); ?>
               </span>
            </div>
         </div>
      </div>
      <?php
      if(!FS_DEMO)
      {
         $url = 'https://www.facturascripts.com/comm3/index.php?page=community_stats'
                 . '&add=TRUE&version='.$updater->version.'&plugins='.join(',', $updater->plugins);
         ?>
         <div style="display: none;">
            <iframe src="<?php echo $url; ?>" height="0"></iframe>
         </div>
         <?php
      }
      ?>
   </body>
</html>