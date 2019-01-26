<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <neorazorx@gmail.com>
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
 * Description of fs_db_engine
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
abstract class fs_db_engine
{

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     * @var fs_core_log 
     */
    protected static $core_log;

    /**
     * El enlace con la base de datos.
     * @var resource
     */
    protected static $link;

    /**
     * Nº de selects ejecutados.
     * @var integer 
     */
    protected static $t_selects;

    /**
     * Nº de transacciones ejecutadas.
     * @var integer 
     */
    protected static $t_transactions;

    abstract public function begin_transaction();

    abstract public function check_table_aux($table_name);

    abstract public function close();

    abstract public function commit();

    abstract public function compare_columns($table_name, $xml_cols, $db_cols);

    abstract public function compare_constraints($table_name, $xml_cons, $db_cons, $delete_only = FALSE);

    abstract public function connect();

    abstract public function date_style();

    abstract public function escape_string($str);

    abstract public function exec($sql, $transaction = TRUE);

    abstract public function generate_table($table_name, $xml_cols, $xml_cons);

    abstract public function get_columns($table_name);

    abstract public function get_constraints($table_name);

    abstract public function get_constraints_extended($table_name);

    abstract public function get_indexes($table_name);

    abstract public function get_locks();

    abstract public function lastval();

    abstract public function list_tables();

    abstract public function rollback();

    abstract public function select($sql);

    abstract public function select_limit($sql, $limit = FS_ITEM_LIMIT, $offset = 0);

    abstract public function sql_to_int($col_name);

    abstract public function version();

    public function __construct()
    {
        if (!isset(self::$link)) {
            self::$t_selects = 0;
            self::$t_transactions = 0;
            self::$core_log = new fs_core_log();
        }
    }

    /**
     * Devuelve TRUE si se está conectado a la base de datos.
     * @return boolean
     */
    public function connected()
    {
        return (bool) self::$link;
    }

    /**
     * Devuelve el historial SQL.
     * @return array
     */
    public function get_history()
    {
        return self::$core_log->get_sql_history();
    }

    /**
     * Devuelve el número de selects ejecutados
     * @return integer
     */
    public function get_selects()
    {
        return self::$t_selects;
    }

    /**
     * Devuele le número de transacciones realizadas
     * @return integer
     */
    public function get_transactions()
    {
        return self::$t_transactions;
    }

    /**
     * Look for a column with a value by his name in array.
     *
     * @param array  $items
     * @param string $index
     * @param string $value
     *
     * @return array
     */
    protected function search_in_array($items, $index, $value)
    {
        if (empty($items)) {
            return [];
        }

        foreach ($items as $column) {
            if ($column[$index] === $value) {
                return $column;
            }
        }

        return [];
    }
}
