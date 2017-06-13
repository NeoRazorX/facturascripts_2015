<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of fs_core_log
 *
 * @author Carlos García Gómez
 */
class fs_core_log {

    private static $advices;
    private static $controller_name;
    private static $errors;
    private static $messages;
    private static $sql_history;

    public function __construct($controller_name = NULL) {
        if (!isset(self::$advices)) {
            self::$advices = array();
            self::$controller_name = $controller_name;
            self::$errors = array();
            self::$messages = array();
            self::$sql_history = array();
        }
    }
    
    public function clean_advices() {
        self::$advices = array();
    }
    
    public function clean_errors() {
        self::$errors = array();
    }
    
    public function clean_messages() {
        self::$messages = array();
    }
    
    public function clean_sql_history() {
        self::$sql_history = array();
    }
    
    public function controller_name() {
        return self::$controller_name;
    }

    public function get_advices() {
        return self::$advices;
    }

    public function get_errors() {
        return self::$errors;
    }

    public function get_messages() {
        return self::$messages;
    }
    
    public function get_sql_history() {
        return self::$sql_history;
    }

    public function new_advice($msg) {
        self::$advices[] = $msg;
    }

    public function new_error($msg) {
        self::$errors[] = $msg;
    }

    public function new_message($msg) {
        self::$messages[] = $msg;
    }
    
    public function new_sql($sql) {
        self::$sql_history[] = $sql;
    }

}
