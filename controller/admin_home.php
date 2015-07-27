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

class admin_home extends fs_controller
{
   public $download_list;
   public $download_list2;
   public $last_download_check;
   public $new_downloads;
   public $paginas;
   public $step;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Panel de control', 'admin', TRUE, TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->download_list = array(
          'facturacion_base' => array(
              'url' => 'https://github.com/NeoRazorX/facturacion_base/archive/master.zip',
              'description' => 'Permite la gestión básica de una empresa: gestión de ventas, de compras y contabilidad básica.'
          ),
          'colombia' => array(
              'url' => 'https://github.com/salvaWEBco/colombia/archive/master.zip',
              'description' => 'Plugin de adaptación de FacturaScripts a <b>Colombia</b>.'
          ),
          'peru' => array(
              'url' => 'https://github.com/NeoRazorX/peru/archive/master.zip',
              'description' => 'Plugin de adaptación de FacturaScripts a <b>Perú</b>.'
          ),
      );
      $fsvar = new fs_var();
      $this->step = $fsvar->simple_get('install_step');
      
      /**
       * Pestaña avanzado
       */
      $guardar = FALSE;
      foreach($GLOBALS['config2'] as $i => $value)
      {
         if( isset($_POST[$i]) )
         {
            $GLOBALS['config2'][$i] = $_POST[$i];
            $guardar = TRUE;
         }
      }
      
      /**
       * Pestaña descargas
       */
      if( !isset($_GET['check4updates']) )
      {
         /**
          * Usamos last_download_check para almacenar la última vez que vimos las descargas.
          * Así podemos saber qué descargas son nuevas.
          */
         $this->last_download_check = $fsvar->simple_get('last_download_check');
         if(!$this->last_download_check)
         {
            $this->last_download_check = Date('d-m-Y', strtotime('-1day'));
         }
         $this->new_downloads = 0;
         
         $this->download_list2 = $this->cache->get('download_list');
         if(!$this->download_list2)
         {
            $this->download_list2 = json_decode( @$this->curl_get_contents('https://www.facturascripts.com/comm3/index.php?page=community_plugins&json=TRUE', 5) );
            if($this->download_list2)
            {
               $this->cache->set('download_list', $this->download_list2);
            }
            else
               $this->download_list2 = array();
         }
         foreach($this->download_list2 as $i => $di)
         {
            $this->download_list2[$i]->nuevo = FALSE;
            if( strtotime($di->creado) > strtotime($this->last_download_check) )
            {
               $this->new_downloads++;
               $this->download_list2[$i]->nuevo = TRUE;
            }
         }
         /// ahora nos guardamos last_download_check
         $this->last_download_check = Date('d-m-Y', strtotime('-1day'));
         $fsvar->simple_save('last_download_check', $this->last_download_check);
      }
      
      if( isset($_GET['check4updates']) )
      {
         $this->template = FALSE;
         if( $this->check_for_updates2() )
         {
            echo 'Hay actualizaciones disponibles.';
         }
         else
            echo 'No hay actualizaciones.';
      }
      else if( isset($_GET['updated']) )
      {
         /// el sistema ya se ha actualizado
         $fsvar->name = 'updates';
         $fsvar->delete();
      }
      else if( !$this->user->admin )
      {
         $this->new_error_msg('Sólo un administrador puede hacer cambios en esta página.');
      }
      else if(FS_DEMO)
      {
         $this->new_error_msg('En el modo demo no se pueden hacer cambios en esta página.');
      }
      else if( isset($_POST['modpages']) )
      {
         if(!$this->step)
         {
            $this->step = '1';
            $fsvar->simple_save('install_step', $this->step);
         }
         
         foreach($this->all_pages() as $p)
         {
            if( !$p->exists ) /// la página está en la base de datos pero ya no existe el controlador
            {
               if( $p->delete() )
               {
                  $this->new_message('Se ha eliminado automáticamente la página '.$p->name.
                          ' ya que no tiene un controlador asociado en la carpeta controller.');
               }
            }
            else if( !isset($_POST['enabled']) ) /// ninguna página marcada
            {
               $this->disable_page($p);
            }
            else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) ) /// página no activa marcada para activar
            {
               $this->enable_page($p);
            }
            else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) ) /// págine activa no marcada (desactivar)
            {
               $this->disable_page($p);
            }
         }
         
         $this->new_message('Datos guardados correctamente.');
      }
      else if( isset($_GET['enable']) )
      {
         $this->enable_plugin($_GET['enable']);
         
         if($this->step == '1')
         {
            $this->step = '2';
            $fsvar->simple_save('install_step', $this->step);
         }
      }
      else if( isset($_GET['disable']) )
      {
         $this->disable_plugin($_GET['disable']);
      }
      else if( isset($_GET['delete_plugin']) )
      {
         if( is_writable('plugins/'.$_GET['delete_plugin']) )
         {
            if( $this->delTree('plugins/'.$_GET['delete_plugin']) )
            {
               $this->new_message('Plugin '.$_GET['delete_plugin'].' eliminado correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar el plugin '.$_GET['delete_plugin']);
         }
         else
            $this->new_error_msg('No tienes permisos de escritura sobre la carpeta plugins/'.$_GET['delete_plugin']);
      }
      else if( isset($_POST['install']) )
      {
         if( is_uploaded_file($_FILES['fplugin']['tmp_name']) )
         {
            $zip = new ZipArchive();
            if( $zip->open($_FILES['fplugin']['tmp_name']) )
            {
               $zip->extractTo('plugins/');
               $zip->close();
               $this->new_message('Plugin '.$_FILES['fplugin']['name'].' añadido correctamente. Ya puedes activarlo.');
            }
            else
               $this->new_error_msg('Archivo no encontrado.');
         }
      }
      else if( isset($_GET['download']) )
      {
         $this->download1();
      }
      else if( isset($_GET['download2']) )
      {
         $this->download2();
      }
      else if( isset($_GET['reset']) )
      {
         if( file_exists('tmp/'.FS_TMP_NAME.'config2.ini') )
         {
            unlink('tmp/'.FS_TMP_NAME.'config2.ini');
            $this->new_message('Configuración reiniciada correctamente, pulsa <a href="'.$this->url().'#avanzado">aquí</a> para continuar.');
         }
      }
      else if($guardar)
      {
         $file = fopen('tmp/'.FS_TMP_NAME.'config2.ini', 'w');
         if($file)
         {
            foreach($GLOBALS['config2'] as $i => $value)
            {
               if( is_numeric($value) )
               {
                  fwrite($file, $i." = ".$value.";\n");
               }
               else
               {
                  fwrite($file, $i." = '".$value."';\n");
               }
            }
            
            fclose($file);
         }
         
         $this->new_message('Datos guardados correctamente.');
      }
      
      
      $this->paginas = $this->all_pages();
      $this->load_menu(TRUE);
   }
   
   private function all_pages()
   {
      $pages = array();
      $page_names = array();
      
      /// añadimos las páginas de los plugins
      foreach($this->plugins() as $plugin)
      {
         if( file_exists(getcwd().'/plugins/'.$plugin.'/controller') )
         {
            foreach( scandir(getcwd().'/plugins/'.$plugin.'/controller') as $f )
            {
               if( substr($f, -4) == '.php' )
               {
                  $p = new fs_page();
                  $p->name = substr($f, 0, -4);
                  $p->exists = TRUE;
                  $p->show_on_menu = FALSE;
                  
                  if( !in_array($p->name, $page_names) )
                  {
                     $pages[] = $p;
                     $page_names[] = $p->name;
                  }
               }
            }
         }
      }
      
      /// añadimos las páginas que están en el directorio controller
      foreach( scandir(getcwd().'/controller') as $f)
      {
         if( substr($f, -4) == '.php' )
         {
            $p = new fs_page();
            $p->name = substr($f, 0, -4);
            $p->exists = TRUE;
            $p->show_on_menu = FALSE;
            
            if( !in_array($p->name, $page_names) )
            {
               $pages[] = $p;
               $page_names[] = $p->name;
            }
         }
      }
      
      /// completamos los datos de las páginas con los datos de la base de datos
      foreach($this->page->all() as $p)
      {
         $encontrada = FALSE;
         foreach($pages as $i => $value)
         {
            if($p->name == $value->name)
            {
               $pages[$i] = $p;
               $pages[$i]->enabled = TRUE;
               $pages[$i]->exists = TRUE;
               $encontrada = TRUE;
               break;
            }
         }
         if( !$encontrada )
         {
            $p->enabled = TRUE;
            $pages[] = $p;
         }
      }
      
      /// ordenamos
      usort($pages, function($a,$b){
         if($a->name == $b->name)
         {
            return 0;
         }
         else if($a->name > $b->name)
         {
            return 1;
         }
         else
            return -1;
      });
      
      return $pages;
   }
   
   private function plugins()
   {
      return $GLOBALS['plugins'];
   }
   
   private function enable_page($page)
   {
      /// primero buscamos en los plugins
      $found = FALSE;
      foreach($this->plugins() as $plugin)
      {
         if( file_exists('plugins/'.$plugin.'/controller/'.$page->name.'.php') )
         {
            require_once 'plugins/'.$plugin.'/controller/'.$page->name.'.php';
            $new_fsc = new $page->name();
            $found = TRUE;
            
            if( !$new_fsc->page->save() )
               $this->new_error_msg("Imposible guardar la página ".$page->name);
            
            unset($new_fsc);
            break;
         }
      }
      
      if( !$found )
      {
         require_once 'controller/'.$page->name.'.php';
         $new_fsc = new $page->name(); /// cargamos el controlador asociado
         
         if( !$new_fsc->page->save() )
            $this->new_error_msg("Imposible guardar la página ".$page->name);
         
         unset($new_fsc);
      }
   }
   
   private function disable_page($page)
   {
      if($page->name == $this->page->name)
      {
         $this->new_error_msg("No puedes desactivar esta página (".$page->name.").");
      }
      else if( !$page->delete() )
      {
         $this->new_error_msg('Imposible eliminar la página '.$page->name.'.');
      }
   }
   
   public function traducciones()
   {
      $clist = array();
      $include = array(
          'factura','facturas','albaran','albaranes','pedido','pedidos',
          'presupuesto','presupuestos','provincia','apartado','cifnif',
          'iva','irpf','numero2'
      );
      
      foreach($GLOBALS['config2'] as $i => $value)
      {
         if( in_array($i, $include) )
            $clist[] = array('nombre' => $i, 'valor' => $value);
      }
      
      return $clist;
   }

   /**
   * Timezones list with GMT offset
   * 
   * @return array
   * @link http://stackoverflow.com/a/9328760
   */
   public function get_timezone_list()
   {
      $zones_array = array();
      
      $timestamp = time();
      foreach(timezone_identifiers_list() as $key => $zone) {
         date_default_timezone_set($zone);
         $zones_array[$key]['zone'] = $zone;
         $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
      }
      
      return $zones_array;
   }
   
   public function nf0()
   {
      return array(0, 1, 2, 3, 4);
   }
   
   public function nf1()
   {
      return array(
          ',' => 'coma',
          '.' => 'punto',
          ' ' => '(espacio en blanco)'
      );
   }
   
   public function plugin_advanced_list()
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
                'prioridad' => '-',
                'download2_url' => '',
                'idplugin' => NULL
            );
            
            if( file_exists('plugins/'.$f.'/facturascripts.ini') )
            {
               $plugin['compatible'] = TRUE;
               $plugin['enabled'] = in_array($f, $this->plugins());
               
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
               
               if( isset($ini_file['idplugin']) )
               {
                  $plugin['idplugin'] = $ini_file['idplugin'];
               }
               
               if( isset($ini_file['update_url']) )
               {
                  $plugin['update_url'] = $ini_file['update_url'];
               }
               
               if( isset($ini_file['version_url']) )
               {
                  $plugin['version_url'] = $ini_file['version_url'];
               }
               elseif( is_array($this->download_list2) )
               {
                  foreach($this->download_list2 as $ditem)
                  {
                     if($ditem->id == $plugin['idplugin'])
                     {
                        if( intval($ditem->version) > $plugin['version'] )
                        {
                           $plugin['download2_url'] = 'updater.php?idplugin='.$plugin['idplugin'].'&name='.$f;
                        }
                        break;
                     }
                  }
               }
               
               if($plugin['enabled'])
               {
                  foreach( array_reverse($this->plugins()) as $i => $value)
                  {
                     if($value == $f)
                     {
                        $plugin['prioridad'] = $i;
                        break;
                     }
                  }
               }
            }
            
            $plugins[] = $plugin;
         }
      }
      
      return $plugins;
   }
   
   private function delTree($dir)
   {
      $files = array_diff(scandir($dir), array('.','..'));
      foreach ($files as $file)
      {
         (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
      }
      return rmdir($dir);
   }
   
   private function enable_plugin($name)
   {
      if( substr($name, -7) == '-master' )
      {
         /// renombramos el directorio
         $name = substr($name, 0, -7);
         rename('plugins/'.$name.'-master', 'plugins/'.$name);
      }
      
      /// comprobamos las dependencias
      $install = TRUE;
      foreach($this->plugin_advanced_list() as $pitem)
      {
         if($pitem['name'] == $name)
         {
            if($pitem['require'] != '')
            {
               if( !in_array($pitem['require'], $GLOBALS['plugins']) )
               {
                  $install = FALSE;
                  $this->new_error_msg('Dependencias incumplidas: <b>'.$pitem['require'].'</b>');
               }
            }
            break;
         }
      }
      
      if( $install AND !in_array($name, $GLOBALS['plugins']) )
      {
         array_unshift($GLOBALS['plugins'], $name);
         
         if( file_put_contents('tmp/enabled_plugins.list', join(',', $GLOBALS['plugins']) ) !== FALSE )
         {
            /// cargamos el archivo functions.php
            if( file_exists('plugins/'.$name.'/functions.php') )
            {
               require_once 'plugins/'.$name.'/functions.php';
            }
            
            if( file_exists(getcwd().'/plugins/'.$name.'/controller') )
            {
               /// activamos las páginas del plugin
               $page_list = array();
               foreach( scandir(getcwd().'/plugins/'.$name.'/controller') as $f)
               {
                  if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
                  {
                     $page_name = substr($f, 0, -4);
                     $page_list[] = $page_name;
                     
                     require_once 'plugins/'.$name.'/controller/'.$f;
                     $new_fsc = new $page_name();
                     
                     if( !$new_fsc->page->save() )
                        $this->new_error_msg("Imposible guardar la página ".$page_name);
                     
                     unset($new_fsc);
                  }
               }
               
               $this->new_message('Se han activado automáticamente las siguientes páginas: '.join(', ', $page_list) . '.');
            }
            
            $this->new_message('Plugin <b>'.$name.'</b> activado correctamente.');
            $this->load_menu(TRUE);
            
            /// limpiamos la caché
            $this->cache->clean();
         }
         else
            $this->new_error_msg('Imposible activar el plugin <b>'.$name.'</b>.');
      }
   }
   
   private function disable_plugin($name)
   {
      if( file_exists('tmp/enabled_plugins.list') )
      {
         if( in_array($name, $this->plugins()) )
         {
            if( count($GLOBALS['plugins']) == 1 AND $GLOBALS['plugins'][0] == $name )
            {
               $GLOBALS['plugins'] = array();
               unlink('tmp/enabled_plugins.list');
               
               $this->new_message('Plugin <b>'.$name.'</b> desactivado correctamente.');
            }
            else
            {
               foreach($GLOBALS['plugins'] as $i => $value)
               {
                  if($value == $name)
                  {
                     unset($GLOBALS['plugins'][$i]);
                     break;
                  }
               }
               
               if( file_put_contents('tmp/enabled_plugins.list', join(',', $GLOBALS['plugins']) ) !== FALSE )
               {
                  $this->new_message('Plugin <b>'.$name.'</b> desactivado correctamente.');
               }
               else
                  $this->new_error_msg('Imposible desactivar el plugin <b>'.$name.'</b>.');
            }
         }
         
         
         /*
          * Desactivamos las páginas que ya no existen
          */
         $eliminadas = array();
         foreach($this->page->all() as $p)
         {
            $encontrada = FALSE;
            
            if( file_exists(getcwd().'/controller/'.$p->name.'.php') )
            {
               $encontrada = TRUE;
            }
            else
            {
               foreach($GLOBALS['plugins'] as $plugin)
               {
                  if( file_exists(getcwd().'/plugins/'.$plugin.'/controller/'.$p->name.'.php') AND $name != $plugin)
                  {
                     $encontrada = TRUE;
                     break;
                  }
               }
            }
            
            if( !$encontrada )
            {
               if( $p->delete() )
               {
                  $eliminadas[] = $p->name;
               }
            }
         }
         if($eliminadas)
         {
            $this->new_message('Se han eliminado automáticamente las siguientes páginas: '.join(', ', $eliminadas));
         }
         
         /// borramos los archivos temporales del motor de plantillas
         foreach( scandir(getcwd().'/tmp') as $f)
         {
            if( substr($f, -4) == '.php' )
               unlink('tmp/'.$f);
         }
         
         /// limpiamos la caché
         $this->cache->clean();
      }
   }
   
   public function check_for_updates2()
   {
      if( !$this->user->admin )
      {
         return FALSE;
      }
      else
      {
         $fsvar = new fs_var();
         
         /// comprobamos actualizaciones en los plugins
         $updates = FALSE;
         foreach($this->plugin_advanced_list() as $plugin)
         {
            if($plugin['version_url'] != '' AND $plugin['update_url'] != '')
            {
               $internet_ini = @parse_ini_string( $this->curl_get_contents($plugin['version_url']) );
               if($internet_ini)
               {
                  if( $plugin['version'] < intval($internet_ini['version']) )
                  {
                     $updates = TRUE;
                     break;
                  }
               }
            }
         }
         
         /// comprobamos actualizaciones del núcleo
         $version = file_get_contents('VERSION');
         $internet_version = $this->curl_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/VERSION');
         if( floatval($version) < floatval($internet_version) )
         {
            $updates = TRUE;
         }
         
         if($updates)
         {
            $fsvar->simple_save('updates', 'true');
            return TRUE;
         }
         else
         {
            $fsvar->name = 'updates';
            $fsvar->delete();
            return FALSE;
         }
      }
   }
   
   private function curl_get_contents($url, $timeout = 30)
   {
      if( function_exists('curl_init') )
      {
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
         curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
         $data = curl_exec($ch);
         $info = curl_getinfo($ch);
         curl_close($ch);
         
         if($info['http_code'] == 302)
         {
            return file_get_contents($url);
         }
         else
            return $data;
      }
      else
         return file_get_contents($url);
   }
   
   public function get_max_file_upload()
   {
      $max = intval( ini_get('post_max_size') );
      
      if( intval(ini_get('upload_max_filesize')) > $max )
      {
         $max = intval(ini_get('upload_max_filesize'));
      }
      
      return $max;
   }
   
   private function download1()
   {
      if( isset($this->download_list[$_GET['download']]) )
      {
         $this->new_message('Descargando el plugin '.$_GET['download']);
         
         if( @file_put_contents('download.zip', $this->curl_get_contents($this->download_list[$_GET['download']]['url']) ) )
         {
            $zip = new ZipArchive();
            if( $zip->open('download.zip') )
            {
               $zip->extractTo('plugins/');
               $zip->close();
               unlink('download.zip');
               
               /// renombramos el directorio
               if( file_exists('plugins/'.$_GET['download'].'-master') )
               {
                  rename('plugins/'.$_GET['download'].'-master', 'plugins/'.$_GET['download']);
               }
               
               $this->new_message('Plugin añadido correctamente.');
               $this->enable_plugin($_GET['download']);
               
               if($this->step == '1')
               {
                  $this->step = '2';
                  $fsvar = new fs_var();
                  $fsvar->simple_save('install_step', $this->step);
               }
            }
            else
               $this->new_error_msg('Archivo no encontrado.');
         }
         else
         {
            $this->new_error_msg('Error al descargar. Tendrás que descargarlo manualmente desde '
                    . '<a href="'.$this->download_list[$_GET['download']]['url'].'" target="_blank">aquí</a> '
                    . 'y añadirlo desde la pestaña <b>plugins</b>.');
         }
      }
      else
         $this->new_error_msg('Descarga no encontrada.');
   }
   
   private function download2()
   {
      $encontrado = FALSE;
      foreach($this->download_list2 as $item)
      {
         if( $item->id == intval($_GET['download2']) )
         {
            $this->new_message('Descargando el plugin '.$item->nombre);
            $encontrado = TRUE;
            
            if( @file_put_contents('download.zip', $this->curl_get_contents($item->zip_link) ) )
            {
               $zip = new ZipArchive();
               if( $zip->open('download.zip') )
               {
                  $plugins_list = scandir(getcwd().'/plugins');
                  $zip->extractTo('plugins/');
                  $zip->close();
                  unlink('download.zip');
                  
                  /// renombramos si es necesario
                  foreach( scandir(getcwd().'/plugins') as $f)
                  {
                     if( is_dir('plugins/'.$f) AND $f != '.' AND $f != '..')
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
                           rename('plugins/'.$f, 'plugins/'.$item->nombre);
                           break;
                        }
                     }
                  }
                  
                  $this->new_message('Plugin añadido correctamente.');
                  $this->enable_plugin($item->nombre);
               }
               else
                  $this->new_error_msg('Archivo no encontrado.');
            }
            else
            {
               $this->new_error_msg('Error al descargar. Tendrás que descargarlo manualmente desde '
                       . '<a href="'.$item->zip_link.'" target="_blank">aquí</a> y añadirlo desde la pestaña <b>plugins</b>.');
            }
            break;
         }
      }
      
      if(!$encontrado)
      {
         $this->new_error_msg('Descarga no encontrada.');
      }
   }
}
