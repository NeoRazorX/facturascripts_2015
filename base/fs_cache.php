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
 * Simple file cache
 *
 * This class is great for those who can't use apc or memcached in their proyects.
 *
 * @author Emilio Cobos (emiliocobos.net) <ecoal95@gmail.com> and github contributors
 * @author Carlos García Gómez <neorazorx@gmail.com>
 * @version 1.0.1
 * @link http://emiliocobos.net/php-cache/
 *
 */
class php_file_cache
{
   /**
	 * Configuration
	 *
	 * @access private
	 */
	private static $config;
   
   public function __construct()
   {
      self::$config = array(
          'cache_path' => 'tmp/'.FS_TMP_NAME.'cache',
          'expires' => 180,
      );
      
      if( !file_exists(self::$config['cache_path']) )
      {
         @mkdir(self::$config['cache_path']);
      }
   }
   
	/**
	 * Get a route to the file associated to that key.
	 *
	 * @access public
	 * @param string $key
	 * @return string the filename of the php file
	 */
	public function get_route($key)
   {
		return self::$config['cache_path'] . '/' . md5($key) . '.php';
	}
   
	/**
	 * Get the data associated with a key
	 *
	 * @access public
	 * @param string $key
	 * @return mixed the content you put in, or null if expired or not found
	 */
	public function get($key, $raw = false, $custom_time = NULL)
   {
		if( !$this->file_expired($file = $this->get_route($key), $custom_time))
      {
			$content = file_get_contents($file);
			return $raw ? $content : unserialize($content);
		}
      
		return NULL;
	}
   
	/**
	 * Put content into the cache
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $content the the content you want to store
	 * @param bool $raw whether if you want to store raw data or not. If it is true, $content *must* be a string
	 * @return bool whether if the operation was successful or not
	 */
	public function put($key, $content, $raw = FALSE)
   {
		$dest_file_name = $this->get_route($key);
		/** Use a unique temporary filename to make writes atomic with rewrite */
		$temp_file_name = str_replace( ".php", uniqid("-" , true).".php", $dest_file_name );
		$ret = @file_put_contents($temp_file_name, $raw ? $content : serialize($content));
		if( $ret !== FALSE)
      {
			return @rename($temp_file_name, $dest_file_name);
		}
		@unlink($temp_file_name);
		return false;
	}
   
	/**
	 * Delete data from cache
	 *
	 * @access public
	 * @param string $key
	 * @return bool true if the data was removed successfully
	 */
	public function delete($key)
   {
      $ruta = $this->get_route($key);
      if( file_exists($ruta) )
      {
         return @unlink($ruta);
      }
		else
      {
         return TRUE;
      }
	}
   
	/**
	 * Flush all cache
	 *
	 * @access public
	 * @return bool always true
	 */
	public function flush()
   {
		$cache_files = glob(self::$config['cache_path'] . '/*.php', GLOB_NOSORT);
		foreach ($cache_files as $file)
      {
			@unlink($file);
		}
		return TRUE;
	}
   
	/**
	 * Check if a file has expired or not.
	 *
	 * @access public
	 * @param $file the rout to the file
	 * @param int $time the number of minutes it was set to expire
	 * @return bool if the file has expired or not
	 */
	public function file_expired($file, $time = NULL)
   {
		if( !file_exists($file) )
      {
			return TRUE;
		}
      else
      {
         return (time() > (filemtime($file) + 60 * ($time ? $time : self::$config['expires'])));
      }
	}
}


/**
 * Clase para concectar e interactuar con memcache.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_cache
{
   private static $memcache;
   private static $php_file_cache;
   private static $connected;
   private static $error;
   private static $error_msg;
   
   public function __construct()
   {
      if( !isset(self::$memcache) )
      {
         if( class_exists('Memcache') )
         {
            self::$memcache = new Memcache();
            if( @self::$memcache->connect(FS_CACHE_HOST, FS_CACHE_PORT) )
            {
               self::$connected = TRUE;
               self::$error = FALSE;
               self::$error_msg = '';
            }
            else
            {
               self::$connected = FALSE;
               self::$error = TRUE;
               self::$error_msg = 'Error al conectar al servidor Memcache.';
            }
         }
         else
         {
            self::$memcache = NULL;
            self::$connected = FALSE;
            self::$error = TRUE;
            self::$error_msg = 'Clase Memcache no encontrada. Debes
               <a target="_blank" href="//www.facturascripts.com/comm3/index.php?page=community_item&id=553">
               instalar Memcache</a> y activarlo en el php.ini';
         }
      }
      
      self::$php_file_cache = new php_file_cache();
   }
   
   public function error()
   {
      return self::$error;
   }
   
   public function error_msg()
   {
      return self::$error_msg;
   }
   
   public function close()
   {
      if( isset(self::$memcache) AND self::$connected )
      {
         self::$memcache->close();
      }
   }
   
   public function set($key, $object, $expire=5400)
   {
      if(self::$connected)
      {
         self::$memcache->set(FS_CACHE_PREFIX.$key, $object, FALSE, $expire);
      }
      else
      {
         self::$php_file_cache->put($key, $object);
      }
   }
   
   public function get($key)
   {
      if(self::$connected)
      {
         return self::$memcache->get(FS_CACHE_PREFIX.$key);
      }
      else
      {
         return self::$php_file_cache->get($key);
      }
   }
   
   /**
    * Devuelve un array almacenado en cache
    * @param type $key
    * @return type
    */
   public function get_array($key)
   {
      $aa = array();
      
      if(self::$connected)
      {
         $a = self::$memcache->get(FS_CACHE_PREFIX.$key);
         if($a)
         {
            $aa = $a;
         }
      }
      else
      {
         $a = self::$php_file_cache->get($key);
         if($a)
         {
            $aa = $a;
         }
      }
      
      return $aa;
   }
   
   /**
    * Devuelve un array almacenado en cache, tal y como get_array(), pero con la direfencia
    * de que si no se encuentra en cache, se pone $error a true.
    * @param type $key
    * @param type $error
    * @return type
    */
   public function get_array2($key, &$error)
   {
      $aa = array();
      $error = TRUE;
      
      if(self::$connected)
      {
         $a = self::$memcache->get(FS_CACHE_PREFIX.$key);
         if( is_array($a) )
         {
            $aa = $a;
            $error = FALSE;
         }
      }
      else
      {
         $a = self::$php_file_cache->get($key);
         if( is_array($a) )
         {
            $aa = $a;
            $error = FALSE;
         }
      }
      
      return $aa;
   }
   
   public function delete($key)
   {
      if(self::$connected)
      {
         return self::$memcache->delete(FS_CACHE_PREFIX.$key);
      }
      else
      {
         return self::$php_file_cache->delete($key);
      }
   }
   
   public function delete_multi($keys)
   {
      if(self::$connected)
      {
         foreach($keys as $i => $value)
         {
            $keys[$i] = FS_CACHE_PREFIX.$value;
         }
         
         return self::$memcache->deleteMulti($keys);
      }
      else
      {
         foreach($keys as $i => $value)
         {
            return self::$php_file_cache->delete($value);
         }
      }
   }
   
   public function clean()
   {
      if(self::$connected)
      {
         return self::$memcache->flush();
      }
      else
      {
         return self::$php_file_cache->flush();
      }
   }
   
   public function version()
   {
      if(self::$connected)
      {
         return 'Memcache '.self::$memcache->getVersion();
      }
      else
      {
         return 'Files';
      }
   }
   
   public function connected()
   {
      return self::$connected;
   }
}
