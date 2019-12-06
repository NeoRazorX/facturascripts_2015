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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'base/fs_model.php';

/**
 * Description of fs_extended_model
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class fs_extended_model extends fs_model
{

    /**
     *
     * @var array
     */
    private static $model_fields = [];

    abstract public function model_class_name();

    abstract public function primary_column();

    /**
     * 
     * @param string     $table_name
     * @param array|bool $data
     */
    public function __construct($table_name, $data = false)
    {
        parent::__construct($table_name);
        if (empty($data)) {
            $this->clear();
        } else {
            $this->load_from_data($data);
        }
    }

    /**
     * 
     */
    public function clear()
    {
        foreach (array_keys($this->get_model_fields()) as $field) {
            $this->{$field} = null;
        }
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        $sql = "DELETE FROM " . $this->table_name() . " WHERE " . $this->primary_column() . " = " . $this->var2str($this->primary_column_value());
        return (bool) $this->db->exec($sql);
    }

    /**
     * 
     * @return bool
     */
    public function exists()
    {
        if (is_null($this->primary_column_value())) {
            return FALSE;
        }

        $sql = "SELECT * FROM " . $this->table_name() . " WHERE " . $this->primary_column() . " = " . $this->var2str($this->primary_column_value());
        return (bool) $this->db->select($sql);
    }

    /**
     * 
     * @param string $code
     * @param string $col_name
     * 
     * @return fs_extended_model|boolean
     */
    public function get($code, $col_name = '')
    {
        $column = empty($col_name) ? $this->primary_column() : $col_name;
        $sql = "SELECT * FROM " . $this->table_name() . " WHERE " . $column . " = " . $this->var2str($code);
        $data = $this->db->select($sql);
        if (empty($data)) {
            return false;
        }

        $model_class = $this->model_class_name();
        return new $model_class($data[0]);
    }

    /**
     * 
     * @return array
     */
    public function get_model_fields()
    {
        $table_name = $this->table_name();
        if (!isset(self::$model_fields[$table_name])) {
            self::$model_fields[$table_name] = [];
            foreach ($this->db->get_columns($table_name) as $column) {
                self::$model_fields[$table_name][$column['name']] = $column['type'];
            }
        }

        return self::$model_fields[$table_name];
    }

    /**
     * 
     * @param string $code
     * 
     * @return bool
     */
    public function load_from_code($code)
    {
        $sql = "SELECT * FROM " . $this->table_name() . " WHERE " . $this->primary_column() . " = " . $this->var2str($code);
        $data = $this->db->select($sql);
        if (empty($data)) {
            return false;
        }

        $this->load_from_data($data[0]);
        return true;
    }

    /**
     * 
     * @param array $data
     */
    public function load_from_data($data)
    {
        $fields = $this->get_model_fields();
        foreach ($data as $key => $value) {
            if (!isset($fields[$key])) {
                $this->{$key} = $value;
                continue;
            }

            $field_type = $fields[$key];
            $type = (strpos($field_type, '(') === false) ? $field_type : substr($field_type, 0, strpos($field_type, '('));
            switch ($type) {
                case 'tinyint':
                case 'boolean':
                    $this->{$key} = $this->str2bool($value);
                    break;

                case 'integer':
                case 'int':
                    $this->{$key} = $this->intval($value);
                    break;

                case 'double':
                case 'double precision':
                case 'float':
                    $this->{$key} = empty($value) ? 0.00 : (float) $value;
                    break;

                case 'date':
                    $this->{$key} = empty($value) ? null : date('d-m-Y', strtotime($value));
                    break;

                default:
                    $this->{$key} = $value;
            }
        }
    }

    /**
     * 
     * @return mixed
     */
    public function primary_column_value()
    {
        return $this->{$this->primary_column()};
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            return $this->save_update();
        }

        return $this->save_insert();
    }

    /**
     * 
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        $edit_url = 'index.php?page=edit_' . $this->model_class_name() . '&code=' . $this->primary_column_value();
        $list_url = 'index.php?page=list_' . $this->model_class_name();

        switch ($type) {
            case 'edit':
                return $edit_url;

            case 'list':
                return $list_url;

            case 'new':
                return 'index.php?page=edit_' . $this->model_class_name();

            default:
                return is_null($this->primary_column_value()) ? $list_url : $edit_url;
        }
    }

    /**
     * 
     * @return bool
     */
    protected function save_insert()
    {
        $columns = [];
        $values = [];
        foreach (array_keys($this->get_model_fields()) as $field) {
            if (null !== $this->{$field}) {
                $columns[] = $field;
                $values[] = $this->var2str($this->{$field});
            }
        }

        $sql = 'INSERT INTO ' . $this->table_name() . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ');';
        if ($this->db->exec($sql)) {
            if (null === $this->primary_column_value()) {
                $this->{$this->primary_column()} = $this->db->lastval();
            }

            return true;
        }

        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function save_update()
    {
        $sql = 'UPDATE ' . $this->table_name();
        $coma = ' SET ';
        foreach (array_keys($this->get_model_fields()) as $field) {
            if ($field == $this->primary_column()) {
                continue;
            }

            $sql .= $coma . $field . ' = ' . $this->var2str($this->{$field});
            $coma = ', ';
        }

        $sql .= ' WHERE ' . $this->primary_column() . ' = ' . $this->var2str($this->primary_column_value()) . ';';
        return (bool) $this->db->exec($sql);
    }
}
