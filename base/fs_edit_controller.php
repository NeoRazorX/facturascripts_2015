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
     * @var boolean 
     */
    public $allow_delete;

    /**
     *
     * @var array
     */
    public $columns = [];

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

    public function __construct($name = __CLASS__, $title = 'home', $folder = '')
    {
        parent::__construct($name, $title, $folder, FALSE, FALSE, FALSE);
    }

    protected function add_edit_column($col_name, $label, $type, $num_cols = 2, $required = false)
    {
        $this->columns[$col_name] = [
            'label' => $label,
            'num_cols' => $num_cols,
            'required' => $required,
            'type' => $type,
        ];
    }

    protected function delete_action()
    {
        if (!$this->allow_delete) {
            $this->new_error_msg('No tienes permiso para eliminar en esta página.');
        }

        if ($this->model->delete()) {
            $this->new_message('Datos eliminados correctamente.');
            $this->model->clear();
        } else {
            $this->new_error_msg('Error al eliminar los datos.');
        }
    }

    protected function edit_action()
    {
        foreach (array_keys($this->columns) as $key) {
            if (isset($_POST[$key])) {
                $this->model->{$key} = $_POST[$key];
            }
        }

        if ($this->model->save()) {
            $this->new_message('Datos guardados correctamente.');
        } else {
            $this->new_error_msg('Error al guardar los datos.');
        }
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);

        $this->decoration = new fs_edit_decoration();
        $this->template = 'master/edit_controller';

        /// load model
        $model_class = $this->get_model_class_name();
        $this->model = new $model_class();
        if (isset($_REQUEST['code'])) {
            $this->model->load_from_code($_REQUEST['code']);
        }

        $this->set_edit_columns();

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        switch ($action) {
            case 'delete':
                return $this->delete_action();

            case 'edit':
                return $this->edit_action();
        }
    }
}
