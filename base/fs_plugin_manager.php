<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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

require_once 'base/fs_file_manager.php';

/**
 * Description of fs_plugin_manager
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_plugin_manager
{

    private $cache;
    private $core_log;
    public $disable_mod_plugins = false;
    public $disable_add_plugins = false;
    public $disable_rm_plugins = false;
    private $download_list;
    public $version = 2017.900;

    public function __construct()
    {
        $this->cache = new fs_cache();
        $this->core_log = new fs_core_log();

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

        if (file_exists('VERSION')) {
            $this->version = (float) trim(file_get_contents(FS_FOLDER . '/VERSION'));
        }
    }

    public function disable($plugin_name)
    {
        if (!in_array($plugin_name, $this->enabled())) {
            return true;
        }

        foreach ($GLOBALS['plugins'] as $i => $value) {
            if ($value == $plugin_name) {
                unset($GLOBALS['plugins'][$i]);
                break;
            }
        }

        if ($this->save()) {
            $this->core_log->new_message('Plugin <b>' . $plugin_name . '</b> desactivado correctamente.');
        } else {
            $this->core_log->new_error('Imposible desactivar el plugin <b>' . $plugin_name . '</b>.');
            return false;
        }

        /*
         * Desactivamos las páginas que ya no existen
         */
        $this->disable_unnused_pages();

        /// desactivamos los plugins que dependan de este
        foreach ($this->enabled() as $plug) {
            /**
             * Si el plugin que hemos desactivado, es requerido por el plugin
             * que estamos comprobando, lo desativamos también.
             */
            if (in_array($plug['name'], $GLOBALS['plugins']) && in_array($plugin_name, $plug['require'])) {
                $this->disable($plug['name']);
            }
        }

        $this->clean_cache();
        return true;
    }

    public function disabled()
    {
        $disabled = [];
        if (defined('FS_DISABLED_PLUGINS')) {
            foreach (explode(',', FS_DISABLED_PLUGINS) as $aux) {
                $disabled[] = $aux;
            }
        }

        return $disabled;
    }

    public function download($plugin_id)
    {
        if ($this->disable_mod_plugins) {
            $this->core_log->new_error('No tienes permiso para descargar plugins.');
            return false;
        }

        foreach ($this->downloads() as $item) {
            if ($item['id'] != (int) $plugin_id) {
                continue;
            }

            $this->core_log->new_message('Descargando el plugin ' . $item['nombre']);
            if (!@fs_file_download($item['zip_link'], FS_FOLDER . '/download.zip')) {
                $this->core_log->new_error('Error al descargar. Tendrás que descargarlo manualmente desde '
                    . '<a href="' . $item['zip_link'] . '" target="_blank">aquí</a> y añadirlo pulsando el botón <b>añadir</b>.');
                return false;
            }

            $zip = new ZipArchive();
            $res = $zip->open(FS_FOLDER . '/download.zip', ZipArchive::CHECKCONS);
            if ($res !== TRUE) {
                $this->core_log->new_error('Error al abrir el ZIP. Código: ' . $res);
                return false;
            }

            $plugins_list = fs_file_manager::scan_folder(FS_FOLDER . '/plugins');
            $zip->extractTo(FS_FOLDER . '/plugins/');
            $zip->close();
            unlink(FS_FOLDER . '/download.zip');

            /// renombramos si es necesario
            foreach (fs_file_manager::scan_folder(FS_FOLDER . '/plugins') as $f) {
                if (is_dir(FS_FOLDER . '/plugins/' . $f) && !in_array($f, $plugins_list)) {
                    rename(FS_FOLDER . '/plugins/' . $f, FS_FOLDER . '/plugins/' . $item['nombre']);
                    break;
                }
            }

            $this->core_log->new_message('Plugin añadido correctamente.');
            return $this->enable($item['nombre']);
        }

        $this->core_log->new_error('Descarga no encontrada.');
        return false;
    }

    public function downloads()
    {
        if (isset($this->download_list)) {
            return $this->download_list;
        }

        /// buscamos en la cache
        $this->download_list = $this->cache->get('download_list');
        if ($this->download_list) {
            return $this->download_list;
        }

        /// lista de plugins de la comunidad, se descarga de Internet.
        $json = @fs_file_get_contents('https://www.facturascripts.com/plugins?json=TRUE', 10);
        if ($json && $json != 'ERROR') {
            $this->download_list = json_decode($json, true);
            foreach ($this->download_list as $key => $value) {
                $this->download_list[$key]['instalado'] = file_exists(FS_FOLDER . '/plugins/' . $value['nombre']);
            }

            $this->cache->set('download_list', $this->download_list);
            return $this->download_list;
        }

        $this->core_log->new_error('Error al descargar la lista de plugins.');
        $this->download_list = [
            [
                'id' => 87,
                'nick' => "NeoRazorX",
                'creador' => "NeoRazorX",
                'nombre' => "facturacion_base",
                'tipo' => "gratis",
                'descripcion' => "Plugin con las funciones básicas de facturación, contabilidad e informes simples.",
                'link' => "https://github.com/NeoRazorX/facturacion_base",
                'zip_link' => "https://github.com/NeoRazorX/facturacion_base/archive/master.zip",
                'imagen' => "https://www.facturascripts.com/comm3/plugins/community3/view/img/laptop.png",
                'estable' => true,
                'version' => 140,
                'creado' => "14-07-2016",
                'ultima_modificacion' => "30-06-2018",
                'descargas' => 130611,
                'oferta_hasta' => null,
                'caducidad' => null,
                'licencia' => "LGPL",
                'youtube_id' => "",
                'demo_url' => "https://www.facturascripts.com/demos/e/demo1",
                'precio' => 0,
                'instalado' => file_exists(FS_FOLDER . '/plugins/facturacion_base')
            ]
        ];

        return $this->download_list;
    }

    public function enable($plugin_name)
    {
        if (in_array($plugin_name, $GLOBALS['plugins'])) {
            $this->core_log->new_message('Plugin <b>' . $plugin_name . '</b> ya activado.');
            return true;
        }

        $name = $this->rename_plugin($plugin_name);

        /// comprobamos las dependencias
        $install = TRUE;
        $wizard = FALSE;
        foreach ($this->installed() as $pitem) {
            if ($pitem['name'] != $name) {
                continue;
            }

            $wizard = $pitem['wizard'];
            foreach ($pitem['require'] as $req) {
                if (!in_array($req, $GLOBALS['plugins'])) {
                    $install = FALSE;
                    $txt = 'Dependencias incumplidas: <b>' . $req . '</b>';
                    foreach ($this->downloads() as $value) {
                        if ($value['nombre'] == $req && !$this->disable_add_plugins) {
                            $txt .= '. Puedes descargar este plugin desde la <b>pestaña descargas</b>.';
                            break;
                        }
                    }

                    $this->core_log->new_error($txt);
                }
            }
            break;
        }

        if (!$install) {
            $this->core_log->new_error('Imposible activar el plugin <b>' . $name . '</b>.');
            return false;
        }

        array_unshift($GLOBALS['plugins'], $name);
        if (!$this->save()) {
            $this->core_log->new_error('Imposible activar el plugin <b>' . $name . '</b>.');
            return false;
        }

        require_all_models();

        if ($wizard) {
            $this->core_log->new_advice('Ya puedes <a href="index.php?page=' . $wizard . '">configurar el plugin</a>.');
            header('Location: index.php?page=' . $wizard);
            $this->clean_cache();
            return true;
        }

        $this->enable_plugin_controllers($name);
        $this->core_log->new_message('Plugin <b>' . $name . '</b> activado correctamente.');
        $this->clean_cache();
        return true;
    }

    public function enabled()
    {
        return $GLOBALS['plugins'];
    }

    public function install($path, $name)
    {
        if ($this->disable_add_plugins) {
            $this->core_log->new_error('La subida de plugins está desactivada. Contacta con tu proveedor de hosting.');
            return;
        }

        $zip = new ZipArchive();
        $res = $zip->open($path, ZipArchive::CHECKCONS);
        if ($res === TRUE) {
            $zip->extractTo(FS_FOLDER . '/plugins/');
            $zip->close();

            $name = $this->rename_plugin(substr($name, 0, -4));
            $this->core_log->new_message('Plugin <b>' . $name . '</b> añadido correctamente. Ya puede activarlo.');
            $this->clean_cache();
        } else {
            $this->core_log->new_error('Error al abrir el archivo ZIP. Código: ' . $res);
        }
    }

    public function installed()
    {
        $plugins = [];
        $disabled = $this->disabled();

        foreach (fs_file_manager::scan_folder(FS_FOLDER . '/plugins') as $file_name) {
            if (!is_dir(FS_FOLDER . '/plugins/' . $file_name) || in_array($file_name, $disabled)) {
                continue;
            }

            $plugins[] = $this->get_plugin_data($file_name);
        }

        return $plugins;
    }

    public function remove($plugin_name)
    {
        if ($this->disable_rm_plugins) {
            $this->core_log->new_error('No tienes permiso para eliminar plugins.');
            return false;
        }

        if (!is_writable(FS_FOLDER . '/plugins/' . $plugin_name)) {
            $this->core_log->new_error('No tienes permisos de escritura sobre la carpeta plugins/' . $plugin_name);
            return false;
        }

        if (fs_file_manager::del_tree(FS_FOLDER . '/plugins/' . $plugin_name)) {
            $this->core_log->new_message('Plugin ' . $plugin_name . ' eliminado correctamente.');
            $this->clean_cache();
            return true;
        }

        $this->core_log->new_error('Imposible eliminar el plugin ' . $plugin_name);
        return false;
    }

    private function clean_cache()
    {
        $this->cache->clean();
        fs_file_manager::clear_raintpl_cache();
    }

    private function disable_unnused_pages()
    {
        $eliminadas = [];
        $page_model = new fs_page();
        foreach ($page_model->all() as $page) {
            if (file_exists(FS_FOLDER . '/controller/' . $page->name . '.php')) {
                continue;
            }

            $encontrada = FALSE;
            foreach ($this->enabled() as $plugin) {
                if (file_exists(FS_FOLDER . '/plugins/' . $plugin . '/controller/' . $page->name . '.php')) {
                    $encontrada = TRUE;
                    break;
                }
            }

            if (!$encontrada && $page->delete()) {
                $eliminadas[] = $page->name;
            }
        }

        if (!empty($eliminadas)) {
            $this->core_log->new_message('Se han eliminado automáticamente las siguientes páginas: ' . implode(', ', $eliminadas));
        }
    }

    private function enable_plugin_controllers($plugin_name)
    {
        /// cargamos el archivo functions.php
        if (file_exists(FS_FOLDER . '/plugins/' . $plugin_name . '/functions.php')) {
            require_once 'plugins/' . $plugin_name . '/functions.php';
        }

        /// buscamos controladores
        if (file_exists(FS_FOLDER . '/plugins/' . $plugin_name . '/controller')) {
            $page_list = [];
            foreach (fs_file_manager::scan_files(FS_FOLDER . '/plugins/' . $plugin_name . '/controller', 'php') as $f) {
                $page_name = substr($f, 0, -4);
                $page_list[] = $page_name;

                require_once 'plugins/' . $plugin_name . '/controller/' . $f;
                $new_fsc = new $page_name();

                if (!$new_fsc->page->save()) {
                    $this->core_log->new_error("Imposible guardar la página " . $page_name);
                }

                unset($new_fsc);
            }

            $this->core_log->new_message('Se han activado automáticamente las siguientes páginas: ' . implode(', ', $page_list) . '.');
        }
    }

    private function get_plugin_data($plugin_name)
    {
        $plugin = [
            'compatible' => FALSE,
            'description' => 'Sin descripción.',
            'download2_url' => '',
            'enabled' => FALSE,
            'error_msg' => 'Falta archivo facturascripts.ini',
            'idplugin' => NULL,
            'min_version' => 2017.000,
            'name' => $plugin_name,
            'prioridad' => '-',
            'require' => [],
            'update_url' => '',
            'version' => 1,
            'version_url' => '',
            'wizard' => FALSE,
        ];

        if (!file_exists(FS_FOLDER . '/plugins/' . $plugin_name . '/facturascripts.ini')) {
            return $plugin;
        }

        $ini_file = parse_ini_file(FS_FOLDER . '/plugins/' . $plugin_name . '/facturascripts.ini');
        foreach (['description', 'idplugin', 'min_version', 'update_url', 'version', 'version_url', 'wizard'] as $field) {
            if (isset($ini_file[$field])) {
                $plugin[$field] = $ini_file[$field];
            }
        }

        $plugin['enabled'] = in_array($plugin_name, $this->enabled());
        $plugin['version'] = (int) $plugin['version'];
        $plugin['min_version'] = (float) $plugin['min_version'];

        if ($this->version >= $plugin['min_version']) {
            $plugin['compatible'] = true;
        } else {
            $plugin['error_msg'] = 'Requiere FacturaScripts ' . $plugin['min_version'];
        }

        if (file_exists('plugins/' . $plugin_name . '/description')) {
            $plugin['description'] = file_get_contents('plugins/' . $plugin_name . '/description');
        }

        if (isset($ini_file['require']) && $ini_file['require'] != '') {
            $plugin['require'] = explode(',', $ini_file['require']);
        }

        if (!isset($ini_file['version_url']) && $this->downloads()) {
            foreach ($this->downloads() as $ditem) {
                if ($ditem['id'] != $plugin['idplugin']) {
                    continue;
                }

                if (intval($ditem['version']) > $plugin['version']) {
                    $plugin['download2_url'] = 'updater.php?idplugin=' . $plugin['idplugin'] . '&name=' . $plugin_name;
                }
                break;
            }
        }

        if ($plugin['enabled']) {
            foreach (array_reverse($this->enabled()) as $i => $value) {
                if ($value == $plugin_name) {
                    $plugin['prioridad'] = $i;
                    break;
                }
            }
        }

        return $plugin;
    }

    private function rename_plugin($name)
    {
        $new_name = $name;
        if (strpos($name, '-master') !== FALSE) {
            /// renombramos el directorio
            $new_name = substr($name, 0, strpos($name, '-master'));
            if (!rename(FS_FOLDER . '/plugins/' . $name, FS_FOLDER . '/plugins/' . $new_name)) {
                $this->core_log->new_error('Error al renombrar el plugin.');
            }
        }

        return $new_name;
    }

    private function save()
    {
        if (empty($GLOBALS['plugins'])) {
            return unlink(FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'enabled_plugins.list');
        }

        $string = implode(',', $GLOBALS['plugins']);
        if (false === file_put_contents(FS_FOLDER . '/tmp/' . FS_TMP_NAME . 'enabled_plugins.list', $string)) {
            return false;
        }

        return true;
    }
}
