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
 * Elemento del menú de FacturaScripts, cada uno se corresponde con un controlador.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_page extends fs_model
{

    /**
     * Clave primaria. Varchar (30).
     * Nombre de la página (controlador).
     * @var string 
     */
    public $name;
    public $title;

    /**
     * Nombre del menú donde queremos colocar el acceso.
     * @var string 
     */
    public $folder;
    public $version;

    /**
     * FALSE -> ocultar en el menú.
     * @var boolean
     */
    public $show_on_menu;
    public $exists;
    public $enabled;
    public $extra_url;

    /**
     * Cuando un usuario no tiene asignada una página por defecto, se selecciona
     * la primera página importante a la que tiene acceso.
     */
    public $important;
    public $orden;

    public function __construct($data = FALSE)
    {
        parent::__construct('fs_pages');
        if ($data) {
            $this->name = $data['name'];
            $this->title = $data['title'];
            $this->folder = $data['folder'];

            $this->version = NULL;
            if (isset($data['version'])) {
                $this->version = $data['version'];
            }

            $this->show_on_menu = $this->str2bool($data['show_on_menu']);
            $this->important = $this->str2bool($data['important']);

            $this->orden = 100;
            if (isset($data['orden'])) {
                $this->orden = $this->intval($data['orden']);
            }
        } else {
            $this->name = NULL;
            $this->title = NULL;
            $this->folder = NULL;
            $this->version = NULL;
            $this->show_on_menu = TRUE;
            $this->important = FALSE;
            $this->orden = 100;
        }

        $this->exists = FALSE;
        $this->enabled = FALSE;
        $this->extra_url = '';
    }

    public function __clone()
    {
        $page = new fs_page();
        $page->name = $this->name;
        $page->title = $this->title;
        $page->folder = $this->folder;
        $page->version = $this->version;
        $page->show_on_menu = $this->show_on_menu;
        $page->important = $this->important;
        $page->orden = $this->orden;
    }

    protected function install()
    {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (name,title,folder,version,show_on_menu)
         VALUES ('admin_home','panel de control','admin',NULL,TRUE);";
    }

    public function url()
    {
        if (is_null($this->name)) {
            return 'index.php?page=admin_home';
        }

        return 'index.php?page=' . $this->name . $this->extra_url;
    }

    public function is_default()
    {
        return ( $this->name == $this->default_items->default_page() );
    }

    public function showing()
    {
        return ( $this->name == $this->default_items->showing_page() );
    }

    public function exists()
    {
        if (is_null($this->name)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name) . ";");
    }

    public function get($name)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($name) . ";");
        if ($data) {
            return new fs_page($data[0]);
        }

        return FALSE;
    }

    public function save()
    {
        $this->clean_cache();

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET title = " . $this->var2str($this->title)
                . ", folder = " . $this->var2str($this->folder)
                . ", version = " . $this->var2str($this->version)
                . ", show_on_menu = " . $this->var2str($this->show_on_menu)
                . ", important = " . $this->var2str($this->important)
                . ", orden = " . $this->var2str($this->orden)
                . "  WHERE name = " . $this->var2str($this->name) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name,title,folder,version,show_on_menu,important,orden) VALUES "
                . "(" . $this->var2str($this->name)
                . "," . $this->var2str($this->title)
                . "," . $this->var2str($this->folder)
                . "," . $this->var2str($this->version)
                . "," . $this->var2str($this->show_on_menu)
                . "," . $this->var2str($this->important)
                . "," . $this->var2str($this->orden) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name) . ";");
    }

    private function clean_cache()
    {
        $this->cache->delete('m_fs_page_all');
    }

    /**
     * Devuelve todas las páginas o entradas del menú
     * @return \fs_page
     */
    public function all()
    {
        /// comprobamos en la caché
        $pagelist = $this->cache->get_array('m_fs_page_all');

        /// si no está en la caché, comprobamos en la base de datos
        if (empty($pagelist)) {
            $pages = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY lower(folder) ASC, orden ASC, lower(title) ASC;");
            if ($pages) {
                foreach ($pages as $p) {
                    $pagelist[] = new fs_page($p);
                }
            }

            /// guardamos en la caché
            $this->cache->set('m_fs_page_all', $pagelist);
        }

        return $pagelist;
    }
}
