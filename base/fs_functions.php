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

/**
 * Esta función sirve para cargar modelos, y sobre todo, para cargarlos
 * desde la carpeta plugins, así se puede personalizar aún más el comportamiento
 * de FacturaScripts.
 * 
 * No se producirá ningún error en caso de que el archivo no se encuentre.
 * @param string $name nombre del archivo que se desea cargar.
 */
function require_model($name) {
   if (!isset($GLOBALS['models'])) {
      $GLOBALS['models'] = array();
   }

   if (!in_array($name, $GLOBALS['models'])) {
      /// primero buscamos en los plugins
      $found = FALSE;
      foreach ($GLOBALS['plugins'] as $plugin) {
         if (file_exists('plugins/' . $plugin . '/model/' . $name)) {
            require_once 'plugins/' . $plugin . '/model/' . $name;
            $GLOBALS['models'][] = $name;
            $found = TRUE;
            break;
         }
      }

      if (!$found) {
         if (file_exists('model/' . $name)) {
            require_once 'model/' . $name;
            $GLOBALS['models'][] = $name;
         }
      }
   }
}

/**
 * Devuelve el nombre de la clase del objeto, pero sin el namespace.
 * @param type $object
 * @return type
 */
function get_class_name($object = NULL) {
   $name = get_class($object);

   $pos = strrpos($name, '\\');
   if ($pos !== FALSE) {
      $name = substr($name, $pos + 1);
   }

   return $name;
}

/**
 * Redondeo bancario
 * @staticvar real $dFuzz
 * @param type $dVal
 * @param type $iDec
 * @return type
 */
function bround($dVal, $iDec = 2) {
   // banker's style rounding or round-half-even
   // (round down when even number is left of 5, otherwise round up)
   // $dVal is value to round
   // $iDec specifies number of decimal places to retain
   static $dFuzz = 0.00001; // to deal with floating-point precision loss
   $iRoundup = 0; // amount to round up by

   $iSign = ($dVal != 0.0) ? intval($dVal / abs($dVal)) : 1;
   $dVal = abs($dVal);

   // get decimal digit in question and amount to right of it as a fraction
   $dWorking = $dVal * pow(10.0, $iDec + 1) - floor($dVal * pow(10.0, $iDec)) * 10.0;
   $iEvenOddDigit = floor($dVal * pow(10.0, $iDec)) - floor($dVal * pow(10.0, $iDec - 1)) * 10.0;

   if (abs($dWorking - 5.0) < $dFuzz) {
      $iRoundup = ($iEvenOddDigit & 1) ? 1 : 0;
   } else {
      $iRoundup = ($dWorking > 5.0) ? 1 : 0;
   }

   return $iSign * ((floor($dVal * pow(10.0, $iDec)) + $iRoundup) / pow(10.0, $iDec));
}

/**
 * Descarga el contenido con curl o file_get_contents.
 * @param type $url
 * @param type $timeout
 * @return type
 */
function fs_file_get_contents($url, $timeout = 10) {
   if (function_exists('curl_init')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      if (is_null(ini_get('open_basedir'))) {
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      }
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      if (defined('FS_PROXY_TYPE')) {
         curl_setopt($ch, CURLOPT_PROXYTYPE, FS_PROXY_TYPE);
         curl_setopt($ch, CURLOPT_PROXY, FS_PROXY_HOST);
         curl_setopt($ch, CURLOPT_PROXYPORT, FS_PROXY_PORT);
      }
      $data = curl_exec($ch);
      $info = curl_getinfo($ch);

      if ($info['http_code'] == 301 OR $info['http_code'] == 302) {
         $redirs = 0;
         return fs_curl_redirect_exec($ch, $redirs);
      } else {
         curl_close($ch);
         return $data;
      }
   } else
      return file_get_contents($url);
}

/**
 * Función alternativa para cuando el followlocation falla.
 * @param type $ch
 * @param type $redirects
 * @param type $curlopt_header
 * @return type
 */
function fs_curl_redirect_exec($ch, &$redirects, $curlopt_header = false) {
   curl_setopt($ch, CURLOPT_HEADER, true);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $data = curl_exec($ch);
   $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

   if ($http_code == 301 || $http_code == 302) {
      list($header) = explode("\r\n\r\n", $data, 2);
      $matches = array();
      preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
      $url = trim(str_replace($matches[1], "", $matches[0]));
      $url_parsed = parse_url($url);
      if (isset($url_parsed)) {
         curl_setopt($ch, CURLOPT_URL, $url);
         $redirects++;
         return fs_curl_redirect_exec($ch, $redirects, $curlopt_header);
      }
   }

   if ($curlopt_header) {
      curl_close($ch);
      return $data;
   } else {
      list(, $body) = explode("\r\n\r\n", $data, 2);
      curl_close($ch);
      return $body;
   }
}

function fs_file_download($url, $filename, $timeout = 30) {
   $ok = FALSE;

   try {
      $data = fs_file_get_contents($url, $timeout);
      if ($data) {
         if (file_put_contents($filename, $data) !== FALSE) {
            $ok = TRUE;
         }
      }
   } catch (Exception $e) {
      /// nada
   }

   return $ok;
}

function fs_fix_html($txt) {
   $a = array('&lt;', '&gt;', '&quot;', '&#39;');
   $b = array('<', '>', "'", "'");
   return trim(str_replace($a, $b, $txt));
}
