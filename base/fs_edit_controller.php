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
require_once 'base/fs_edit_decoration.php';

/**
 * Description of fs_edit_controller
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class fs_edit_controller extends fs_controller
{

    /**
     * TRUE si el usuario tiene permisos para eliminar en la página.
     *
     * @var boolean 
     */
    public $allow_delete;

    /**
     *
     * @var fs_edit_decoration
     */
    public $decoration;

    /**
     *
     * @var fs_model_extended
     */
    public $model;

    abstract public function get_model_class_name();

    abstract protected function set_edit_columns();

    /**
     * 
     * @param string $name
     * @param string $title
     * @param string $folder
     */
    public function __construct($name = __CLASS__, $title = 'home', $folder = '')
    {
        parent::__construct($name, $title, $folder, FALSE, FALSE, FALSE);
    }

    /**
     * 
     * @return boolean
     */
    protected function delete_action()
    {
        if (!$this->allow_delete) {
            $this->new_error_msg('No tienes permiso para eliminar en esta página.');
            return false;
        }

        if ($this->model->delete()) {
            $this->new_message('Datos eliminados correctamente.');
            $this->model->clear();
            return true;
        }

        $this->new_error_msg('Error al eliminar los datos.');
        return false;
    }

    /**
     * 
     * @return boolean
     */
    protected function edit_action()
    {
        if (isset($_POST['petition_id']) && $this->duplicated_petition($_POST['petition_id'])) {
            $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón y se han'
                . ' enviado dos peticiones. O tienes el ratón roto.');
            return false;
        }

        /// asignamos valores
        foreach (array_keys($this->decoration->columns) as $key) {
            if (isset($_POST[$key])) {
                $this->model->{$key} = $_POST[$key];
            }
        }

        if ($this->model->save()) {
            $this->new_message('Datos guardados correctamente.');
            return true;
        }

        $this->new_error_msg('Error al guardar los datos.');
        return false;
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);

        $this->decoration = new fs_edit_decoration();
        $this->template = 'master/edit_controller';

        /// cargamos el modelo
        $model_class = $this->get_model_class_name();
        $this->model = new $model_class();
        if (isset($_REQUEST['code'])) {
            $this->model->load_from_code($_REQUEST['code']);
        }

        $this->set_edit_columns();

        /// acciones
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        switch ($action) {
            case 'delete':
                $this->delete_action();
                break;

            case 'edit':
                $this->edit_action();
                break;
        }
    }

    /**
     * 
     * @param string $tabla
     * @param string $columna1
     * @param string $columna2
     *
     * @return array
     */
    protected function sql_distinct($tabla, $columna1, $columna2 = '')
    {
        if (!$this->db->table_exists($tabla)) {
            return [];
        }

        $columna2 = empty($columna2) ? $columna1 : $columna2;
        $final = [];
        $sql = "SELECT DISTINCT " . $columna1 . ", " . $columna2 . " FROM " . $tabla . " ORDER BY " . $columna2 . " ASC;";
        $data = $this->db->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                if ($d[$columna1] != '') {
                    $final[$d[$columna1]] = $d[$columna2];
                }
            }
        }

        return $final;
    }
}
