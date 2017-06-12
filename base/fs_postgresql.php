<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Clase para conectar a PostgreSQL.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_postgresql {

    /**
     * El enlace con la base de datos.
     * @var type 
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

    /**
     * Historial de consultas SQL.
     * @var type 
     */
    protected static $history;

    /**
     * Lista de errores.
     * @var type 
     */
    protected static $errors;

    public function __construct() {
        if (!isset(self::$link)) {
            self::$t_selects = 0;
            self::$t_transactions = 0;
            self::$history = array();
            self::$errors = array();
        }
    }

    /**
     * Conecta a la base de datos.
     * @return boolean
     */
    public function connect() {
        $connected = FALSE;

        if (self::$link) {
            $connected = TRUE;
        } else if (function_exists('pg_connect')) {
            self::$link = pg_connect('host=' . FS_DB_HOST . ' dbname=' . FS_DB_NAME .
                    ' port=' . FS_DB_PORT . ' user=' . FS_DB_USER . ' password=' . FS_DB_PASS);
            if (self::$link) {
                $connected = TRUE;

                /// establecemos el formato de fecha para la conexión
                pg_query(self::$link, "SET DATESTYLE TO ISO, DMY;");
            }
        } else {
            self::$errors[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
        }

        return $connected;
    }

    /**
     * Devuelve TRUE si se está conectado a la base de datos.
     * @return boolean
     */
    public function connected() {
        return (bool) self::$link;
    }

    /**
     * Desconecta de la base de datos.
     * @return boolean
     */
    public function close() {
        if (self::$link) {
            $return = pg_close(self::$link);
            self::$link = NULL;
            return $return;
        } else {
            return TRUE;
        }
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     * @return boolean
     */
    public function version() {
        if (self::$link) {
            $aux = pg_version(self::$link);
            return 'POSTGRESQL ' . $aux['server'];
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve la lista de errores.
     * @return type
     */
    public function get_errors() {
        return self::$errors;
    }

    /**
     * Vacía la lista de errores.
     */
    public function clean_errors() {
        self::$errors = array();
    }

    /**
     * Devuelve el número de selects ejecutados
     * @return integer
     */
    public function get_selects() {
        return self::$t_selects;
    }

    /**
     * Devuele le número de transacciones realizadas
     * @return integer
     */
    public function get_transactions() {
        return self::$t_transactions;
    }

    /**
     * Devuelve el historial SQL.
     * @return type
     */
    public function get_history() {
        return self::$history;
    }

    /**
     * Devuelve un array con las columnas de una tabla dada.
     * @param string $table_name
     * @return type
     */
    public function get_columns($table_name) {
        $columns = array();
        $sql = "SELECT column_name as name, data_type as type, character_maximum_length, column_default as default, is_nullable"
                . " FROM information_schema.columns WHERE table_catalog = '" . FS_DB_NAME
                . "' AND table_name = '" . $table_name . "' ORDER BY name ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $d) {
                $d['extra'] = NULL;

                /// añadimos la longitud, si tiene
                if ($d['character_maximum_length']) {
                    $d['type'] .= '(' . $d['character_maximum_length'] . ')';
                    unset($d['character_maximum_length']);
                }

                $columns[] = $d;
            }
        }

        return $columns;
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada:
     * clave primaria, claves ajenas, etc.
     * @param string $table_name
     * @return type
     */
    public function get_constraints($table_name) {
        $constraints = array();
        $sql = "SELECT tc.constraint_name as name, tc.constraint_type as type"
                . " FROM information_schema.table_constraints AS tc"
                . " WHERE tc.table_name = '" . $table_name . "' AND tc.constraint_type IN"
                . " ('PRIMARY KEY','FOREIGN KEY','UNIQUE') ORDER BY type DESC, name ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $constraints[] = $a;
            }
        }

        return $constraints;
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada, pero aportando muchos más detalles.
     * @param string $table_name
     * @return type
     */
    public function get_constraints_extended($table_name) {
        $constraints = array();
        $sql = "SELECT tc.constraint_name as name,
            tc.constraint_type as type,
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name,
            rc.update_rule AS on_update,
            rc.delete_rule AS on_delete
         FROM information_schema.table_constraints AS tc
         LEFT JOIN information_schema.key_column_usage AS kcu
            ON kcu.constraint_schema = tc.constraint_schema
            AND kcu.constraint_catalog = tc.constraint_catalog
            AND kcu.constraint_name = tc.constraint_name
         LEFT JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_schema = tc.constraint_schema
            AND ccu.constraint_catalog = tc.constraint_catalog
            AND ccu.constraint_name = tc.constraint_name
            AND ccu.column_name = kcu.column_name
         LEFT JOIN information_schema.referential_constraints rc
            ON rc.constraint_schema = tc.constraint_schema
            AND rc.constraint_catalog = tc.constraint_catalog
            AND rc.constraint_name = tc.constraint_name
         WHERE tc.table_name = '" . $table_name . "' AND tc.constraint_type IN ('PRIMARY KEY','FOREIGN KEY','UNIQUE')
         ORDER BY type DESC, name ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $constraints[] = $a;
            }
        }

        return $constraints;
    }

    /**
     * Devuelve una array con los indices de una tabla dada.
     * @param string $table_name
     * @return type
     */
    public function get_indexes($table_name) {
        $indexes = array();

        $aux = $this->select("SELECT indexname FROM pg_indexes WHERE tablename = '" . $table_name . "';");
        if ($aux) {
            foreach ($aux as $a) {
                $indexes[] = array('name' => $a['indexname']);
            }
        }

        return $indexes;
    }

    /**
     * Devuelve un array con los datos de bloqueos en la base de datos.
     * @return type
     */
    public function get_locks() {
        $llist = array();
        $sql = "SELECT relname,pg_locks.* FROM pg_class,pg_locks WHERE relfilenode=relation AND NOT granted;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $llist = $a;
            }
        }

        return $llist;
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @return type
     */
    public function list_tables() {
        $tables = array();
        $sql = "SELECT * FROM pg_catalog.pg_tables WHERE schemaname NOT IN "
                . "('pg_catalog','information_schema') ORDER BY tablename ASC;";

        $aux = $this->select($sql);
        if ($aux) {
            foreach ($aux as $a) {
                $tables[] = array('name' => $a['tablename']);
            }
        }

        return $tables;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     * @param string $sql
     * @return type
     */
    public function select($sql) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos la consulta sql al historial
            self::$history[] = $sql;

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                $result = pg_fetch_all($aux);
                pg_free_result($aux);
            } else {
                /// añadimos el error a la lista de errores
                self::$errors[] = pg_last_error(self::$link);
            }

            /// aumentamos el contador de selects realizados
            self::$t_selects++;
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados o false en caso de fallo.
     * Limit es el número de elementos que quieres que devuelva.
     * Offset es el número de resultado desde el que quieres que empiece.
     * @param string $sql
     * @param integer $limit
     * @param integer $offset
     * @return type
     */
    public function select_limit($sql, $limit = FS_ITEM_LIMIT, $offset = 0) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos limit y offset a la consulta sql
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';

            /// añadimos la consulta sql al historial
            self::$history[] = $sql;

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                $result = pg_fetch_all($aux);
                pg_free_result($aux);
            } else {
                /// añadimos el error a la lista de errores
                self::$errors[] = pg_last_error(self::$link);
            }

            /// aumentamos el contador de selects realizados
            self::$t_selects++;
        }

        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates o deletes).
     * Para hacer selects, mejor usar select() o selec_limit().
     * Por defecto se inicia una transacción, se ejecutan las consultas, y si todo
     * sale bien, se guarda, sino se deshace.
     * Se puede evitar este modo de transacción si se pone false
     * en el parametro transaction.
     * @param string $sql
     * @param boolean $transaction
     * @return boolean
     */
    public function exec($sql, $transaction = TRUE) {
        $result = FALSE;

        if (self::$link) {
            /// añadimos la consulta sql al historial
            self::$history[] = $sql;

            if ($transaction) {
                $this->begin_transaction();
            }

            $aux = pg_query(self::$link, $sql);
            if ($aux) {
                pg_free_result($aux);
                $result = TRUE;
            } else {
                self::$errors[] = pg_last_error(self::$link) . '. La secuencia ocupa la posición ' . count(self::$history);
            }

            if ($transaction) {
                if ($result) {
                    $this->commit();
                } else {
                    $this->rollback();
                }
            }
        }

        return $result;
    }

    /**
     * Inicia una transacción SQL.
     * @return boolean
     */
    public function begin_transaction() {
        if (self::$link) {
            pg_query(self::$link, 'BEGIN TRANSACTION;');
        } else {
            return FALSE;
        }
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @return boolean
     */
    public function commit() {
        if (self::$link) {
            /// aumentamos el contador de selects realizados
            self::$t_transactions++;

            return pg_query(self::$link, 'COMMIT;');
        } else {
            return FALSE;
        }
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @return boolean
     */
    public function rollback() {
        if (self::$link) {
            pg_query(self::$link, 'ROLLBACK;');
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve TRUE si la secuancia solicitada existe.
     * @param string $seq_name
     * @return boolean
     */
    private function sequence_exists($seq_name) {
        return (bool) $this->select("SELECT * FROM pg_class where relname = '" . $seq_name . "';");
    }

    /**
     * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
     * @return integer
     */
    public function lastval() {
        $aux = $this->select('SELECT lastval() as num;');
        if ($aux) {
            return $aux[0]['num'];
        } else {
            return FALSE;
        }
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param string $s
     * @return string
     */
    public function escape_string($s) {
        if (self::$link) {
            return pg_escape_string(self::$link, $s);
        } else {
            return $s;
        }
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function date_style() {
        return 'd-m-Y';
    }

    /**
     * Devuelve el SQL necesario para convertir la columna a entero.
     * @param string $col_name
     * @return string
     */
    public function sql_to_int($col_name) {
        return $col_name . '::integer';
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param type $table_name
     * @param type $xml_cols
     * @param type $db_cols
     * @return string
     */
    public function compare_columns($table_name, $xml_cols, $db_cols) {
        $sql = '';

        foreach ($xml_cols as $xml_col) {
            $encontrada = FALSE;
            if ($db_cols) {
                foreach ($db_cols as $db_col) {
                    if ($db_col['name'] == $xml_col['nombre']) {
                        if (!$this->compare_data_types($db_col['type'], $xml_col['tipo'])) {
                            $sql .= 'ALTER TABLE ' . $table_name . ' ALTER COLUMN "' . $xml_col['nombre'] . '" TYPE ' . $xml_col['tipo'] . ';';
                        }

                        if ($db_col['default'] != $xml_col['defecto']) {
                            if (is_null($xml_col['defecto'])) {
                                $sql .= 'ALTER TABLE ' . $table_name . ' ALTER COLUMN "' . $xml_col['nombre'] . '" DROP DEFAULT;';
                            } else {
                                $this->default2check_sequence($table_name, $xml_col['defecto'], $xml_col['nombre']);
                                $sql .= 'ALTER TABLE ' . $table_name . ' ALTER COLUMN "' . $xml_col['nombre'] . '" SET DEFAULT ' . $xml_col['defecto'] . ';';
                            }
                        }

                        if ($db_col['is_nullable'] != $xml_col['nulo']) {
                            if ($xml_col['nulo'] == 'YES') {
                                $sql .= 'ALTER TABLE ' . $table_name . ' ALTER COLUMN "' . $xml_col['nombre'] . '" DROP NOT NULL;';
                            } else {
                                $sql .= 'ALTER TABLE ' . $table_name . ' ALTER COLUMN "' . $xml_col['nombre'] . '" SET NOT NULL;';
                            }
                        }

                        $encontrada = TRUE;
                        break;
                    }
                }
            }
            if (!$encontrada) {
                $sql .= 'ALTER TABLE ' . $table_name . ' ADD COLUMN "' . $xml_col['nombre'] . '" ' . $xml_col['tipo'];

                if ($xml_col['defecto'] !== NULL) {
                    $sql .= ' DEFAULT ' . $xml_col['defecto'];
                }

                if ($xml_col['nulo'] == 'NO') {
                    $sql .= ' NOT NULL';
                }

                $sql .= ';';
            }
        }

        return $sql;
    }

    /**
     * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
     * @param string $db_type
     * @param string $xml_type
     * @return boolean
     */
    private function compare_data_types($db_type, $xml_type) {
        if (FS_CHECK_DB_TYPES != 1) {
            /// si está desactivada la comprobación de tipos, devolvemos que son iguales.
            return TRUE;
        } else if ($db_type == $xml_type) {
            return TRUE;
        } else if (strtolower($xml_type) == 'serial') {
            return TRUE;
        } else if (substr($db_type, 0, 4) == 'time' AND substr($xml_type, 0, 4) == 'time') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * A partir del campo default del xml de una tabla
     * comprueba si se refiere a una secuencia, y si es así
     * comprueba la existencia de la secuencia. Si no la encuentra
     * la crea.
     * @param string $table_name
     * @param string $default
     * @param string $colname
     */
    private function default2check_sequence($table_name, $default, $colname) {
        /// ¿Se refiere a una secuencia?
        if (strtolower(substr($default, 0, 9)) == "nextval('") {
            $aux = explode("'", $default);
            if (count($aux) == 3) {
                /// ¿Existe esa secuencia?
                if (!$this->sequence_exists($aux[1])) {
                    /// ¿En qué número debería empezar esta secuencia?
                    $num = 1;
                    $aux_num = $this->select("SELECT MAX(" . $colname . "::integer) as num FROM " . $table_name . ";");
                    if ($aux_num) {
                        $num += intval($aux_num[0]['num']);
                    }

                    $this->exec("CREATE SEQUENCE " . $aux[1] . " START " . $num . ";");
                }
            }
        }
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $table_name
     * @param type $xml_cons
     * @param type $db_cons
     * @param boolean $delete_only
     * @return string
     */
    public function compare_constraints($table_name, $xml_cons, $db_cons, $delete_only = FALSE) {
        $sql = '';

        if ($db_cons) {
            /// comprobamos una a una las viejas
            foreach ($db_cons as $db_con) {
                $found = FALSE;
                if ($xml_cons) {
                    foreach ($xml_cons as $xml_con) {
                        if ($db_con['name'] == $xml_con['nombre']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    /// eliminamos la restriccion
                    $sql .= "ALTER TABLE " . $table_name . " DROP CONSTRAINT " . $db_con['name'] . ";";
                }
            }
        }

        if ($xml_cons AND ! $delete_only) {
            /// comprobamos una a una las nuevas
            foreach ($xml_cons as $xml_con) {
                $found = FALSE;
                if ($db_cons) {
                    foreach ($db_cons as $db_con) {
                        if ($xml_con['nombre'] == $db_con['name']) {
                            $found = TRUE;
                            break;
                        }
                    }
                }

                if (!$found) {
                    /// añadimos la restriccion
                    $sql .= "ALTER TABLE " . $table_name . " ADD CONSTRAINT " . $xml_con['nombre'] . " " . $xml_con['consulta'] . ";";
                }
            }
        }

        return $sql;
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $table_name
     * @param type $xml_cols
     * @param type $xml_cons
     * @return string
     */
    public function generate_table($table_name, $xml_cols, $xml_cons) {
        $sql = 'CREATE TABLE ' . $table_name . ' (';

        $i = FALSE;
        foreach ($xml_cols as $col) {
            /// añade la coma al final
            if ($i) {
                $sql .= ', ';
            } else {
                $i = TRUE;
            }

            $sql .= '"' . $col['nombre'] . '" ' . $col['tipo'];

            if ($col['nulo'] == 'NO') {
                $sql .= ' NOT NULL';
            }

            if ($col['defecto'] !== NULL AND ! in_array($col['tipo'], array('serial', 'bigserial'))) {
                $sql .= ' DEFAULT ' . $col['defecto'];
            }
        }

        return $sql . ' ); ' . $this->compare_constraints($table_name, $xml_cons, FALSE);
    }

    /**
     * Debería realizar comprobaciones extra, pero en PostgreSQL no es necesario.
     * @param type $table_name
     * @return boolean
     */
    public function check_table_aux($table_name) {
        return TRUE;
    }

}
