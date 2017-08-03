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
 * Una clase genérica para consultar o almacenar en la base de datos pares clave/valor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_var extends fs_model
{

    /**
     * Clave primaria. Varchar(35).
     * @var string 
     */
    public $name;

    /**
     * Valor almacenado. Text.
     * @var string 
     */
    public $varchar;

    public function __construct($data = FALSE)
    {
        parent::__construct('fs_vars');
        if ($data) {
            $this->name = $data['name'];
            $this->varchar = $data['varchar'];
        } else {
            $this->name = NULL;
            $this->varchar = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    public function exists()
    {
        if (is_null($this->name)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name) . ";");
    }

    public function save()
    {
        $comillas = '';
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $comillas = '`';
        }

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET "
                . $comillas . "varchar" . $comillas . " = " . $this->var2str($this->varchar)
                . " WHERE name = " . $this->var2str($this->name) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name," . $comillas . "varchar" . $comillas . ")
            VALUES (" . $this->var2str($this->name)
                . "," . $this->var2str($this->varchar) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name) . ";");
    }

    /**
     * Devuelve un array con todos los elementos de la tabla.
     * @return \fs_var
     */
    public function all()
    {
        $vlist = array();

        $data = $this->db->select("SELECT * FROM " . $this->table_name . ";");
        if ($data) {
            foreach ($data as $v) {
                $vlist[] = new fs_var($v);
            }
        }

        return $vlist;
    }

    /**
     * Devuelve el valor de una clave dada.
     * @param string $name
     * @return boolean
     */
    public function simple_get($name)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($name) . ";");
        if ($data) {
            return $data[0]['varchar'];
        }

        return FALSE;
    }

    /**
     * Almacena el par clave/valor proporcionado.
     * @param string $name
     * @param string $value
     * @return boolean
     */
    public function simple_save($name, $value)
    {
        $comillas = '';
        if (strtolower(FS_DB_TYPE) == 'mysql') {
            $comillas = '`';
        }

        if ($this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($name) . ";")) {
            $sql = "UPDATE " . $this->table_name . " SET " . $comillas . "varchar" . $comillas . " = " . $this->var2str($value) .
                " WHERE name = " . $this->var2str($name) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name," . $comillas . "varchar" . $comillas . ") VALUES
            (" . $this->var2str($name) . "," . $this->var2str($value) . ");";
        }

        return $this->db->exec($sql);
    }

    /**
     * Elimina de la base de datos la tupla con ese nombre.
     * @param string $name
     * @return boolean
     */
    public function simple_delete($name)
    {
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE name = " . $this->var2str($name) . ";");
    }

    /**
     * Rellena un array con los resultados de la base de datos para cada clave,
     * es decir, para el array('clave1' => false, 'clave2' => false) busca
     * en la tabla las claves clave1 y clave2 y asigna los valores almacenados
     * en la base de datos.
     * 
     * Sustituye los valores por FALSE si no los encentra en la base de datos,
     * a menos que pongas FALSE en el segundo parámetro.
     * 
     * @param array $array
     */
    public function array_get($array, $replace = TRUE)
    {
        /// obtenemos todos los resultados y seleccionamos los que necesitamos
        $data = $this->db->select("SELECT * FROM " . $this->table_name . ";");
        if ($data) {
            foreach ($array as $i => $value) {
                $encontrado = FALSE;
                foreach ($data as $d) {
                    if ($d['name'] == $i) {
                        $array[$i] = $d['varchar'];
                        $encontrado = TRUE;
                        break;
                    }
                }

                if ($replace AND ! $encontrado) {
                    $array[$i] = FALSE;
                }
            }
        }

        return $array;
    }

    /**
     * Guarda en la base de datos los pares clave, valor de un array simple.
     * ATENCIÓN: si el valor es FALSE, elimina la clave de la tabla.
     * 
     * @param array $array
     */
    public function array_save($array)
    {
        $done = TRUE;

        foreach ($array as $i => $value) {
            if ($value === FALSE) {
                if (!$this->simple_delete($i)) {
                    $done = FALSE;
                }
            } else {
                if (!$this->simple_save($i, $value)) {
                    $done = FALSE;
                }
            }
        }

        return $done;
    }
}
