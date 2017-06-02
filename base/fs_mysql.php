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
 * Clase para conectar a MySQL.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_mysql {

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
      } else if (class_exists('mysqli')) {
         self::$link = @new mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, intval(FS_DB_PORT));

         if (self::$link->connect_error) {
            self::$errors[] = self::$link->connect_error;
            self::$link = NULL;
         } else {
            self::$link->set_charset('utf8');
            $connected = TRUE;

            if (!FS_FOREIGN_KEYS) {
               /// desactivamos las claves ajenas
               $this->exec("SET foreign_key_checks = 0;");
            }

            /// desactivamos el autocommit
            self::$link->autocommit(FALSE);
         }
      } else {
         self::$errors[] = 'No tienes instalada la extensión de PHP para MySQL.';
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
         $return = self::$link->close();
         self::$link = NULL;
         return $return;
      } else {
         return TRUE;
      }
   }

   /**
    * Devuelve el motor de base de datos y la versión.
    * @return string
    */
   public function version() {
      if (self::$link) {
         return 'MYSQL ' . self::$link->server_version;
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
    * Devuelve el número de selects ejecutados.
    * @return integer
    */
   public function get_selects() {
      return self::$t_selects;
   }

   /**
    * Devuele le número de transacciones realizadas.
    * @return integer
    */
   public function get_transactions() {
      return self::$t_transactions;
   }

   /**
    * Devuelve el historial de consultas SQL.
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

      $aux = $this->select("SHOW COLUMNS FROM `" . $table_name . "`;");
      if ($aux) {
         foreach ($aux as $a) {
            $columns[] = array(
                'name' => $a['Field'],
                'type' => $a['Type'],
                'default' => $a['Default'],
                'is_nullable' => $a['Null'],
                'extra' => $a['Extra']
            );
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
      $sql = "SELECT CONSTRAINT_NAME as name, CONSTRAINT_TYPE as type FROM information_schema.table_constraints "
              . "WHERE table_schema = schema() AND table_name = '" . $table_name . "';";

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
    * @param type $table_name
    * @return type
    */
   public function get_indexes($table_name) {
      $indexes = array();

      $aux = $this->select("SHOW INDEXES FROM " . $table_name . ";");
      if ($aux) {
         foreach ($aux as $a) {
            $indexes[] = array('name' => $a['Key_name']);
         }
      }

      return $indexes;
   }

   /**
    * Devuelve un array con los datos de bloqueos en la base de datos.
    * @return type
    */
   public function get_locks() {
      return array();
   }

   /**
    * Devuelve un array con los nombres de las tablas de la base de datos.
    * @return type
    */
   public function list_tables() {
      $tables = array();

      $aux = $this->select("SHOW TABLES;");
      if ($aux) {
         foreach ($aux as $a) {
            if (isset($a['Tables_in_' . FS_DB_NAME])) {
               $tables[] = array('name' => $a['Tables_in_' . FS_DB_NAME]);
            }
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

         $aux = self::$link->query($sql);
         if ($aux) {
            $result = array();
            while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
               $result[] = $row;
            }
            $aux->free();
         } else {
            /// añadimos el error a la lista de errores
            self::$errors[] = self::$link->error;
         }

         /// aumentamos el contador de selects realizados
         self::$t_selects++;
      }

      return $result;
   }

   /**
    * Ejecuta una sentencia SQL de tipo select, pero con paginación,
    * y devuelve un array con los resultados,
    * o false en caso de fallo.
    * Limit es el número de elementos que quieres que devuelve.
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

         $aux = self::$link->query($sql);
         if ($aux) {
            $result = array();
            while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
               $result[] = $row;
            }
            $aux->free();
         } else {
            /// añadimos el error a la lista de errores
            self::$errors[] = self::$link->error;
         }

         /// aumentamos el contador de selects realizados
         self::$t_selects++;
      }

      return $result;
   }

   /**
    * Ejecuta sentencias SQL sobre la base de datos (inserts, updates y deletes).
    * Para selects, mejor usar las funciones select() o select_limit().
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

         $i = 0;
         if (self::$link->multi_query($sql)) {
            do {
               $i++;
            } while (self::$link->more_results() AND self::$link->next_result());
         }

         if (self::$link->errno) {
            self::$errors[] = 'Error al ejecutar la consulta ' . $i . ': ' . self::$link->error .
                    '. La secuencia ocupa la posición ' . count(self::$history);
         } else {
            $result = TRUE;
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
         return self::$link->begin_transaction();
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

         return self::$link->commit();
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
         return self::$link->rollback();
      } else {
         return FALSE;
      }
   }

   /**
    * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
    * @return integer
    */
   public function lastval() {
      $aux = $this->select('SELECT LAST_INSERT_ID() as num;');
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
         return self::$link->escape_string($s);
      } else {
         return $s;
      }
   }

   /**
    * Devuelve el estilo de fecha del motor de base de datos.
    * @return string
    */
   public function date_style() {
      return 'Y-m-d';
   }

   /**
    * Devuelve el SQL necesario para convertir la columna a entero.
    * @param string $col_name
    * @return string
    */
   public function sql_to_int($col_name) {
      return 'CAST(' . $col_name . ' as UNSIGNED)';
   }

   /**
    * Compara dos arrays de columnas, devuelve una sentencia SQL en caso de encontrar diferencias.
    * @param string $table_name
    * @param type $xml_cols
    * @param type $db_cols
    * @return type
    */
   public function compare_columns($table_name, $xml_cols, $db_cols) {
      $sql = '';

      foreach ($xml_cols as $xml_col) {
         $encontrada = FALSE;
         if ($db_cols) {
            if (strtolower($xml_col['tipo']) == 'integer') {
               /**
                * Desde la pestaña avanzado el panel de control se puede cambiar
                * el tipo de entero a usar en las columnas.
                */
               $xml_col['tipo'] = FS_DB_INTEGER;
            }

            foreach ($db_cols as $db_col) {
               if ($db_col['name'] == $xml_col['nombre']) {
                  if (!$this->compare_data_types($db_col['type'], $xml_col['tipo'])) {
                     $sql .= 'ALTER TABLE ' . $table_name . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ';';
                  }

                  if ($db_col['is_nullable'] != $xml_col['nulo']) {
                     if ($xml_col['nulo'] == 'YES') {
                        $sql .= 'ALTER TABLE ' . $table_name . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NULL;';
                     } else {
                        $sql .= 'ALTER TABLE ' . $table_name . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NOT NULL;';
                     }
                  }

                  if (!$this->compare_defaults($db_col['default'], $xml_col['defecto'])) {
                     if (is_null($xml_col['defecto'])) {
                        $sql .= 'ALTER TABLE ' . $table_name . ' ALTER `' . $xml_col['nombre'] . '` DROP DEFAULT;';
                     } else {
                        if (strtolower(substr($xml_col['defecto'], 0, 9)) == "nextval('") { /// nextval es para postgresql
                           if ($db_col['extra'] != 'auto_increment') {
                              $sql .= 'ALTER TABLE ' . $table_name . ' MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'];

                              if ($xml_col['nulo'] == 'YES') {
                                 $sql .= ' NULL AUTO_INCREMENT;';
                              } else {
                                 $sql .= ' NOT NULL AUTO_INCREMENT;';
                              }
                           }
                        } else {
                           $sql .= 'ALTER TABLE ' . $table_name . ' ALTER `' . $xml_col['nombre'] . '` SET DEFAULT ' . $xml_col['defecto'] . ";";
                        }
                     }
                  }

                  $encontrada = TRUE;
                  break;
               }
            }
         }
         if (!$encontrada) {
            $sql .= 'ALTER TABLE ' . $table_name . ' ADD `' . $xml_col['nombre'] . '` ';

            if ($xml_col['tipo'] == 'serial') {
               $sql .= '`' . $xml_col['nombre'] . '` ' . FS_DB_INTEGER . ' NOT NULL AUTO_INCREMENT;';
            } else {
               $sql .= $xml_col['tipo'];

               if ($xml_col['nulo'] == 'NO') {
                  $sql .= " NOT NULL";
               } else {
                  $sql .= " NULL";
               }

               if ($xml_col['defecto'] !== NULL) {
                  $sql .= " DEFAULT " . $xml_col['defecto'] . ";";
               } else if ($xml_col['nulo'] == 'YES') {
                  $sql .= " DEFAULT NULL;";
               } else {
                  $sql .= ';';
               }
            }
         }
      }

      return $this->fix_postgresql($sql);
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
      } else if ($db_type == 'tinyint(1)' AND $xml_type == 'boolean') {
         return TRUE;
      } else if (substr($db_type, 0, 4) == 'int(' AND $xml_type == 'INTEGER') {
         return TRUE;
      } else if (substr($db_type, 0, 6) == 'double' AND $xml_type == 'double precision') {
         return TRUE;
      } else if (substr($db_type, 0, 4) == 'time' AND substr($xml_type, 0, 4) == 'time') {
         return TRUE;
      } else if (substr($db_type, 0, 8) == 'varchar(' AND substr($xml_type, 0, 18) == 'character varying(') {
         /// comprobamos las longitudes
         return (substr($db_type, 8, -1) == substr($xml_type, 18, -1));
      } else if (substr($db_type, 0, 5) == 'char(' AND substr($xml_type, 0, 18) == 'character varying(') {
         /// comprobamos las longitudes
         return (substr($db_type, 5, -1) == substr($xml_type, 18, -1));
      } else {
         return FALSE;
      }
   }

   /**
    * Compara los tipos por defecto. Devuelve TRUE si son equivalentes.
    * @param string $db_default
    * @param string $xml_default
    * @return boolean
    */
   private function compare_defaults($db_default, $xml_default) {
      if ($db_default == $xml_default) {
         return TRUE;
      } else if (in_array($db_default, array('0', 'false', 'FALSE'))) {
         return in_array($xml_default, array('0', 'false', 'FALSE'));
      } else if (in_array($db_default, array('1', 'true', 'TRUE'))) {
         return in_array($xml_default, array('1', 'true', 'TRUE'));
      } else if ($db_default == '00:00:00' AND $xml_default == 'now()') {
         return TRUE;
      } else if ($db_default == date('Y-m-d') . ' 00:00:00' AND $xml_default == 'CURRENT_TIMESTAMP') {
         return TRUE;
      } else if ($db_default == 'CURRENT_DATE' AND $xml_default == date("'Y-m-d'")) {
         return TRUE;
      } else if (substr($xml_default, 0, 8) == 'nextval(') {
         return TRUE;
      } else {
         $db_default = str_replace(array('::character varying', "'"), array('', ''), $db_default);
         $xml_default = str_replace(array('::character varying', "'"), array('', ''), $xml_default);
         return ($db_default == $xml_default);
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
         /**
          * comprobamos una a una las restricciones de la base de datos, si hay que eliminar una,
          * tendremos que eliminar todas para evitar problemas.
          */
         $delete = FALSE;
         foreach ($db_cons as $db_con) {
            $found = FALSE;
            if ($xml_cons) {
               foreach ($xml_cons as $xml_con) {
                  if ($db_con['name'] == 'PRIMARY' OR $db_con['name'] == $xml_con['nombre']) {
                     $found = TRUE;
                     break;
                  }
               }
            }

            if (!$found) {
               $delete = TRUE;
               break;
            }
         }

         /// eliminamos todas las restricciones
         if ($delete) {
            /// eliminamos antes las claves ajenas y luego los unique, evita problemas
            foreach ($db_cons as $db_con) {
               if ($db_con['type'] == 'FOREIGN KEY') {
                  $sql .= 'ALTER TABLE ' . $table_name . ' DROP FOREIGN KEY ' . $db_con['name'] . ';';
               }
            }

            foreach ($db_cons as $db_con) {
               if ($db_con['type'] == 'UNIQUE') {
                  $sql .= 'ALTER TABLE ' . $table_name . ' DROP INDEX ' . $db_con['name'] . ';';
               }
            }

            $db_cons = array();
         }
      }

      if ($xml_cons AND ! $delete_only AND FS_FOREIGN_KEYS) {
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
               if (substr($xml_con['consulta'], 0, 11) == 'FOREIGN KEY') {
                  $sql .= 'ALTER TABLE ' . $table_name . ' ADD CONSTRAINT ' . $xml_con['nombre'] . ' ' . $xml_con['consulta'] . ';';
               } else if (substr($xml_con['consulta'], 0, 6) == 'UNIQUE') {
                  $sql .= 'ALTER TABLE ' . $table_name . ' ADD CONSTRAINT ' . $xml_con['nombre'] . ' ' . $xml_con['consulta'] . ';';
               }
            }
         }
      }

      return $this->fix_postgresql($sql);
   }

   /**
    * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
    * @param string $table_name
    * @param type $xml_cols
    * @param type $xml_cons
    * @return string
    */
   public function generate_table($table_name, $xml_cols, $xml_cons) {
      $sql = "CREATE TABLE " . $table_name . " ( ";

      $i = FALSE;
      foreach ($xml_cols as $col) {
         /// añade la coma al final
         if ($i) {
            $sql .= ", ";
         } else {
            $i = TRUE;
         }

         if ($col['tipo'] == 'serial') {
            $sql .= '`' . $col['nombre'] . '` ' . FS_DB_INTEGER . ' NOT NULL AUTO_INCREMENT';
         } else {
            if (strtolower($col['tipo']) == 'integer') {
               /**
                * Desde la pestaña avanzado el panel de control se puede cambiar
                * el tipo de entero a usar en las columnas.
                */
               $col['tipo'] = FS_DB_INTEGER;
            }

            $sql .= '`' . $col['nombre'] . '` ' . $col['tipo'];

            if ($col['nulo'] == 'NO') {
               $sql .= " NOT NULL";
            } else {
               /// es muy importante especificar que la columna permite NULL
               $sql .= " NULL";
            }

            if ($col['defecto'] !== NULL) {
               $sql .= " DEFAULT " . $col['defecto'];
            }
         }
      }

      return $this->fix_postgresql($sql) . ' ' . $this->generate_table_constraints($xml_cons) . ' ) '
              . 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;';
   }

   /**
    * Genera el SQL para establecer las restricciones proporcionadas.
    * @param type $xml_cons
    * @return string
    */
   private function generate_table_constraints($xml_cons) {
      $sql = '';

      if ($xml_cons) {
         foreach ($xml_cons as $res) {
            if (strstr(strtolower($res['consulta']), 'primary key')) {
               $sql .= ', ' . $res['consulta'];
            } else if (FS_FOREIGN_KEYS OR substr($res['consulta'], 0, 11) != 'FOREIGN KEY') {
               $sql .= ', CONSTRAINT ' . $res['nombre'] . ' ' . $res['consulta'];
            }
         }
      }

      return $this->fix_postgresql($sql);
   }

   /**
    * Realiza comprobaciones extra a la tabla.
    * @param string $table_name
    * @return boolean
    */
   public function check_table_aux($table_name) {
      $return = TRUE;

      /// ¿La tabla no usa InnoDB?
      $data = $this->select("SHOW TABLE STATUS FROM `" . FS_DB_NAME . "` LIKE '" . $table_name . "';");
      if ($data) {
         if ($data[0]['Engine'] != 'InnoDB') {
            if (!$this->exec("ALTER TABLE " . $table_name . " ENGINE=InnoDB;")) {
               self::$errors[] = 'Imposible convertir la tabla ' . $table_name . ' a InnoDB.'
                       . ' Imprescindible para FacturaScripts.';
               $return = FALSE;
            }
         }
      }

      return $return;
   }

   /**
    * Elimina código problemático de postgresql.
    * @param string $sql
    * @return string
    */
   private function fix_postgresql($sql) {
      return str_replace(
              array('::character varying', 'without time zone', 'now()', 'CURRENT_TIMESTAMP', 'CURRENT_DATE'), array('', '', "'00:00'", "'" . date('Y-m-d') . " 00:00:00'", date("'Y-m-d'")), $sql
      );
   }

}
