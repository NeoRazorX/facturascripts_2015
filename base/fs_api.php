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
 * Description of fs_api
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_api
{

    /**
     * 
     * @return string
     */
    public function run()
    {
        $function_name = fs_filter_input_req('f');
        $version = fs_filter_input_req('v');

        if (!$version) {
            return 'Version de la API de FacturaScripts ausente. Actualiza el cliente.';
        } else if ($version != '2') {
            return 'Version de la API de FacturaScripts incorrecta. Actualiza el cliente.';
        } else if (!$function_name) {
            return 'Ninguna funcion ejecutada.';
        }

        return $this->execute($function_name);
    }

    /**
     * 
     * @return int
     */
    private function get_last_activity()
    {
        $last_activity = 0;

        $user_model = new fs_user();
        foreach ($user_model->all() as $user) {
            $time = empty($user->last_login) ? 0 : strtotime($user->last_login . ' ' . $user->last_login_time);
            if ($time > $last_activity) {
                $last_activity = $time;
            }
        }

        return date('Y-m-d H:i:s', $last_activity);
    }

    /**
     * 
     * @param string $function_name
     *
     * @return string
     */
    private function execute($function_name)
    {
        $fsext = new fs_extension();
        foreach ($fsext->all_4_type('api') as $ext) {
            if ($ext->text != $function_name) {
                continue;
            }

            try {
                call_user_func($function_name);
            } catch (Exception $exception) {
                echo 'ERROR: ' . $exception->getMessage();
            }

            return '';
        }

        if ($function_name == 'lastactivity') {
            return $this->get_last_activity();
        }

        return 'Ninguna funcion API ejecutada.';
    }
}
