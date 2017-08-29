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

require_once 'base/fs_cache.php';
require_once 'base/fs_functions.php';
require_once 'base/fs_core_log.php';

/**
 * Controlador del actualizador de FacturaScripts.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_updater
{

    /**
     *
     * @var boolean
     */
    public $btn_fin;

    /**
     *
     * @var fs_core_log
     */
    public $core_log;

    /**
     *
     * @var boolean
     */
    public $errores;

    /**
     *
     * @var array
     */
    public $plugins;

    /**
     *
     * @var string
     */
    public $tr_options;

    /**
     *
     * @var string
     */
    public $tr_updates;

    /**
     *
     * @var array
     */
    public $updates;

    /**
     *
     * @var string
     */
    public $version;

    /**
     *
     * @var string
     */
    public $xid;

    /**
     *
     * @var fs_cache
     */
    private $cache;

    /**
     *
     * @var array
     */
    private $download_list2;

    /**
     *
     * @var array
     */
    private $plugin_updates;
    private $uptime;

    public function __construct()
    {
        $tiempo = explode(' ', microtime());
        $this->uptime = $tiempo[1] + $tiempo[0];

        $this->btn_fin = FALSE;
        $this->cache = new fs_cache();
        $this->core_log = new fs_core_log();
        $this->plugins = array();
        $this->tr_options = '';
        $this->tr_updates = '';
        $this->version = '';
        $this->xid();

        if (filter_input(INPUT_COOKIE, 'user') && filter_input(INPUT_COOKIE, 'logkey')) {
            /// solamente comprobamos si no hay que hacer nada
            if (!filter_input(INPUT_GET, 'update') && !filter_input(INPUT_GET, 'reinstall') && !filter_input(INPUT_GET, 'plugin') && !filter_input(INPUT_GET, 'idplugin')) {
                /// ¿Están todos los permisos correctos?
                foreach ($this->__are_writable($this->__get_all_sub_directories('.')) as $dir) {
                    $this->core_log->new_error('No se puede escribir sobre el directorio ' . $dir);
                }

                /// ¿Sigue estando disponible ziparchive?
                if (!extension_loaded('zip')) {
                    $this->core_log->new_error('No se encuentra la clase ZipArchive, debes instalar php-zip.');
                }
            }

            if (count($this->core_log->get_errors()) > 0) {
                $this->core_log->new_error('Tienes que corregir estos errores antes de continuar.');
            } else if (filter_input(INPUT_GET, 'update') || filter_input(INPUT_GET, 'reinstall')) {
                $this->actualizar_nucleo();
            } else if (filter_input(INPUT_GET, 'plugin')) {
                $this->actualizar_plugin(filter_input(INPUT_GET, 'plugin'));
            } else if (filter_input(INPUT_GET, 'idplugin') && filter_input(INPUT_GET, 'name') && filter_input(INPUT_GET, 'key')) {
                $this->actualizar_plugin_pago(filter_input(INPUT_GET, 'idplugin'), filter_input(INPUT_GET, 'name'), filter_input(INPUT_GET, 'key'));
            } else if (filter_input(INPUT_GET, 'idplugin') && filter_input(INPUT_GET, 'name') && filter_input(INPUT_POST, 'key')) {
                $this->guardar_key();
            }

            if (count($this->core_log->get_errors()) == 0) {
                $this->comprobar_actualizaciones();
            } else {
                $this->tr_updates = '<tr class="warning"><td colspan="5">Aplazada la comprobación'
                    . ' de plugins hasta que resuelvas los problemas.</td></tr>';
            }
        } else {
            $this->core_log->new_error('<a href="index.php">Debes iniciar sesi&oacute;n</a>');
        }
    }

    private function comprobar_actualizaciones()
    {
        if (!isset($this->updates)) {
            /// comprobamos la lista de actualizaciones de cache
            $this->updates = $this->cache->get('updater_lista');
            if ($this->updates) {
                $this->plugin_updates = $this->updates['plugins'];
            } else {
                /// si no está en cache, nos toca comprobar todo
                $this->updates = array(
                    'version' => '',
                    'core' => FALSE,
                    'plugins' => array(),
                );

                $version_actual = file_get_contents('VERSION');
                $this->updates['version'] = $version_actual;
                $nueva_version = @fs_file_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/VERSION');
                if (floatval($version_actual) < floatval($nueva_version)) {
                    $this->updates['core'] = $nueva_version;
                } else {
                    /// comprobamos los plugins
                    foreach ($this->check_for_plugin_updates() as $plugin) {
                        $this->updates['plugins'][] = $plugin;
                    }
                }

                /// guardamos la lista de actualizaciones en cache
                if (count($this->updates['plugins']) > 0) {
                    $this->cache->set('updater_lista', $this->updates);
                }
            }
        }

        $this->version = $this->updates['version'];

        if ($this->updates['core']) {
            $this->tr_updates = '<tr>'
                . '<td><b>Núcleo</b></td>'
                . '<td>Núcleo de FacturaScripts.</td>'
                . '<td class="text-right">' . $this->version . '</td>'
                . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                . $this->updates['core'] . '" target="_blank">' . $this->updates['core'] . '</a></td>'
                . '<td class="text-right">
                    <a class="btn btn-sm btn-primary" href="updater.php?update=TRUE" role="button">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar
                    </a></td>'
                . '</tr>';
        } else {
            $this->tr_options = '<tr>'
                . '<td><b>Núcleo</b></td>'
                . '<td>Núcleo de FacturaScripts.</td>'
                . '<td class="text-right">' . $this->version . '</td>'
                . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                . $this->version . '" target="_blank">' . $this->version . '</a></td>'
                . '<td class="text-right">
                    <a class="btn btn-xs btn-default" href="updater.php?reinstall=TRUE" role="button">
                        <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>&nbsp; Reinstalar
                    </a></td>'
                . '</tr>';

            foreach ($this->updates['plugins'] as $plugin) {
                if ($plugin['depago']) {
                    if (!$this->xid) {
                        /// nada
                    } else if ($plugin['private_key']) {
                        $this->tr_updates .= '<tr>'
                            . '<td>' . $plugin['name'] . '</td>'
                            . '<td>' . $plugin['description'] . '</td>'
                            . '<td class="text-right">' . $plugin['version'] . '</td>'
                            . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                            . $plugin['new_version'] . '&plugin=' . $plugin['name'] . '" target="_blank">' . $plugin['new_version'] . '</a></td>'
                            . '<td class="text-center">'
                            . '<div class="btn-group">'
                            . '<a href="updater.php?idplugin=' . $plugin['idplugin'] . '&name=' . $plugin['name'] . '&key=' . $plugin['private_key']
                            . '" class="btn btn-block btn-xs btn-primary">'
                            . '<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar'
                            . '</a>'
                            . '<a href="#" data-toggle="modal" data-target="#modal_key_' . $plugin['name'] . '">'
                            . '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Cambiar la clave'
                            . '</a>'
                            . '</div>'
                            . '</td></tr>';
                    } else {
                        $this->tr_updates .= '<tr>'
                            . '<td>' . $plugin['name'] . '</td>'
                            . '<td>' . $plugin['description'] . '</td>'
                            . '<td class="text-right">' . $plugin['version'] . '</td>'
                            . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                            . $plugin['new_version'] . '&plugin=' . $plugin['name'] . '" target="_blank">' . $plugin['new_version'] . '</a></td>'
                            . '<td class="text-right">'
                            . '<div class="btn-group">'
                            . '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modal_key_' . $plugin['name'] . '">'
                            . '<i class="fa fa-key" aria-hidden="true"></i>&nbsp; Añadir clave'
                            . '</a>'
                            . '</div>'
                            . '</td></tr>';
                    }
                } else {
                    $this->tr_updates .= '<tr>'
                        . '<td>' . $plugin['name'] . '</td>'
                        . '<td>' . $plugin['description'] . '</td>'
                        . '<td class="text-right">' . $plugin['version'] . '</td>'
                        . '<td class="text-right"><a href="https://www.facturascripts.com/comm3/index.php?page=community_changelog&version='
                        . $plugin['new_version'] . '&plugin=' . $plugin['name'] . '" target="_blank">' . $plugin['new_version'] . '</a></td>'
                        . '<td class="text-right">'
                        . '<a href="updater.php?plugin=' . $plugin['name'] . '" class="btn btn-xs btn-primary">'
                        . '<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp; Actualizar'
                        . '</a>'
                        . '</td></tr>';
                }
            }

            if ($this->tr_updates == '') {
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
        if (!isset($this->updates)) {
            /// comprobamos la lista de actualizaciones de cache
            $this->updates = $this->cache->get('updater_lista');
        }

        if ($this->updates) {
            if ($plugin) {
                foreach ($this->updates['plugins'] as $i => $pl) {
                    if ($pl['name'] == $plugin) {
                        unset($this->updates['plugins'][$i]);
                        break;
                    }
                }
            } else {
                /// hemos actualizado el core
                $this->updates['core'] = FALSE;
            }

            /// guardamos la lista de actualizaciones en cache
            if (count($this->updates['plugins']) > 0) {
                $this->cache->set('updater_lista', $this->updates);
            }
        }
    }

    private function actualizar_nucleo()
    {
        $urls = array(
            'https://github.com/NeoRazorX/facturascripts_2015/archive/master.zip',
            'https://codeload.github.com/NeoRazorX/facturascripts_2015/zip/master'
        );

        foreach ($urls as $url) {
            if (@fs_file_download($url, 'update-core.zip')) {
                $zip = new ZipArchive();
                $zip_status = $zip->open('update-core.zip', ZipArchive::CHECKCONS);

                if ($zip_status !== TRUE) {
                    $this->core_log->new_error('Ha habido un error con el archivo update-core.zip. Código: ' . $zip_status
                        . '. Intente de nuevo en unos minutos.');
                } else {
                    $zip->extractTo('.');
                    $zip->close();

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

                    $this->core_log->new_message('Actualizado correctamente.');
                    $this->actualizacion_correcta();
                    break;
                }
            } else {
                $this->core_log->new_error('Error al descargar el archivo update-core.zip. Intente de nuevo en unos minutos.');
            }
        }
    }

    private function actualizar_plugin($plugin_name)
    {
        /// leemos el ini del plugin
        $plugin_ini = parse_ini_file('plugins/' . $plugin_name . '/facturascripts.ini');
        if (!empty($plugin_ini)) {
            /// descargamos el zip
            if (@fs_file_download($plugin_ini['update_url'], 'update.zip')) {
                $zip = new ZipArchive();
                $zip_status = $zip->open('update.zip', ZipArchive::CHECKCONS);

                if ($zip_status !== TRUE) {
                    $this->core_log->new_error('Ha habido un error con el archivo update.zip. Código: ' . $zip_status
                        . '. Intente de nuevo en unos minutos.');
                } else {
                    /// nos guardamos la lista previa de plugins
                    $plugins_list = scandir(getcwd() . '/plugins');

                    /// eliminamos los archivos antiguos
                    $this->del_tree('plugins/' . $plugin_name);

                    /// descomprimimos
                    $zip->extractTo('plugins/');
                    $zip->close();
                    unlink('update.zip');

                    /// renombramos si es necesario
                    foreach (scandir(getcwd() . '/plugins') as $f) {
                        if ($f != '.' && $f != '..' && is_dir('plugins/' . $f)) {
                            $encontrado2 = FALSE;
                            foreach ($plugins_list as $f2) {
                                if ($f == $f2) {
                                    $encontrado2 = TRUE;
                                    break;
                                }
                            }

                            if (!$encontrado2) {
                                rename('plugins/' . $f, 'plugins/' . $plugin_name);
                                break;
                            }
                        }
                    }

                    $this->core_log->new_message('Plugin actualizado correctamente.');
                    $this->actualizacion_correcta($plugin_name);
                }
            } else {
                $this->core_log->new_error('Error al descargar el archivo update.zip. Intente de nuevo en unos minutos.');
            }
        } else {
            $this->core_log->new_error('Error al leer el archivo plugins/' . $plugin_name . '/facturascripts.ini');
        }
    }

    private function actualizar_plugin_pago($idplugin, $name, $key)
    {
        $url = 'https://www.facturascripts.com/comm3/index.php?page=community_edit_plugin&id=' .
            $idplugin . '&xid=' . $this->xid . '&key=' . $key;

        /// descargamos el zip
        if (@fs_file_download($url, 'update-pay.zip')) {
            $zip = new ZipArchive();
            $zip_status = $zip->open('update-pay.zip', ZipArchive::CHECKCONS);

            if ($zip_status !== TRUE) {
                $this->core_log->new_error('Ha habido un error con el archivo update-pay.zip. Código: ' . $zip_status
                    . '. Intente de nuevo en unos minutos.');
            } else {
                /// eliminamos los archivos antiguos
                $this->del_tree('plugins/' . $name);

                /// descomprimimos
                $zip->extractTo('plugins/');
                $zip->close();
                unlink('update-pay.zip');

                if (file_exists('plugins/' . $name . '-master')) {
                    /// renombramos el directorio
                    rename('plugins/' . $name . '-master', 'plugins/' . $name);
                }

                $this->core_log->new_message('Plugin actualizado correctamente.');
                $this->actualizacion_correcta($name);
            }
        } else {
            $this->core_log->new_error('Error al descargar el archivo update-pay.zip. <a href="updater.php?idplugin=' .
                $idplugin . '&name=' . $name . '">¿Clave incorrecta?</a>');
        }
    }

    private function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function del_tree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->del_tree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function __get_all_sub_directories($base_dir)
    {
        $directories = array();

        foreach (scandir($base_dir) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $dir = $base_dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($dir)) {
                $directories[] = $dir;
                $directories = array_merge($directories, $this->__get_all_sub_directories($dir));
            }
        }

        return $directories;
    }

    private function __are_writable($dirlist)
    {
        $notwritable = array();

        foreach ($dirlist as $dir) {
            if (!is_writable($dir)) {
                $notwritable[] = $dir;
            }
        }

        return $notwritable;
    }

    public function check_for_plugin_updates()
    {
        if (!isset($this->plugin_updates)) {
            $this->plugin_updates = array();
            foreach (scandir(getcwd() . '/plugins') as $f) {
                if ($f != '.' && $f != '..' && is_dir('plugins/' . $f)) {
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

                    if (file_exists('plugins/' . $f . '/facturascripts.ini')) {
                        if (file_exists('plugins/' . $f . '/description')) {
                            $plugin['description'] = file_get_contents('plugins/' . $f . '/description');
                        }

                        $ini_file = parse_ini_file('plugins/' . $f . '/facturascripts.ini');
                        if (isset($ini_file['version'])) {
                            $plugin['version'] = intval($ini_file['version']);
                        }

                        if (isset($ini_file['update_url'])) {
                            $plugin['update_url'] = $ini_file['update_url'];
                        }

                        if (isset($ini_file['version_url'])) {
                            $plugin['version_url'] = $ini_file['version_url'];
                        }

                        if (isset($ini_file['idplugin'])) {
                            $plugin['idplugin'] = $ini_file['idplugin'];
                        }

                        if ($plugin['version_url'] != '' && $plugin['update_url'] != '') {
                            /// plugin con descarga gratuita
                            $internet_ini = @parse_ini_string(@fs_file_get_contents($plugin['version_url']));
                            if ($internet_ini && $plugin['version'] < intval($internet_ini['version'])) {
                                $plugin['new_version'] = intval($internet_ini['version']);
                                $this->plugin_updates[] = $plugin;
                            }
                        } else if ($plugin['idplugin']) {
                            /// plugin de pago/oculto

                            foreach ($this->download_list2() as $ditem) {
                                if ($ditem->id == $plugin['idplugin']) {
                                    if (intval($ditem->version) > $plugin['version']) {
                                        $plugin['new_version'] = intval($ditem->version);
                                        $plugin['depago'] = TRUE;

                                        if (file_exists('tmp/' . FS_TMP_NAME . 'private_keys/' . $plugin['idplugin'])) {
                                            $plugin['private_key'] = trim(@file_get_contents('tmp/' . FS_TMP_NAME . 'private_keys/' . $plugin['idplugin']));
                                        } else if (!file_exists('tmp/' . FS_TMP_NAME . 'private_keys/') && mkdir('tmp/' . FS_TMP_NAME . 'private_keys/')) {
                                            file_put_contents('tmp/' . FS_TMP_NAME . 'private_keys/.htaccess', 'Deny from all');
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
        if (!isset($this->download_list2)) {
            $cache = new fs_cache();

            /**
             * Download_list2 es la lista de plugins de la comunidad, se descarga de Internet.
             */
            $this->download_list2 = $cache->get('download_list2');
            if (!$this->download_list2) {
                $json = @fs_file_get_contents('https://www.facturascripts.com/plugins?json2=TRUE', 10);
                if ($json && $json != 'ERROR') {
                    $this->download_list2 = json_decode($json);
                    $cache->set('download_list2', $this->download_list2);
                } else {
                    $this->download_list2 = array();
                }
            }
        }

        return $this->download_list2;
    }

    private function xid()
    {
        $this->xid = '';
        $data = $this->cache->get_array('empresa');
        if (!empty($data)) {
            $this->xid = $data[0]['xid'];
            if (!filter_input(INPUT_COOKIE, 'uxid')) {
                setcookie('uxid', $this->xid, time() + FS_COOKIES_EXPIRE);
            }
        } else if (filter_input(INPUT_COOKIE, 'uxid')) {
            $this->xid = filter_input(INPUT_COOKIE, 'uxid');
        }
    }

    private function guardar_key()
    {
        $private_key = filter_input(INPUT_POST, 'key');
        if (file_put_contents('tmp/' . FS_TMP_NAME . 'private_keys/' . filter_input(INPUT_GET, 'idplugin'), $private_key)) {
            $this->core_log->new_message('Clave añadida correctamente.');
            $this->cache->clean();
        } else {
            $this->core_log->new_error('Error al guardar la clave.');
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
