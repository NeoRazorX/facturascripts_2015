<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Redondeo bancario
 * @staticvar real $dFuzz
 * @param float $dVal
 * @param integer $iDec
 * @return float
 */
function bround($dVal, $iDec = 2)
{
    // banker's style rounding or round-half-even
    // (round down when even number is left of 5, otherwise round up)
    // $dVal is value to round
    // $iDec specifies number of decimal places to retain
    static $dFuzz = 0.00001; // to deal with floating-point precision loss

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
 * Muestra un mensaje de error en caso de error fatal, aunque php tenga
 * desactivados los errores.
 */
function fatal_handler()
{
    $error = error_get_last();
    if (isset($error) && in_array($error["type"], [1, 64])) {
        echo "<h1>Error fatal</h1>"
        . "<ul>"
        . "<li><b>Tipo:</b> " . $error["type"] . "</li>"
        . "<li><b>Archivo:</b> " . $error["file"] . "</li>"
        . "<li><b>Línea:</b> " . $error["line"] . "</li>"
        . "<li><b>Mensaje:</b> " . $error["message"] . "</li>"
        . "</ul>";
    }
}

/**
 * Función alternativa para cuando el followlocation falla.
 * @param resource $ch
 * @param integer $redirects
 * @param boolean $curlopt_header
 * @return string
 */
function fs_curl_redirect_exec($ch, &$redirects, $curlopt_header = false)
{
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
    }

    list(, $body) = explode("\r\n\r\n", $data, 2);
    curl_close($ch);
    return $body;
}

/**
 * Descarga el archivo de la url especificada
 * @param string $url
 * @param string $filename
 * @param integer $timeout
 * @return boolean
 */
function fs_file_download($url, $filename, $timeout = 30)
{
    $ok = FALSE;

    try {
        $data = fs_file_get_contents($url, $timeout);
        if ($data && $data != 'ERROR' && file_put_contents($filename, $data) !== FALSE) {
            $ok = TRUE;
        }
    } catch (Exception $e) {
        /// nada
    }

    return $ok;
}

/**
 * Descarga el contenido con curl o file_get_contents.
 * @param string $url
 * @param integer $timeout
 * @return string
 */
function fs_file_get_contents($url, $timeout = 10)
{
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (ini_get('open_basedir') === NULL) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        /**
         * En algunas configuraciones de php es necesario desactivar estos flags,
         * en otras es necesario activarlos. habrá que buscar una solución mejor.
         */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if (defined('FS_PROXY_TYPE')) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, FS_PROXY_TYPE);
            curl_setopt($ch, CURLOPT_PROXY, FS_PROXY_HOST);
            curl_setopt($ch, CURLOPT_PROXYPORT, FS_PROXY_PORT);
        }
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            curl_close($ch);
            return $data;
        } else if ($info['http_code'] == 301 || $info['http_code'] == 302) {
            $redirs = 0;
            return fs_curl_redirect_exec($ch, $redirs);
        }

        /// guardamos en el log
        if (class_exists('fs_core_log') && $info['http_code'] != 404) {
            $error = curl_error($ch);
            if ($error == '') {
                $error = 'ERROR ' . $info['http_code'];
            }

            $core_log = new fs_core_log();
            $core_log->new_error($error);
            $core_log->save($error);
        }

        curl_close($ch);
        return 'ERROR';
    }

    return file_get_contents($url);
}

/**
 * Devuelve el equivalente a $_POST[$name], pero pudiendo definicar un valor
 * por defecto si no encuentra nada.
 * @param string $name
 * @param mixed $default
 * @return mixed
 */
function fs_filter_input_post($name, $default = false)
{
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}

/**
 * Devuelve el equivalente a $_REQUEST[$name], pero pudiendo definicar un valor
 * por defecto si no encuentra nada.
 * @param string $name
 * @param mixed $default
 * @return mixed
 */
function fs_filter_input_req($name, $default = false)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

/**
 * Deshace las conversiones realizadas por fs_model::no_html()
 * @param string $txt
 * @return string
 */
function fs_fix_html($txt)
{
    $original = array('&lt;', '&gt;', '&quot;', '&#39;');
    $final = array('<', '>', "'", "'");
    return trim(str_replace($original, $final, $txt));
}

/**
 * Devuelve el tamaño máximo de archivo que soporta el servidor para las subidas por formulario.
 * @return int
 */
function fs_get_max_file_upload()
{
    $max = intval(ini_get('post_max_size'));
    if (intval(ini_get('upload_max_filesize')) < $max) {
        $max = intval(ini_get('upload_max_filesize'));
    }

    return $max;
}

/**
 * Establece el límite de tiempo de ejecución de PHP, si puede.
 * @param int $limit
 */
function fs_set_time_limit($limit)
{
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    if (!in_array('set_time_limit', $disabledFunctions)) {
        @set_time_limit($limit);
    }
}

/**
 * Devuelve el nombre de la clase del objeto, pero sin el namespace.
 * @param mixed $object
 * @return string
 */
function get_class_name($object = NULL)
{
    $name = get_class($object);
    $pos = strrpos($name, '\\');
    if ($pos !== FALSE) {
        $name = substr($name, $pos + 1);
    }

    return $name;
}

/**
 * Carga todos los modelos disponibles en los pugins activados y el núcleo.
 */
function require_all_models()
{
    if (!isset($GLOBALS['models'])) {
        $GLOBALS['models'] = array();
    }

    foreach ($GLOBALS['plugins'] as $plugin) {
        if (file_exists('plugins/' . $plugin . '/model')) {
            foreach (scandir('plugins/' . $plugin . '/model') as $file_name) {
                if ($file_name != '.' && $file_name != '..' && substr($file_name, -4) == '.php' && !in_array($file_name, $GLOBALS['models'])) {
                    require_once 'plugins/' . $plugin . '/model/' . $file_name;
                    $GLOBALS['models'][] = $file_name;
                }
            }
        }
    }

    /// ahora cargamos los del núcleo
    foreach (scandir('model') as $file_name) {
        if ($file_name != '.' && $file_name != '..' && substr($file_name, -4) == '.php' && !in_array($file_name, $GLOBALS['models'])) {
            require_once 'model/' . $file_name;
            $GLOBALS['models'][] = $file_name;
        }
    }
}

/**
 * Función obsoleta para cargar un modelo concreto.
 * @deprecated since version 2017.025
 * @param string $name
 */
function require_model($name)
{
    if (FS_DB_HISTORY) {
        $core_log = new fs_core_log();
        $core_log->new_error("require_model('" . $name . "') es innecesario desde FacturaScripts 2017.025.");
    }
}
