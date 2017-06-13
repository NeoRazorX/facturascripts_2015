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

/**
 * Panel de control de FacturaScripts.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_home extends fs_controller {

    public $disable_mod_plugins;
    public $disable_add_plugins;
    public $disable_rm_plugins;
    public $download_list;
    public $download_list2;
    public $paginas;
    public $step;
    private $fs_var;

    public function __construct() {
        parent::__construct(__CLASS__, 'Panel de control', 'admin');
    }

    protected function private_core() {
        $this->fs_var = new fs_var();

        $this->check_htaccess();
        $this->chech_config();
        $this->get_download_list();

        if (filter_input(INPUT_GET, 'check4updates')) {
            $this->template = '';
            if ($this->check_for_updates2()) {
                echo 'Hay actualizaciones disponibles.';
            } else {
                echo 'No hay actualizaciones.';
            }
        } else if (filter_input(INPUT_GET, 'updated')) {
            /// el sistema ya se ha actualizado
            $this->fs_var->simple_delete('updates');
            $this->activar_comprobacion_columnas();
            $this->clean_cache();
        } else if (FS_DEMO) {
            $this->new_advice('En el modo demo no se pueden hacer cambios en esta página.');
            $this->new_advice('Si te gusta FacturaScripts y quieres saber más, consulta la '
                    . '<a href="https://www.facturascripts.com/comm3/index.php?page=community_questions">sección preguntas</a>.');
        } else if (!$this->user->admin) {
            $this->new_error_msg('Sólo un administrador puede hacer cambios en esta página.');
        } else if (filter_input(INPUT_GET, 'skip')) {
            if ($this->step == '1') {
                $this->step = '2';
                $this->fs_var->simple_save('install_step', $this->step);
            }
        } else if (filter_input(INPUT_POST, 'modpages')) {
            /// activar/desactivas páginas del menú
            $this->enable_pages();
        } else if (filter_input(INPUT_GET, 'enable')) {
            /// activar plugin
            $this->enable_plugin(filter_input(INPUT_GET, 'enable'));
        } else if (filter_input(INPUT_GET, 'disable')) {
            /// desactivar plugin
            $this->disable_plugin(filter_input(INPUT_GET, 'disable'));
        } else if (filter_input(INPUT_GET, 'delete_plugin')) {
            /// eliminar plugin
            $this->delete_plugin(filter_input(INPUT_GET, 'delete_plugin'));
        } else if (filter_input(INPUT_POST, 'install')) {
            /// instalar plugin (copiarlo y descomprimirlo)
            $this->install_plugin();
        } else if (filter_input(INPUT_GET, 'download')) {
            if ($this->disable_mod_plugins) {
                $this->new_error_msg('No tienes permiso para descargar plugins.');
            } else {
                /// descargamos un plugin de la lista fija
                $this->download1();
            }
        } else if (filter_input(INPUT_GET, 'download2')) {
            if ($this->disable_mod_plugins) {
                $this->new_error_msg('No tienes permiso para descargar plugins.');
            } else {
                /// descargamos un plugin de la lista de la comunidad
                $this->download2();
            }
        } else if (filter_input(INPUT_GET, 'reset')) {
            /// reseteamos la configuración avanzada
            if (file_exists('tmp/' . FS_TMP_NAME . 'config2.ini')) {
                unlink('tmp/' . FS_TMP_NAME . 'config2.ini');
            }

            $this->new_message('Configuración reiniciada correctamente, pulsa <a href="' . $this->url() . '#avanzado">aquí</a> para continuar.', TRUE);
        } else {
            /// ¿Guardamos las opciones de la pestaña avanzado?
            $this->save_avanzado();
        }

        $this->paginas = $this->all_pages();
        $this->load_menu(TRUE);
    }

    private function chech_config() {
        $this->disable_mod_plugins = FALSE;
        $this->disable_add_plugins = FALSE;
        $this->disable_rm_plugins = FALSE;
        if (defined('FS_DISABLE_MOD_PLUGINS')) {
            $this->disable_mod_plugins = FS_DISABLE_MOD_PLUGINS;
            $this->disable_add_plugins = FS_DISABLE_MOD_PLUGINS;
            $this->disable_rm_plugins = FS_DISABLE_MOD_PLUGINS;
        }

        if (!$this->disable_mod_plugins) {
            if (defined('FS_DISABLE_ADD_PLUGINS')) {
                $this->disable_add_plugins = FS_DISABLE_ADD_PLUGINS;
            }

            if (defined('FS_DISABLE_RM_PLUGINS')) {
                $this->disable_rm_plugins = FS_DISABLE_RM_PLUGINS;
            }
        }
    }

    private function enable_pages() {
        if (!$this->step) {
            $this->step = '1';
            $this->fs_var->simple_save('install_step', $this->step);
        }

        $enabled = filter_input(INPUT_POST, 'enabled', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        foreach ($this->all_pages() as $p) {
            if (!$p->exists) { /// la página está en la base de datos pero ya no existe el controlador
                if ($p->delete()) {
                    $this->new_message('Se ha eliminado automáticamente la página ' . $p->name .
                            ' ya que no tiene un controlador asociado en la carpeta controller.');
                }
            } else if (!$enabled) { /// ninguna página marcada
                $this->disable_page($p);
            } else if (!$p->enabled AND in_array($p->name, $enabled)) { /// página no activa marcada para activar
                $this->enable_page($p);
            } else if ($p->enabled AND ! in_array($p->name, $enabled)) { /// págine activa no marcada (desactivar)
                $this->disable_page($p);
            }
        }

        $this->new_message('Datos guardados correctamente.');
    }

    private function save_avanzado() {
        $guardar = FALSE;
        foreach ($GLOBALS['config2'] as $i => $value) {
            if (filter_input(INPUT_POST, $i)) {
                $GLOBALS['config2'][$i] = filter_input(INPUT_POST, $i);
                $guardar = TRUE;
            }
        }

        if ($guardar) {
            $file = fopen('tmp/' . FS_TMP_NAME . 'config2.ini', 'w');
            if ($file) {
                foreach ($GLOBALS['config2'] as $i => $value) {
                    if (is_numeric($value)) {
                        fwrite($file, $i . " = " . $value . ";\n");
                    } else {
                        fwrite($file, $i . " = '" . $value . "';\n");
                    }
                }

                fclose($file);
            }

            $this->new_message('Datos guardados correctamente.');
        }
    }

    /**
     * Devuelve las páginas/controladore de los plugins activos.
     * @return \fs_page
     */
    private function all_pages() {
        $pages = array();
        $page_names = array();

        /// añadimos las páginas de los plugins
        foreach ($this->plugins() as $plugin) {
            if (file_exists(getcwd() . '/plugins/' . $plugin . '/controller')) {
                foreach (scandir(getcwd() . '/plugins/' . $plugin . '/controller') as $f) {
                    if (substr($f, -4) == '.php') {
                        $p = new fs_page();
                        $p->name = substr($f, 0, -4);
                        $p->exists = TRUE;
                        $p->show_on_menu = FALSE;

                        if (!in_array($p->name, $page_names)) {
                            $pages[] = $p;
                            $page_names[] = $p->name;
                        }
                    }
                }
            }
        }

        /// añadimos las páginas que están en el directorio controller
        foreach (scandir(getcwd() . '/controller') as $f) {
            if (substr($f, -4) == '.php') {
                $p = new fs_page();
                $p->name = substr($f, 0, -4);
                $p->exists = TRUE;
                $p->show_on_menu = FALSE;

                if (!in_array($p->name, $page_names)) {
                    $pages[] = $p;
                    $page_names[] = $p->name;
                }
            }
        }

        /// completamos los datos de las páginas con los datos de la base de datos
        foreach ($this->page->all() as $p) {
            $encontrada = FALSE;
            foreach ($pages as $i => $value) {
                if ($p->name == $value->name) {
                    $pages[$i] = $p;
                    $pages[$i]->enabled = TRUE;
                    $pages[$i]->exists = TRUE;
                    $encontrada = TRUE;
                    break;
                }
            }
            if (!$encontrada) {
                $p->enabled = TRUE;
                $pages[] = $p;
            }
        }

        /// ordenamos
        usort($pages, function($a, $b) {
            if ($a->name == $b->name) {
                return 0;
            } else if ($a->name > $b->name) {
                return 1;
            } else
                return -1;
        });

        return $pages;
    }

    /**
     * Devuelve la lista de plugins instalados y activados
     * @return array
     */
    private function plugins() {
        return $GLOBALS['plugins'];
    }

    /**
     * Activa una página/controlador.
     * @param fs_page $page
     */
    private function enable_page($page) {
        /// primero buscamos en los plugins
        $found = FALSE;
        foreach ($this->plugins() as $plugin) {
            if (file_exists('plugins/' . $plugin . '/controller/' . $page->name . '.php')) {
                require_once 'plugins/' . $plugin . '/controller/' . $page->name . '.php';
                $new_fsc = new $page->name();
                $found = TRUE;

                if (isset($new_fsc->page)) {
                    if (!$new_fsc->page->save()) {
                        $this->new_error_msg("Imposible guardar la página " . $page->name);
                    }
                } else {
                    $this->new_error_msg("Error al leer la página " . $page->name);
                }

                unset($new_fsc);
                break;
            }
        }

        if (!$found) {
            require_once 'controller/' . $page->name . '.php';
            $new_fsc = new $page->name(); /// cargamos el controlador asociado

            if (!$new_fsc->page->save()) {
                $this->new_error_msg("Imposible guardar la página " . $page->name);
            }

            unset($new_fsc);
        }
    }

    /**
     * Desactiva una página/controlador.
     * @param fs_page $page
     */
    private function disable_page($page) {
        if ($page->name == $this->page->name) {
            $this->new_error_msg("No puedes desactivar esta página (" . $page->name . ").");
        } else if (!$page->delete()) {
            $this->new_error_msg('Imposible eliminar la página ' . $page->name . '.');
        }
    }

    /**
     * Devuelve la lista de elementos a traducir
     * @return array
     */
    public function traducciones() {
        $clist = array();
        $include = array(
            'factura', 'facturas', 'factura_simplificada', 'factura_rectificativa',
            'albaran', 'albaranes', 'pedido', 'pedidos', 'presupuesto', 'presupuestos',
            'provincia', 'apartado', 'cifnif', 'iva', 'irpf', 'numero2', 'serie', 'series'
        );

        foreach ($GLOBALS['config2'] as $i => $value) {
            if (in_array($i, $include)) {
                $clist[] = array('nombre' => $i, 'valor' => $value);
            }
        }

        return $clist;
    }

    /**
     * Timezones list with GMT offset
     * 
     * @return array
     * @link http://stackoverflow.com/a/9328760
     */
    public function get_timezone_list() {
        $zones_array = array();

        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }

        return $zones_array;
    }

    /**
     * Lista de opciones para NF0
     * @return integer[]
     */
    public function nf0() {
        return array(0, 1, 2, 3, 4, 5);
    }

    /**
     * Lista de opciones para NF1
     * @return array
     */
    public function nf1() {
        return array(
            ',' => 'coma',
            '.' => 'punto',
            ' ' => '(espacio en blanco)'
        );
    }

    /**
     * Devuelve la lista completada de plugins instalados
     * @return array
     */
    public function plugin_advanced_list() {
        $plugins = array();
        $disabled = array();

        if (defined('FS_DISABLED_PLUGINS')) {
            foreach (explode(',', FS_DISABLED_PLUGINS) as $aux) {
                $disabled[] = $aux;
            }
        }

        foreach (scandir(getcwd() . '/plugins') as $f) {
            if ($f != '.' AND $f != '..' AND is_dir('plugins/' . $f) AND ! in_array($f, $disabled)) {
                $plugin = array(
                    'compatible' => FALSE,
                    'description' => 'Sin descripción.',
                    'download2_url' => '',
                    'enabled' => FALSE,
                    'idplugin' => NULL,
                    'name' => $f,
                    'prioridad' => '-',
                    'require' => array(),
                    'update_url' => '',
                    'version' => 0,
                    'version_url' => '',
                    'wizard' => FALSE,
                );

                if (file_exists('plugins/' . $f . '/facturascripts.ini')) {
                    $plugin['compatible'] = TRUE;
                    $plugin['enabled'] = in_array($f, $this->plugins());

                    if (file_exists('plugins/' . $f . '/description')) {
                        $plugin['description'] = file_get_contents('plugins/' . $f . '/description');
                    }

                    $ini_file = parse_ini_file('plugins/' . $f . '/facturascripts.ini');
                    if (isset($ini_file['version'])) {
                        $plugin['version'] = intval($ini_file['version']);
                    }

                    if (isset($ini_file['require'])) {
                        if ($ini_file['require'] != '') {
                            foreach (explode(',', $ini_file['require']) as $aux) {
                                $plugin['require'][] = $aux;
                            }
                        }
                    }

                    if (isset($ini_file['idplugin'])) {
                        $plugin['idplugin'] = $ini_file['idplugin'];
                    }

                    if (isset($ini_file['update_url'])) {
                        $plugin['update_url'] = $ini_file['update_url'];
                    }

                    if (isset($ini_file['version_url'])) {
                        $plugin['version_url'] = $ini_file['version_url'];
                    } else if ($this->download_list2) {
                        foreach ($this->download_list2 as $ditem) {
                            if ($ditem->id == $plugin['idplugin']) {
                                if (intval($ditem->version) > $plugin['version']) {
                                    $plugin['download2_url'] = 'updater.php?idplugin=' . $plugin['idplugin'] . '&name=' . $f;
                                }
                                break;
                            }
                        }
                    }

                    if (isset($ini_file['wizard'])) {
                        $plugin['wizard'] = $ini_file['wizard'];
                    }

                    if ($plugin['enabled']) {
                        foreach (array_reverse($this->plugins()) as $i => $value) {
                            if ($value == $f) {
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

    /**
     * Elimina recursivamente un directorio
     * @param string $dir
     * @return boolean
     */
    private function del_tree($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->del_tree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function install_plugin() {
        if ($this->disable_add_plugins) {
            $this->new_error_msg('La subida de plugins está desactivada.');
        } else if (is_uploaded_file($_FILES['fplugin']['tmp_name'])) {
            $zip = new ZipArchive();
            $res = $zip->open($_FILES['fplugin']['tmp_name'], ZipArchive::CHECKCONS);
            if ($res === TRUE) {
                $zip->extractTo('plugins/');
                $zip->close();
                $this->new_message('Plugin ' . $_FILES['fplugin']['name'] . ' añadido correctamente. Ya puedes activarlo.');

                $this->clean_cache();
            } else
                $this->new_error_msg('Error al abrir el archivo ZIP. Código: ' . $res);
        } else {
            $this->new_error_msg('Archivo no encontrado. ¿Pesa más de '
                    . $this->get_max_file_upload() . ' MB? Ese es el límite que tienes'
                    . ' configurado en tu servidor.');
        }
    }

    /**
     * Activa un plugin
     * @param string $name
     */
    private function enable_plugin($name) {
        if (strpos($name, '-master') !== FALSE) {
            /// renombramos el directorio
            $name2 = substr($name, 0, strpos($name, '-master'));
            if (rename('plugins/' . $name, 'plugins/' . $name2)) {
                $name = $name2;
            } else {
                $this->new_error_msg('Error al renombrar el plugin.');
            }
        }

        /// comprobamos las dependencias
        $install = TRUE;
        $wizard = FALSE;
        foreach ($this->plugin_advanced_list() as $pitem) {
            if ($pitem['name'] == $name) {
                $wizard = $pitem['wizard'];

                foreach ($pitem['require'] as $req) {
                    if (!in_array($req, $GLOBALS['plugins'])) {
                        $install = FALSE;
                        $txt = 'Dependencias incumplidas: <b>' . $req . '</b>';

                        foreach ($this->download_list2 as $value) {
                            if ($value->nombre == $req) {
                                $txt .= '. Puedes descargar este plugin desde la <b>pestaña descargas</b>.';
                                break;
                            }
                        }

                        $this->new_error_msg($txt);
                    }
                }
                break;
            }
        }

        if ($install AND ! in_array($name, $GLOBALS['plugins'])) {
            array_unshift($GLOBALS['plugins'], $name);

            if (file_put_contents('tmp/' . FS_TMP_NAME . 'enabled_plugins.list', join(',', $GLOBALS['plugins'])) !== FALSE) {
                if ($wizard) {
                    $this->new_advice('Ya puedes <a href="index.php?page=' . $wizard . '">configurar el plugin</a>.');
                    header('Location: index.php?page=' . $wizard);
                } else {
                    /// cargamos el archivo functions.php
                    if (file_exists('plugins/' . $name . '/functions.php')) {
                        require_once 'plugins/' . $name . '/functions.php';
                    }

                    if (file_exists(getcwd() . '/plugins/' . $name . '/controller')) {
                        /// activamos las páginas del plugin
                        $page_list = array();
                        foreach (scandir(getcwd() . '/plugins/' . $name . '/controller') as $f) {
                            if ($f != '.' AND $f != '..' AND is_string($f) AND strlen($f) > 4 AND ! is_dir($f)) {
                                if (substr($f, -4) == '.php') {
                                    $page_name = substr($f, 0, -4);
                                    $page_list[] = $page_name;

                                    require_once 'plugins/' . $name . '/controller/' . $f;
                                    $new_fsc = new $page_name();

                                    if (!$new_fsc->page->save()) {
                                        $this->new_error_msg("Imposible guardar la página " . $page_name);
                                    }

                                    unset($new_fsc);
                                }
                            }
                        }

                        $this->new_message('Se han activado automáticamente las siguientes páginas: ' . join(', ', $page_list) . '.');
                    }

                    $this->new_message('Plugin <b>' . $name . '</b> activado correctamente.');
                    $this->load_menu(TRUE);
                }

                $this->clean_cache();
            } else
                $this->new_error_msg('Imposible activar el plugin <b>' . $name . '</b>.');
        }

        if ($this->step == '1') {
            $this->step = '2';
            $this->fs_var->simple_save('install_step', $this->step);
        }
    }

    /**
     * Desactiva un plugin
     * @param string $name
     */
    private function disable_plugin($name) {
        if (file_exists('tmp/' . FS_TMP_NAME . 'enabled_plugins.list')) {
            if (in_array($name, $this->plugins())) {
                if (count($GLOBALS['plugins']) == 1 AND $GLOBALS['plugins'][0] == $name) {
                    $GLOBALS['plugins'] = array();
                    unlink('tmp/' . FS_TMP_NAME . 'enabled_plugins.list');

                    $this->new_message('Plugin <b>' . $name . '</b> desactivado correctamente.');
                } else {
                    foreach ($GLOBALS['plugins'] as $i => $value) {
                        if ($value == $name) {
                            unset($GLOBALS['plugins'][$i]);
                            break;
                        }
                    }

                    if (file_put_contents('tmp/' . FS_TMP_NAME . 'enabled_plugins.list', join(',', $GLOBALS['plugins'])) !== FALSE) {
                        $this->new_message('Plugin <b>' . $name . '</b> desactivado correctamente.');
                    } else
                        $this->new_error_msg('Imposible desactivar el plugin <b>' . $name . '</b>.');
                }
            }

            /*
             * Desactivamos las páginas que ya no existen
             */
            $eliminadas = array();
            foreach ($this->page->all() as $p) {
                $encontrada = FALSE;

                if (file_exists(getcwd() . '/controller/' . $p->name . '.php')) {
                    $encontrada = TRUE;
                } else {
                    foreach ($GLOBALS['plugins'] as $plugin) {
                        if (file_exists(getcwd() . '/plugins/' . $plugin . '/controller/' . $p->name . '.php') AND $name != $plugin) {
                            $encontrada = TRUE;
                            break;
                        }
                    }
                }

                if (!$encontrada) {
                    if ($p->delete()) {
                        $eliminadas[] = $p->name;
                    }
                }
            }
            if ($eliminadas) {
                $this->new_message('Se han eliminado automáticamente las siguientes páginas: ' . join(', ', $eliminadas));
            }

            /// desactivamos los plugins que dependan de este
            foreach ($this->plugin_advanced_list() as $plug) {
                /// ¿El plugin está activo?
                if (in_array($plug['name'], $GLOBALS['plugins'])) {
                    /**
                     * Si el plugin que hemos desactivado, es requerido por el plugin
                     * que estamos comprobando, lo desativamos también.
                     */
                    if (in_array($name, $plug['require'])) {
                        $this->disable_plugin($plug['name']);
                    }
                }
            }

            /// borramos los archivos temporales del motor de plantillas
            foreach (scandir(getcwd() . '/tmp/' . FS_TMP_NAME) as $f) {
                if (substr($f, -4) == '.php') {
                    unlink('tmp/' . FS_TMP_NAME . $f);
                }
            }

            $this->clean_cache();
        }
    }

    /**
     * Elimina el plugin del directorio
     * @param string $name
     */
    private function delete_plugin($name) {
        if ($this->disable_rm_plugins) {
            $this->new_error_msg('No tienes permiso para eliminar plugins.');
        } else if (is_writable('plugins/' . $name)) {
            if ($this->del_tree('plugins/' . $name)) {
                $this->new_message('Plugin ' . $name . ' eliminado correctamente.', TRUE);
            } else {
                $this->new_error_msg('Imposible eliminar el plugin ' . $name);
            }
        } else {
            $this->new_error_msg('No tienes permisos de escritura sobre la carpeta plugins/' . $name);
        }
    }

    /**
     * Comprueba actualizaciones de los plugins y del núcleo.
     * @return boolean
     */
    public function check_for_updates2() {
        if (!$this->user->admin) {
            return FALSE;
        } else {
            /// comprobamos actualizaciones en los plugins
            $updates = FALSE;
            foreach ($this->plugin_advanced_list() as $plugin) {
                if ($plugin['version_url'] != '' AND $plugin['update_url'] != '') {
                    /// plugin con descarga gratuita
                    $internet_ini = @parse_ini_string(@fs_file_get_contents($plugin['version_url']));
                    if ($internet_ini) {
                        if ($plugin['version'] < intval($internet_ini['version'])) {
                            $updates = TRUE;
                            break;
                        }
                    }
                } else if ($plugin['idplugin']) {
                    /// plugin de pago/oculto

                    if ($plugin['download2_url'] != '') {
                        /// download2_url implica que hay actualización
                        $updates = TRUE;
                        break;
                    }
                }
            }

            if (!$updates) {
                /// comprobamos actualizaciones del núcleo
                $version = file_get_contents('VERSION');
                $internet_version = @fs_file_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/VERSION');
                if (floatval($version) < floatval($internet_version)) {
                    $updates = TRUE;
                }
            }

            if ($updates) {
                $this->fs_var->simple_save('updates', 'true');
                return TRUE;
            } else {
                $this->fs_var->name = 'updates';
                $this->fs_var->delete();
                return FALSE;
            }
        }
    }

    /**
     * Descarga un plugin de la lista de plugins fijos.
     */
    private function download1() {
        if (isset($this->download_list[filter_input(INPUT_GET, 'download')])) {
            $this->new_message('Descargando el plugin ' . filter_input(INPUT_GET, 'download'));

            if (@fs_file_download($this->download_list[filter_input(INPUT_GET, 'download')]['url'], 'download.zip')) {
                $zip = new ZipArchive();
                $res = $zip->open('download.zip', ZipArchive::CHECKCONS);
                if ($res === TRUE) {
                    $plugins_list = scandir(getcwd() . '/plugins');
                    $zip->extractTo('plugins/');
                    $zip->close();
                    unlink('download.zip');

                    /// renombramos si es necesario
                    foreach (scandir(getcwd() . '/plugins') as $f) {
                        if ($f != '.' AND $f != '..' AND is_dir('plugins/' . $f)) {
                            $encontrado2 = FALSE;
                            foreach ($plugins_list as $f2) {
                                if ($f == $f2) {
                                    $encontrado2 = TRUE;
                                    break;
                                }
                            }

                            if (!$encontrado2) {
                                rename('plugins/' . $f, 'plugins/' . filter_input(INPUT_GET, 'download'));
                                break;
                            }
                        }
                    }

                    $this->new_message('Plugin añadido correctamente.');
                    $this->enable_plugin(filter_input(INPUT_GET, 'download'));

                    if ($this->step == '1') {
                        $this->step = '2';
                        $this->fs_var->simple_save('install_step', $this->step);
                    }
                } else {
                    $this->new_error_msg('Error al abrir el ZIP. Código: ' . $res);
                }
            } else {
                $this->new_error_msg('Error al descargar. Tendrás que descargarlo manualmente desde '
                        . '<a href="' . $this->download_list[filter_input(INPUT_GET, 'download')]['url'] . '" target="_blank">aquí</a> '
                        . 'y añadirlo desde la pestaña <b>plugins</b>.');
            }
        } else {
            $this->new_error_msg('Descarga no encontrada.');
        }
    }

    /**
     * Descarga un plugin de la lista dinámica de la comunidad.
     */
    private function download2() {
        $encontrado = FALSE;
        foreach ($this->download_list2 as $item) {
            if ($item->id == intval(filter_input(INPUT_GET, 'download2'))) {
                $this->new_message('Descargando el plugin ' . $item->nombre);
                $encontrado = TRUE;

                if (@fs_file_download($item->zip_link, 'download.zip')) {
                    $zip = new ZipArchive();
                    $res = $zip->open('download.zip', ZipArchive::CHECKCONS);
                    if ($res === TRUE) {
                        $plugins_list = scandir(getcwd() . '/plugins');
                        $zip->extractTo('plugins/');
                        $zip->close();
                        unlink('download.zip');

                        /// renombramos si es necesario
                        foreach (scandir(getcwd() . '/plugins') as $f) {
                            if ($f != '.' AND $f != '..' AND is_dir('plugins/' . $f)) {
                                $encontrado2 = FALSE;
                                foreach ($plugins_list as $f2) {
                                    if ($f == $f2) {
                                        $encontrado2 = TRUE;
                                        break;
                                    }
                                }

                                if (!$encontrado2) {
                                    rename('plugins/' . $f, 'plugins/' . $item->nombre);
                                    break;
                                }
                            }
                        }

                        $this->new_message('Plugin añadido correctamente.');
                        $this->enable_plugin($item->nombre);
                    } else {
                        $this->new_error_msg('Error al abrir el ZIP. Código: ' . $res);
                    }
                }
                else {
                    $this->new_error_msg('Error al descargar. Tendrás que descargarlo manualmente desde '
                            . '<a href="' . $item->zip_link . '" target="_blank">aquí</a> y añadirlo desde la pestaña <b>plugins</b>.');
                }
                break;
            }
        }

        if (!$encontrado) {
            $this->new_error_msg('Descarga no encontrada.');
        }
    }

    private function get_download_list() {
        /**
         * Esta es la lista de plugins fijos, los imprescindibles.
         */
        $this->download_list = array(
            'facturacion_base' => array(
                'url' => 'https://github.com/NeoRazorX/facturacion_base/archive/master.zip',
                'url_repo' => 'https://github.com/NeoRazorX/facturacion_base',
                'description' => 'Permite la gestión básica de una empresa: gestión de ventas, de compras y contabilidad básica.'
            ),
            'argentina' => array(
                'url' => 'https://github.com/FacturaScripts/argentina/archive/master.zip',
                'url_repo' => 'https://github.com/FacturaScripts/argentina',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Argentina</b>.'
            ),
            'chile' => array(
                'url' => 'https://github.com/FacturaScripts/chile/archive/master.zip',
                'url_repo' => 'https://github.com/FacturaScripts/chile',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Chile</b>.'
            ),
            'colombia' => array(
                'url' => 'https://github.com/FacturaScripts/colombia/archive/master.zip',
                'url_repo' => 'https://github.com/FacturaScripts/colombia',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Colombia</b>.'
            ),
            'ecuador' => array(
                'url' => 'https://github.com/FacturaScripts/ecuador/archive/master.zip',
                'url_repo' => 'https://github.com/FacturaScripts/ecuador',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Ecuador</b>.'
            ),
            'panama' => array(
                'url' => 'https://github.com/NeoRazorX/panama/archive/master.zip',
                'url_repo' => 'https://github.com/NeoRazorX/panama',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Panamá</b>.'
            ),
            'peru' => array(
                'url' => 'https://github.com/NeoRazorX/peru/archive/master.zip',
                'url_repo' => 'https://github.com/NeoRazorX/peru',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Perú</b>.'
            ),
            'republica_dominicana' => array(
                'url' => 'https://github.com/joenilson/republica_dominicana/archive/master.zip',
                'url_repo' => 'https://github.com/joenilson/republica_dominicana',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>República Dominicana</b>.'
            ),
            'venezuela' => array(
                'url' => 'https://github.com/ConsultoresTecnologicos/FS-LocalizacionVenezuela/archive/master.zip',
                'url_repo' => 'https://github.com/ConsultoresTecnologicos/FS-LocalizacionVenezuela',
                'description' => 'Plugin de adaptación de FacturaScripts a <b>Venezuela</b>.'
            ),
        );
        $this->step = $this->fs_var->simple_get('install_step');

        /**
         * Download_list2 es la lista de plugins de la comunidad, se descarga de Internet.
         */
        $this->download_list2 = $this->cache->get('download_list');
        if (!$this->download_list2) {
            $json = @fs_file_get_contents('https://www.facturascripts.com/comm3/index.php?page=community_plugins&json=TRUE', 5);
            if ($json) {
                $this->download_list2 = json_decode($json);
                $this->cache->set('download_list', $this->download_list2);
            } else {
                $this->new_error_msg('Error al descargar la lista de plugins.');
                $this->download_list2 = array();
            }
        }
    }

    private function check_htaccess() {
        if (!file_exists('.htaccess')) {
            $txt = file_get_contents('htaccess-sample');
            file_put_contents('.htaccess', $txt);
        }

        /// ahora comprobamos el de tmp/XXXXX/private_keys
        if (file_exists('tmp/' . FS_TMP_NAME . 'private_keys')) {
            if (!file_exists('tmp/' . FS_TMP_NAME . 'private_keys/.htaccess')) {
                file_put_contents('tmp/' . FS_TMP_NAME . 'private_keys/.htaccess', 'Deny from all');
            }
        }
    }

    private function clean_cache() {
        $this->cache->clean();

        /// borramos los archivos temporales del motor de plantillas
        foreach (scandir(getcwd() . '/tmp/' . FS_TMP_NAME) as $f) {
            if (substr($f, -4) == '.php') {
                unlink('tmp/' . FS_TMP_NAME . $f);
            }
        }
    }

    /**
     * Activamos/desactivamos aleatoriamente la comprobación de tipos de las columnas
     * de las tablas. ¿Por qué? Porque la comprobación es lenta y no merece la pena hacerla
     * siempre, pero tras las actualizaciones puede haber cambios en las columnas de las tablas.
     */
    private function activar_comprobacion_columnas() {
        $GLOBALS['config2']['check_db_types'] = mt_rand(0, 1);

        $file = fopen('tmp/' . FS_TMP_NAME . 'config2.ini', 'w');
        if ($file) {
            foreach ($GLOBALS['config2'] as $i => $value) {
                if (is_numeric($value)) {
                    fwrite($file, $i . " = " . $value . ";\n");
                } else {
                    fwrite($file, $i . " = '" . $value . "';\n");
                }
            }

            fclose($file);
        }
    }

}
