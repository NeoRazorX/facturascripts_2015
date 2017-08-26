<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once __DIR__ . '/php_file_cache.php';

/**
 * Clase para concectar e interactuar con memcache.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_cache
{

    private static $memcache;
    
    /**
     *
     * @var php_file_cache
     */
    private static $php_file_cache;
    private static $connected;
    private static $error;
    private static $error_msg;

    public function __construct()
    {
        if (!isset(self::$memcache)) {
            if (class_exists('Memcache')) {
                self::$memcache = new Memcache();
                if (@self::$memcache->connect(FS_CACHE_HOST, FS_CACHE_PORT)) {
                    self::$connected = TRUE;
                    self::$error = FALSE;
                    self::$error_msg = '';
                } else {
                    self::$connected = FALSE;
                    self::$error = TRUE;
                    self::$error_msg = 'Error al conectar al servidor Memcache.';
                }
            } else {
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
        if (isset(self::$memcache) && self::$connected) {
            self::$memcache->close();
        }
    }

    public function set($key, $object, $expire = 5400)
    {
        if (self::$connected) {
            self::$memcache->set(FS_CACHE_PREFIX . $key, $object, FALSE, $expire);
        } else {
            self::$php_file_cache->put($key, $object);
        }
    }

    public function get($key)
    {
        if (self::$connected) {
            return self::$memcache->get(FS_CACHE_PREFIX . $key);
        }

        return self::$php_file_cache->get($key);
    }

    /**
     * Devuelve un array almacenado en cache
     * @param string $key
     * @return array
     */
    public function get_array($key)
    {
        $aa = array();

        if (self::$connected) {
            $a = self::$memcache->get(FS_CACHE_PREFIX . $key);
            if ($a) {
                $aa = $a;
            }
        } else {
            $a = self::$php_file_cache->get($key);
            if ($a) {
                $aa = $a;
            }
        }

        return $aa;
    }

    /**
     * Devuelve un array almacenado en cache, tal y como get_array(), pero con la direfencia
     * de que si no se encuentra en cache, se pone $error a true.
     * @param string $key
     * @param boolean $error
     * @return array
     */
    public function get_array2($key, &$error)
    {
        $aa = array();
        $error = TRUE;

        if (self::$connected) {
            $a = self::$memcache->get(FS_CACHE_PREFIX . $key);
            if (is_array($a)) {
                $aa = $a;
                $error = FALSE;
            }
        } else {
            $a = self::$php_file_cache->get($key);
            if (is_array($a)) {
                $aa = $a;
                $error = FALSE;
            }
        }

        return $aa;
    }

    public function delete($key)
    {
        if (self::$connected) {
            return self::$memcache->delete(FS_CACHE_PREFIX . $key);
        }

        return self::$php_file_cache->delete($key);
    }

    public function delete_multi($keys)
    {
        $done = FALSE;

        if (self::$connected) {
            foreach ($keys as $i => $value) {
                $done = self::$memcache->delete(FS_CACHE_PREFIX . $value);
            }
        } else {
            foreach ($keys as $i => $value) {
                $done = self::$php_file_cache->delete($value);
            }
        }

        return $done;
    }

    public function clean()
    {
        if (self::$connected) {
            return self::$memcache->flush();
        }

        return self::$php_file_cache->flush();
    }

    public function version()
    {
        if (self::$connected) {
            return 'Memcache ' . self::$memcache->getVersion();
        }

        return 'Files';
    }

    public function connected()
    {
        return self::$connected;
    }
}
