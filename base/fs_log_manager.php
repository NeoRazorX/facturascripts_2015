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

/**
 * Description of fs_log_manager
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_log_manager
{

    /**
     *
     * @var fs_core_log
     */
    private $core_log;

    public function __construct()
    {
        $this->core_log = new fs_core_log();
    }

    public function save()
    {
        foreach ($this->core_log->get_to_save() as $data) {
            $new_log = new fs_log();
            $new_log->alerta = $data['context']['alert'];
            $new_log->controlador = $this->core_log->controller_name();
            $new_log->detalle = $data['message'];
            $new_log->fecha = date('d-m-Y H:i:s', $data['time']);
            $new_log->ip = fs_get_ip();
            $new_log->tipo = $data['context']['type'];
            $new_log->usuario = $this->core_log->user_nick();
            $new_log->save();
        }
    }
}
