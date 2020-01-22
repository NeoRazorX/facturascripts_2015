<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2020 Carlos Garcia Gomez <neorazorx@gmail.com>
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
require_once 'base/fs_plugin_manager.php';
require_once 'base/fs_settings.php';

/**
 * Panel de control de FacturaScripts.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_home extends fs_controller
{

    /**
     *
     * @var \fs_var
     */
    private $fs_var;

    /**
     *
     * @var \fs_page[]
     */
    public $paginas;

    /**
     *
     * @var \fs_plugin_manager
     */
    public $plugin_manager;

    /**
     *
     * @var \fs_settings
     */
    public $settings;

    /**
     *
     * @var string
     */
    public $step;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Panel de control', 'admin');
    }

    /**
     * Comprueba actualizaciones de los plugins y del núcleo.
     *
     * @return boolean
     */
    public function check_for_updates2()
    {
        if (!$this->user->admin) {
            return FALSE;
        }

        /// comprobamos actualizaciones en los plugins
        $updates = FALSE;
        foreach ($this->plugin_manager->installed() as $plugin) {
            if ($plugin['version_url'] != '' && $plugin['update_url'] != '') {
                /// plugin con descarga gratuita
                $internet_ini = @parse_ini_string(@fs_file_get_contents($plugin['version_url']));
                if ($internet_ini && $plugin['version'] < intval($internet_ini['version'])) {
                    $updates = TRUE;
                    break;
                }
            } else if ($plugin['idplugin'] && $plugin['download2_url'] != '') {
                /// plugin de pago/oculto
                /// download2_url implica que hay actualización
                $updates = TRUE;
                break;
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
        }

        $this->fs_var->name = 'updates';
        $this->fs_var->delete();
        return FALSE;
    }

    public function plugin_advanced_list()
    {
        /**
         * Si se produce alguna llamada a esta función, desactivamos todos los plugins,
         * porque debe haber alguno que está desactualizado, y un problema al cargar
         * está página será muy difícil de resolver para un novato.
         */
        foreach ($this->plugin_manager->enabled() as $plug) {
            $this->plugin_manager->disable($plug);
        }

        return [];
    }

    protected function private_core()
    {
        $this->fs_var = new fs_var();
        $this->plugin_manager = new fs_plugin_manager();
        $this->settings = new fs_settings();
        $this->step = (string) $this->fs_var->simple_get('install_step');

        $this->exec_actions();

        $this->paginas = $this->all_pages();
        $this->load_menu(TRUE);
    }

    private function exec_actions()
    {
        if (filter_input(INPUT_GET, 'check4updates')) {
            $this->template = FALSE;
            if ($this->check_for_updates2()) {
                echo 'Hay actualizaciones disponibles.';
            } else {
                echo 'No hay actualizaciones.';
            }
            return;
        }

        if (filter_input(INPUT_GET, 'updated')) {
            /// el sistema ya se ha actualizado
            $this->fs_var->simple_delete('updates');
            $this->activar_comprobacion_columnas();
            $this->clean_cache();
            return;
        }

        if (FS_DEMO) {
            $this->new_advice('En el modo demo no se pueden hacer cambios en esta página.');
            $this->new_advice('Si te gusta FacturaScripts y quieres saber más, consulta la '
                . '<a href="https://facturascripts.com/doc/2">documentación</a>.');
            return;
        }

        if (!$this->user->admin) {
            $this->new_error_msg('Sólo un administrador puede hacer cambios en esta página.');
            return;
        }

        if (filter_input(INPUT_GET, 'skip')) {
            if ($this->step == '1') {
                $this->step = '2';
                $this->fs_var->simple_save('install_step', $this->step);
            }
            return;
        }

        if (filter_input(INPUT_POST, 'modpages')) {
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
            /// descargamos un plugin de la lista de la comunidad
            $this->download(filter_input(INPUT_GET, 'download'));
        } else if (filter_input(INPUT_GET, 'reset')) {
            /// reseteamos la configuración avanzada
            $this->settings->reset();
            $this->new_message('Configuración reiniciada correctamente, pulsa <a href="' . $this->url() . '#avanzado">aquí</a> para continuar.', TRUE);
            return;
        }

        fs_file_manager::check_htaccess();

        /// ¿Guardamos las opciones de la pestaña avanzado?
        $this->save_avanzado();
    }

    /**
     * Activamos/desactivamos aleatoriamente la comprobación de tipos de las columnas
     * de las tablas. ¿Por qué? Porque la comprobación es lenta y no merece la pena hacerla
     * siempre, pero tras las actualizaciones puede haber cambios en las columnas de las tablas.
     */
    private function activar_comprobacion_columnas()
    {
        $GLOBALS['config2']['check_db_types'] = mt_rand(0, 1);
        $this->settings->save();
    }

    /**
     * Devuelve las páginas/controladore de los plugins activos.
     *
     * @return \fs_page[]
     */
    private function all_pages()
    {
        $pages = [];
        $page_names = [];

        /// añadimos las páginas de los plugins
        foreach ($this->plugin_manager->enabled() as $plugin) {
            if (!file_exists(FS_FOLDER . '/plugins/' . $plugin . '/controller')) {
                continue;
            }

            foreach (fs_file_manager::scan_files(FS_FOLDER . '/plugins/' . $plugin . '/controller', 'php') as $file_name) {
                $p = new fs_page();
                $p->name = substr($file_name, 0, -4);
                $p->exists = TRUE;
                $p->show_on_menu = FALSE;
                if (!in_array($p->name, $page_names)) {
                    $pages[] = $p;
                    $page_names[] = $p->name;
                }
            }
        }

        /// añadimos las páginas que están en el directorio controller
        foreach (fs_file_manager::scan_files(FS_FOLDER . '/controller', 'php') as $file_name) {
            $p = new fs_page();
            $p->name = substr($file_name, 0, -4);
            $p->exists = TRUE;
            $p->show_on_menu = FALSE;
            if (!in_array($p->name, $page_names)) {
                $pages[] = $p;
                $page_names[] = $p->name;
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
            }

            return -1;
        });

        return $pages;
    }

    private function clean_cache()
    {
        $this->cache->clean();
        fs_file_manager::clear_raintpl_cache();
    }

    /**
     * Elimina el plugin del directorio.
     * 
     * @param string $name
     */
    private function delete_plugin($name)
    {
        $this->plugin_manager->remove($name);
    }

    /**
     * Desactiva una página/controlador.
     * 
     * @param fs_page $page
     */
    private function disable_page($page)
    {
        if ($page->name == $this->page->name) {
            $this->new_error_msg("No puedes desactivar esta página (" . $page->name . ").");
            return false;
        } else if (!$page->delete()) {
            $this->new_error_msg('Imposible eliminar la página ' . $page->name . '.');
            return false;
        }

        return true;
    }

    /**
     * Desactiva un plugin.
     *
     * @param string $name
     */
    private function disable_plugin($name)
    {
        $this->plugin_manager->disable($name);
    }

    /**
     * Descarga un plugin de la lista dinámica de la comunidad.
     */
    private function download($plugin_id)
    {
        $this->plugin_manager->download($plugin_id);
    }

    /**
     * Activa una página/controlador.
     * 
     * @param \fs_page $page
     */
    private function enable_page($page)
    {
        $class_name = find_controller($page->name);
        /// ¿No se ha encontrado el controlador?
        if ('base/fs_controller.php' === $class_name) {
            $this->new_error_msg('Controlador <b>' . $page->name . '</b> no encontrado.');
            return false;
        }

        require_once $class_name;
        $new_fsc = new $page->name();
        if (!isset($new_fsc->page)) {
            $this->new_error_msg("Error al leer la página " . $page->name);
            return false;
        } elseif (!$new_fsc->page->save()) {
            $this->new_error_msg("Imposible guardar la página " . $page->name);
            return false;
        }

        unset($new_fsc);
        return true;
    }

    private function enable_pages()
    {
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
            } else if (!$p->enabled && in_array($p->name, $enabled)) { /// página no activa marcada para activar
                $this->enable_page($p);
            } else if ($p->enabled && !in_array($p->name, $enabled)) { /// págine activa no marcada (desactivar)
                $this->disable_page($p);
            }
        }

        $this->new_message('Datos guardados correctamente.');
    }

    /**
     * Activa un plugin.
     *
     * @param string $name
     */
    private function enable_plugin($name)
    {
        if (!$this->plugin_manager->enable($name)) {
            return;
        }

        $this->load_menu(TRUE);

        if ($this->step == '1') {
            $this->step = '2';
            $this->fs_var->simple_save('install_step', $this->step);
        }
    }

    private function install_plugin()
    {
        if (is_uploaded_file($_FILES['fplugin']['tmp_name'])) {
            $this->plugin_manager->install($_FILES['fplugin']['tmp_name'], $_FILES['fplugin']['name']);
        } else {
            $this->new_error_msg('Archivo no encontrado. ¿Pesa más de '
                . $this->get_max_file_upload() . ' MB? Ese es el límite que tienes'
                . ' configurado en tu servidor.');
        }
    }

    private function save_avanzado()
    {
        $guardar = FALSE;
        foreach ($GLOBALS['config2'] as $i => $value) {
            if (filter_input(INPUT_POST, $i) !== NULL) {
                $GLOBALS['config2'][$i] = filter_input(INPUT_POST, $i);
                $guardar = TRUE;
            }
        }

        if (!$guardar) {
            return;
        }

        if ($this->settings->save()) {
            $this->new_message('Datos guardados correctamente.');
        } else {
            $this->new_message('Error al guardar los datos.');
        }
    }
}
