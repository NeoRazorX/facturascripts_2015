<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
 * Permite que un plugin añada elementos a una vista existente,
 * sin necesidad de un reemplazo completo.
 * Los tipos soportados depende de cada página, pero suelen ser:
 * head, css, button y tab.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_extension extends fs_model
{

    /**
     * Identificador de la extensión para poder buscarlo fácilemnte.
     * No es la clave primaria. La clave primaria es name+from.
     * @var string 
     */
    public $name;

    /**
     * Nombre de la página (controlador) que ofrece la extensión.
     * @var string 
     */
    public $from;

    /**
     * Nombre de la página (controlador) que recibe la extensión.
     * @var string 
     */
    public $to;

    /**
     * Tipo de extensión: head, css, button, tab...
     * @var string 
     */
    public $type;

    /**
     * Texto del botón, del tab...
     * @var string 
     */
    public $text;

    /**
     * Parámetros extra para la URL. Debes añadir el &
     * @var string 
     */
    public $params;

    public function __construct($data = FALSE)
    {
        parent::__construct('fs_extensions2');
        if ($data) {
            $this->name = $data['name'];
            $this->from = $data['page_from'];
            $this->to = $data['page_to'];
            $this->type = $data['type'];
            $this->text = $data['text'];
            $this->params = $data['params'];
        } else {
            $this->name = NULL;
            $this->from = NULL;
            $this->to = NULL;
            $this->type = NULL;
            $this->text = NULL;
            $this->params = NULL;
        }
    }

    public function get($name, $from)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($name)
            . " AND page_from = " . $this->var2str($from) . ";";

        $data = $this->db->select($sql);
        if ($data) {
            return new fs_extension($data[0]);
        }

        return FALSE;
    }

    public function exists()
    {
        if (is_null($this->name)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name)
                . " AND page_from = " . $this->var2str($this->from) . ";");
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET page_to = " . $this->var2str($this->to)
                . ", type = " . $this->var2str($this->type)
                . ", text = " . $this->var2str($this->text)
                . ", params = " . $this->var2str($this->params)
                . "  WHERE name = " . $this->var2str($this->name) . " AND page_from = " . $this->var2str($this->from) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name,page_from,page_to,type,text,params) VALUES
                   (" . $this->var2str($this->name)
                . "," . $this->var2str($this->from)
                . "," . $this->var2str($this->to)
                . "," . $this->var2str($this->type)
                . "," . $this->var2str($this->text)
                . "," . $this->var2str($this->params) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name)
                . " AND page_from = " . $this->var2str($this->from) . ";");
    }

    public function all_from($from)
    {
        return $this->all_from_sql("SELECT * FROM " . $this->table_name . " WHERE page_from = " . $this->var2str($from) . " ORDER BY name ASC;");
    }

    public function all_to($to)
    {
        return $this->all_from_sql("SELECT * FROM " . $this->table_name . " WHERE page_to = " . $this->var2str($to) . " ORDER BY name ASC;");
    }

    public function all_4_type($tipo)
    {
        return $this->all_from_sql("SELECT * FROM " . $this->table_name . " WHERE type = " . $this->var2str($tipo) . " ORDER BY name ASC;");
    }

    public function all()
    {
        return $this->all_from_sql("SELECT * FROM " . $this->table_name . " ORDER BY name ASC;");
    }

    private function all_from_sql($sql)
    {
        $elist = [];

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $elist[] = new fs_extension($d);
            }
        }

        return $elist;
    }
}
