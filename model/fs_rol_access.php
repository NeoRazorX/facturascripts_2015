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
 * Define los permisos individuales para cada página dentro de un rol de usuarios.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class fs_rol_access extends fs_model
{

    public $codrol;
    public $fs_page;
    public $allow_delete;

    public function __construct($t = FALSE)
    {
        parent::__construct('fs_roles_access');
        if ($t) {
            $this->codrol = $t['codrol'];
            $this->fs_page = $t['fs_page'];
            $this->allow_delete = $this->str2bool($t['allow_delete']);
        } else {
            $this->codrol = NULL;
            $this->fs_page = NULL;
            $this->allow_delete = FALSE;
        }
    }

    protected function install()
    {
        return '';
    }

    public function exists()
    {
        if (is_null($this->codrol)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name
                    . " WHERE codrol = " . $this->var2str($this->codrol)
                    . " AND fs_page = " . $this->var2str($this->fs_page) . ";");
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET allow_delete = " . $this->var2str($this->allow_delete)
                . " WHERE codrol = " . $this->var2str($this->codrol)
                . " AND fs_page = " . $this->var2str($this->fs_page) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codrol,fs_page,allow_delete) VALUES "
                . "(" . $this->var2str($this->codrol)
                . "," . $this->var2str($this->fs_page)
                . "," . $this->var2str($this->allow_delete) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM " . $this->table_name
                . " WHERE codrol = " . $this->var2str($this->codrol)
                . " AND fs_page = " . $this->var2str($this->fs_page) . ";");
    }

    public function all_from_rol($codrol)
    {
        $accesslist = array();

        $access = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codrol = " . $this->var2str($codrol) . ";");
        if ($access) {
            foreach ($access as $a) {
                $accesslist[] = new fs_rol_access($a);
            }
        }

        return $accesslist;
    }
}
