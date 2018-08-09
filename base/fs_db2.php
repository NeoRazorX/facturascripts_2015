<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
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
require_once 'base/fs_mysql.php';
require_once 'base/fs_postgresql.php';

/**
 * Clase genérica de acceso a la base de datos, ya sea MySQL o PostgreSQL.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_db2
{

    /**
     * Transacttiones automáticas activadas si o no.
     * @var boolean
     */
    private static $auto_transactions;

    /**
     * Motor utilizado, MySQL o PostgreSQL
     * @var fs_mysql|fs_postgresql
     */
    private static $engine;

    /**
     * Última lista de tablas de la base de datos.
     * @var array|false 
     */
    private static $table_list;

    public function __construct()
    {
        if (!isset(self::$engine)) {
            if (strtolower(FS_DB_TYPE) == 'mysql') {
                self::$engine = new fs_mysql();
            } else {
                self::$engine = new fs_postgresql();
            }

            self::$auto_transactions = TRUE;
            self::$table_list = FALSE;
        }
    }

    /**
     * Inicia una transacción SQL.
     * @return boolean
     */
    public function begin_transaction()
    {
        return self::$engine->begin_transaction();
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param string $table_name
     * @return boolean
     */
    public function check_table_aux($table_name)
    {
        return self::$engine->check_table_aux($table_name);
    }

    /**
     * Desconecta de la base de datos.
     * @return boolean
     */
    public function close()
    {
        return self::$engine->close();
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @return boolean
     */
    public function commit()
    {
        return self::$engine->commit();
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia sql en caso de encontrar diferencias.
     * @param string $table_name
     * @param array $xml_cols
     * @param array $db_cols
     * @return string
     */
    public function compare_columns($table_name, $xml_cols, $db_cols)
    {
        return self::$engine->compare_columns($table_name, $xml_cols, $db_cols);
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia sql en caso de encontrar diferencias.
     * @param string $table_name
     * @param array $xml_cons
     * @param array $db_cons
     * @param boolean $delete_only
     * @return string
     */
    public function compare_constraints($table_name, $xml_cons, $db_cons, $delete_only = FALSE)
    {
        return self::$engine->compare_constraints($table_name, $xml_cons, $db_cons, $delete_only);
    }

    /**
     * Conecta a la base de datos.
     * @return boolean
     */
    public function connect()
    {
        return self::$engine->connect();
    }

    /**
     * Devuelve TRUE si se está conestado a la base de datos.
     * @return boolean
     */
    public function connected()
    {
        return self::$engine->connected();
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function date_style()
    {
        return self::$engine->date_style();
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param string $str
     * @return string
     */
    public function escape_string($str)
    {
        return self::$engine->escape_string($str);
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates o deletes).
     * Para hacer selects, mejor usar select() o selec_limit().
     * Por defecto se inicia una transacción, se ejecutan las consultas, y si todo
     * sale bien, se guarda, sino se deshace.
     * Se puede evitar este modo de transacción si se pone false
     * en el parametro transaction, o con la función set_auto_transactions(FALSE)
     * @param string $sql
     * @param boolean $transaction
     * @return boolean
     */
    public function exec($sql, $transaction = NULL)
    {
        /// usamos self::$auto_transactions como valor por defecto para la función
        if (is_null($transaction)) {
            $transaction = self::$auto_transactions;
        }

        /// limpiamos la lista de tablas, ya que podría haber cambios al ejecutar este sql.
        self::$table_list = FALSE;

        return self::$engine->exec($sql, $transaction);
    }

    /**
     * Devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada.
     * @param string $table_name
     * @param array $xml_cols
     * @param array $xml_cons
     * @return string
     */
    public function generate_table($table_name, $xml_cols, $xml_cons)
    {
        return self::$engine->generate_table($table_name, $xml_cols, $xml_cons);
    }

    /**
     * Devuelve el valor de auto_transacions, para saber si las transacciones
     * automáticas están activadas o no.
     * @return boolean
     */
    public function get_auto_transactions()
    {
        return self::$auto_transactions;
    }

    /**
     * Devuelve un array con las columnas de una tabla dada.
     * @param string $table_name
     * @return array
     */
    public function get_columns($table_name)
    {
        return self::$engine->get_columns($table_name);
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada.
     * @param string $table_name
     * @param boolean $extended
     * @return array
     */
    public function get_constraints($table_name, $extended = FALSE)
    {
        if ($extended) {
            return self::$engine->get_constraints_extended($table_name);
        }

        return self::$engine->get_constraints($table_name);
    }

    /**
     * Devuelve el historial SQL.
     * @return array
     */
    public function get_history()
    {
        return self::$engine->get_history();
    }

    /**
     * Devuelve una array con los indices de una tabla dada.
     * @param string $table_name
     * @return array
     */
    public function get_indexes($table_name)
    {
        return self::$engine->get_indexes($table_name);
    }

    /**
     * Devuelve un array con los bloqueos de la base de datos.
     * @return array
     */
    public function get_locks()
    {
        return self::$engine->get_locks();
    }

    /**
     * Devuelve el nº de selects a la base de datos.
     * @return integer
     */
    public function get_selects()
    {
        return self::$engine->get_selects();
    }

    /**
     * Devuelve el nº de transacciones con la base de datos.
     * @return integer
     */
    public function get_transactions()
    {
        return self::$engine->get_transactions();
    }

    /**
     * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
     * @return integer
     */
    public function lastval()
    {
        return self::$engine->lastval();
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @return array
     */
    public function list_tables()
    {
        if (self::$table_list === FALSE) {
            self::$table_list = self::$engine->list_tables();
        }

        return self::$table_list;
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @return boolean
     */
    public function rollback()
    {
        return self::$engine->rollback();
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     * @param string $sql
     * @return array|false
     */
    public function select($sql)
    {
        return self::$engine->select($sql);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados o false en caso de fallo.
     * Limit es el número de elementos que quieres que devuelva.
     * Offset es el número de resultado desde el que quieres que empiece.
     * @param string $sql
     * @param integer $limit
     * @param integer $offset
     * @return array|false
     */
    public function select_limit($sql, $limit = FS_ITEM_LIMIT, $offset = 0)
    {
        return self::$engine->select_limit($sql, $limit, $offset);
    }

    /**
     * Activa/desactiva las transacciones automáticas en la función exec()
     * @param boolean $value
     */
    public function set_auto_transactions($value)
    {
        self::$auto_transactions = $value;
    }

    /**
     * Devuelve el SQL necesario para convertir la columna a entero.
     * @param string $col_name
     * @return string
     */
    public function sql_to_int($col_name)
    {
        return self::$engine->sql_to_int($col_name);
    }

    /**
     * Devuelve TRUE si la tabla existe, FALSE en caso contrario.
     * @param string $name
     * @param array $list
     * @return boolean
     */
    public function table_exists($name, $list = FALSE)
    {
        $result = FALSE;

        if ($list === FALSE) {
            $list = $this->list_tables();
        }

        foreach ($list as $table) {
            if ($table['name'] == $name) {
                $result = TRUE;
                break;
            }
        }

        return $result;
    }

    /**
     * Devuelve el motor de base de datos usado y la versión.
     * @return string
     */
    public function version()
    {
        return self::$engine->version();
    }
}
