<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
 * Description of fs_core_log
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_core_log
{

    /**
     * Nombre del controlador que inicia este log.
     * @var string
     */
    private static $controller_name;

    /**
     * Array de mensajes.
     * @var array
     */
    private static $data_log;

    /**
     * Usuario que ha iniciado sesión.
     * @var string
     */
    private static $user_nick;

    /**
     * 
     * @param string $controller_name
     */
    public function __construct($controller_name = NULL)
    {
        if (!isset(self::$data_log)) {
            self::$controller_name = $controller_name;
            self::$data_log = [];
        }
    }

    public function clean_advices()
    {
        $this->clean('advices');
    }

    public function clean_errors()
    {
        $this->clean('errors');
    }

    public function clean_messages()
    {
        $this->clean('messages');
    }

    public function clean_sql_history()
    {
        $this->clean('sql');
    }

    public function clean_to_save()
    {
        $this->clean('save');
    }

    /**
     * 
     * @return string
     */
    public function controller_name()
    {
        return self::$controller_name;
    }

    /**
     * Devuelve el listado de consejos a mostrar al usuario.
     * @return array
     */
    public function get_advices()
    {
        return $this->read('advices');
    }

    /**
     * Devuelve el listado de errores a mostrar al usuario.
     * @return array
     */
    public function get_errors()
    {
        return $this->read('errors');
    }

    /**
     * Devuelve el listado de mensajes a mostrar al usuario.
     * @return array
     */
    public function get_messages()
    {
        return $this->read('messages');
    }

    /**
     * Devuelve el historial de consultas SQL.
     * @return array
     */
    public function get_sql_history()
    {
        return $this->read('sql');
    }

    /**
     * Devuelve la lista de mensajes a guardar.
     * @return array
     */
    public function get_to_save()
    {
        return $this->read('save', true);
    }

    /**
     * Añade un consejo al listado.
     * @param string $msg
     * @param array  $context
     */
    public function new_advice($msg, $context = [])
    {
        $this->log($msg, 'advices', $context);
    }

    /**
     * Añade un mensaje de error al listado.
     * @param string $msg
     * @param array  $context
     */
    public function new_error($msg, $context = [])
    {
        $this->log($msg, 'errors', $context);
    }

    /**
     * Añade un mensaje al listado.
     * @param string $msg
     * @param array  $context
     */
    public function new_message($msg, $context = [])
    {
        $this->log($msg, 'messages', $context);
    }

    /**
     * Añade una consulta SQL al historial.
     * @param string $sql
     */
    public function new_sql($sql)
    {
        $this->log($sql, 'sql');
    }

    /**
     * Añade un mensaje para guardar después con el fs_log_manager.
     * @param string $msg
     * @param string $type
     * @param bool   $alert
     * @param array  $context
     */
    public function save($msg, $type = 'error', $alert = FALSE, $context = [])
    {
        $context['alert'] = $alert;
        $context['type'] = $type;
        $this->log($msg, 'save', $context);
    }

    /**
     * 
     * @param string $nick
     */
    public function set_user_nick($nick)
    {
        self::$user_nick = $nick;
    }

    /**
     * 
     * @return string
     */
    public function user_nick()
    {
        return self::$user_nick;
    }

    /**
     * 
     * @param string $channel
     */
    private function clean($channel)
    {
        foreach (self::$data_log as $key => $value) {
            if ($value['channel'] === $channel) {
                unset(self::$data_log[$key]);
            }
        }
    }

    /**
     * 
     * @param string $msg
     * @param string $channel
     * @param array  $context
     */
    private function log($msg, $channel, $context = [])
    {
        self::$data_log[] = [
            'channel' => $channel,
            'context' => $context,
            'message' => $msg,
            'time' => time(),
        ];
    }

    /**
     * 
     * @param string $channel
     * @return array
     */
    private function read($channel, $full = false)
    {
        $messages = [];
        foreach (self::$data_log as $data) {
            if ($data['channel'] === $channel) {
                $messages[] = $full ? $data : $data['message'];
            }
        }

        return $messages;
    }
}
