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

namespace FacturaScripts\model;

/**
 * El almacén donde están físicamente los artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class almacen extends \fs_model {

    /**
     * Clave primaria. Varchar (4).
     * @var string
     */
    public $codalmacen;
    public $nombre;
    public $codpais;
    public $provincia;
    public $poblacion;
    public $codpostal;
    public $direccion;
    public $contacto;
    public $fax;
    public $telefono;

    /**
     * Todavía sin uso.
     * @var string 
     */
    public $observaciones;

    public function __construct($a = FALSE) {
        parent::__construct('almacenes');
        if ($a) {
            $this->codalmacen = $a['codalmacen'];
            $this->nombre = $a['nombre'];
            $this->codpais = $a['codpais'];
            $this->provincia = $a['provincia'];
            $this->poblacion = $a['poblacion'];
            $this->codpostal = $a['codpostal'];
            $this->direccion = $a['direccion'];
            $this->contacto = $a['contacto'];
            $this->fax = $a['fax'];
            $this->telefono = $a['telefono'];
            $this->observaciones = $a['observaciones'];
        } else {
            $this->codalmacen = NULL;
            $this->nombre = '';
            $this->codpais = NULL;
            $this->provincia = NULL;
            $this->poblacion = NULL;
            $this->codpostal = '';
            $this->direccion = '';
            $this->contacto = '';
            $this->fax = '';
            $this->telefono = '';
            $this->observaciones = '';
        }
    }

    public function install() {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (codalmacen,nombre,poblacion,direccion,codpostal,telefono,fax,contacto)
         VALUES ('ALG','ALMACEN GENERAL','','','','','','');";
    }

    /**
     * Devuelve la URL para ver/modificar los datos de este almacén
     * @return string
     */
    public function url() {
        if (is_null($this->codalmacen)) {
            return 'index.php?page=admin_almacenes';
        } else
            return 'index.php?page=admin_almacenes#' . $this->codalmacen;
    }

    /**
     * Devuelve TRUE si este es almacén predeterminado de la empresa.
     * @return type
     */
    public function is_default() {
        return ( $this->codalmacen == $this->default_items->codalmacen() );
    }

    /**
     * Devuelve el almacén con codalmacen = $cod
     * @param string $cod
     * @return \almacen|boolean
     */
    public function get($cod) {
        $almacen = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codalmacen = " . $this->var2str($cod) . ";");
        if ($almacen) {
            return new \almacen($almacen[0]);
        } else
            return FALSE;
    }

    /**
     * Devuelve TRUE si el almacén existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codalmacen)) {
            return FALSE;
        } else
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";");
    }

    /**
     * Comprueba los datos del almacén, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;

        $this->codalmacen = trim($this->codalmacen);
        $this->nombre = $this->no_html($this->nombre);
        $this->provincia = $this->no_html($this->provincia);
        $this->poblacion = $this->no_html($this->poblacion);
        $this->direccion = $this->no_html($this->direccion);
        $this->codpostal = $this->no_html($this->codpostal);
        $this->telefono = $this->no_html($this->telefono);
        $this->fax = $this->no_html($this->fax);
        $this->contacto = $this->no_html($this->contacto);

        if (!preg_match("/^[A-Z0-9]{1,4}$/i", $this->codalmacen)) {
            $this->new_error_msg("Código de almacén no válido.");
        } else if (strlen($this->nombre) < 1 OR strlen($this->nombre) > 100) {
            $this->new_error_msg("Nombre de almacén no válido.");
        } else
            $status = TRUE;

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET nombre = " . $this->var2str($this->nombre)
                        . ", codpais = " . $this->var2str($this->codpais)
                        . ", provincia = " . $this->var2str($this->provincia)
                        . ", poblacion = " . $this->var2str($this->poblacion)
                        . ", direccion = " . $this->var2str($this->direccion)
                        . ", codpostal = " . $this->var2str($this->codpostal)
                        . ", telefono = " . $this->var2str($this->telefono)
                        . ", fax = " . $this->var2str($this->fax)
                        . ", contacto = " . $this->var2str($this->contacto)
                        . "  WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (codalmacen,nombre,codpais,provincia,
               poblacion,direccion,codpostal,telefono,fax,contacto) VALUES
                      (" . $this->var2str($this->codalmacen)
                        . "," . $this->var2str($this->nombre)
                        . "," . $this->var2str($this->codpais)
                        . "," . $this->var2str($this->provincia)
                        . "," . $this->var2str($this->poblacion)
                        . "," . $this->var2str($this->direccion)
                        . "," . $this->var2str($this->codpostal)
                        . "," . $this->var2str($this->telefono)
                        . "," . $this->var2str($this->fax)
                        . "," . $this->var2str($this->contacto) . ");";
            }
            return $this->db->exec($sql);
        } else
            return FALSE;
    }

    /**
     * Elimina el almacén
     * @return type
     */
    public function delete() {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE codalmacen = " . $this->var2str($this->codalmacen) . ";");
    }

    /**
     * Limpiamos la caché
     */
    private function clean_cache() {
        $this->cache->delete('m_almacen_all');
    }

    /**
     * Devuelve un array con todos los almacenes
     * @return \almacen
     */
    public function all() {
        /// leemos esta lista de la caché
        $listaa = $this->cache->get_array('m_almacen_all');
        if (empty($listaa)) {
            /// si no está en caché, leemos de la base de datos
            $data = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY codalmacen ASC;");
            if ($data) {
                foreach ($data as $a) {
                    $listaa[] = new \almacen($a);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_almacen_all', $listaa);
        }

        return $listaa;
    }

}
