<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <neorazorx at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
 * Define un paquete de permisos para asignar rápidamente a usuarios.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class fs_rol extends fs_model
{

    public $codrol;
    public $descripcion;

    public function __construct($t = FALSE)
    {
        parent::__construct('fs_roles');
        if ($t) {
            $this->codrol = $t['codrol'];
            $this->descripcion = $t['descripcion'];
        } else {
            $this->codrol = NULL;
            $this->descripcion = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    public function url()
    {
        if (is_null($this->codrol)) {
            return 'index.php?page=admin_rol';
        }

        return 'index.php?page=admin_rol&codrol=' . urlencode($this->codrol);
    }

    public function get($codrol)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codrol = " . $this->var2str($codrol) . ";");
        if ($data) {
            return new fs_rol($data[0]);
        }

        return FALSE;
    }

    /**
     * Devuelve la lista de accesos permitidos del rol.
     * @return type
     */
    public function get_accesses()
    {
        $access = new fs_rol_access();
        return $access->all_from_rol($this->codrol);
    }

    /**
     * Devuelve la lista de usuarios con este rol.
     * @return type
     */
    public function get_users()
    {
        $ru = new fs_rol_user();
        return $ru->all_from_rol($this->codrol);
    }

    public function exists()
    {
        if (is_null($this->codrol)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codrol = " . $this->var2str($this->codrol) . ";");
    }

    public function save()
    {
        $this->descripcion = $this->no_html($this->descripcion);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET descripcion = " . $this->var2str($this->descripcion)
                . " WHERE codrol = " . $this->var2str($this->codrol) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codrol,descripcion) VALUES "
                . "(" . $this->var2str($this->codrol)
                . "," . $this->var2str($this->descripcion) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        $sql = "DELETE FROM " . $this->table_name . " WHERE codrol = " . $this->var2str($this->codrol) . ";";
        return $this->db->exec($sql);
    }

    public function all()
    {
        $lista = array();

        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY descripcion ASC;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new fs_rol($d);
            }
        }

        return $lista;
    }

    public function all_for_user($nick)
    {
        $lista = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE codrol IN "
            . "(SELECT codrol FROM fs_roles_users WHERE fs_user = " . $this->var2str($nick) . ");";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new fs_rol($d);
            }
        }

        return $lista;
    }
}
