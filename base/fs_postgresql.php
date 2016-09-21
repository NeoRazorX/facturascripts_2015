<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Clase para conectar a PostgreSQL
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_postgresql
{
   /**
    * El enlace con la base de datos.
    * @var type 
    */
   protected static $link;
   
   /**
    * Nº de selects ejecutados.
    * @var type 
    */
   protected static $t_selects;
   
   /**
    * Nº de transacciones ejecutadas.
    * @var type 
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
   
   public function __construct()
   {
      if( !isset(self::$link) )
      {
         self::$t_selects = 0;
         self::$t_transactions = 0;
         self::$history = array();
         self::$errors = array();
      }
   }
   
   /**
    * Devuelve el número de selects ejecutados
    * @return type
    */
   public function get_selects()
   {
      return self::$t_selects;
   }
   
   /**
    * Devuele le número de transacciones realizadas
    * @return type
    */
   public function get_transactions()
   {
      return self::$t_transactions;
   }
   
   /**
    * Devuelve el historial SQL.
    * @return type
    */
   public function get_history()
   {
      return self::$history;
   }
   
   /**
    * Devuelve la lista de errores.
    * @return type
    */
   public function get_errors()
   {
      return self::$errors;
   }
   
   /**
    * Vacía la lista de errores.
    */
   public function clean_errors()
   {
      self::$errors = array();
   }
   
   /**
    * Conecta a la base de datos.
    * @return boolean
    */
   public function connect()
   {
      $connected = FALSE;
      
      if(self::$link)
      {
         $connected = TRUE;
      }
      else if( function_exists('pg_connect') )
      {
         self::$link = pg_connect('host='.FS_DB_HOST.' dbname='.FS_DB_NAME.
                 ' port='.FS_DB_PORT.' user='.FS_DB_USER.' password='.FS_DB_PASS);
         if(self::$link)
         {
            $connected = TRUE;
            
            /// establecemos el formato de fecha para la conexión
            pg_query(self::$link, "SET DATESTYLE TO ISO, DMY;");
         }
      }
      else
         self::$errors[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
      
      return $connected;
   }
   
   /**
    * Devuelve TRUE si se está conectado a la base de datos.
    * @return boolean
    */
   public function connected()
   {
      if(self::$link)
      {
         return TRUE;
      }
      else
         return FALSE;
   }
   
   /**
    * Desconecta de la base de datos.
    * @return boolean
    */
   public function close()
   {
      if(self::$link)
      {
         $retorno = pg_close(self::$link);
         self::$link = NULL;
         return $retorno;
      }
      else
         return TRUE;
   }
   
   /**
    * Devuelve un array con los nombres de las tablas de la base de datos.
    * @return type
    */
   public function list_tables()
   {
      $sql = "SELECT a.relname AS Name FROM pg_class a, pg_user b
         WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
          AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
          AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname))
         ORDER BY a.relname ASC;";
      $resultado = $this->select($sql);
      if($resultado)
      {
         return $resultado;
      }
      else
         return array();
   }
   
   /**
    * Devuelve un array con las columnas de una tabla dada.
    * @param type $table
    * @return type
    */
   public function get_columns($table)
   {
      $sql = "SELECT column_name, data_type, character_maximum_length, column_default, is_nullable
         FROM information_schema.columns
         WHERE table_catalog = '".FS_DB_NAME."' AND table_name = '".$table."'
         ORDER BY column_name ASC;";
      return $this->select($sql);
   }
   
   /**
    * Devuelve una array con las restricciones de una tabla dada:
    * clave primaria, claves ajenas, etc.
    * @param type $table
    * @return type
    */
   public function get_constraints($table)
   {
      $sql = "SELECT c.conname as \"restriccion\", c.contype as \"tipo\"
         FROM pg_class r, pg_constraint c
         WHERE r.oid = c.conrelid AND relname = '".$table."'
         ORDER BY restriccion ASC;";
      return $this->select($sql);
   }
   
   /**
    * Devuelve una array con los indices de una tabla dada.
    * @param type $table
    * @return type
    */
   public function get_indexes($table)
   {
      return $this->select("SELECT indexname as name FROM pg_indexes
         WHERE tablename = '".$table."';");
   }
   
   /**
    * Devuelve un array con los datos de bloqueos en la base de datos.
    * @return type
    */
   public function get_locks()
   {
      return $this->select("SELECT relname,pg_locks.* FROM pg_class,pg_locks
         WHERE relfilenode=relation AND NOT granted;");
   }
   
   /**
    * Devuelve el motor de base de datos y la versión.
    * @return boolean
    */
   public function version()
   {
      if(self::$link)
      {
         $aux = pg_version(self::$link);
         return 'POSTGRESQL '.$aux['server'];
      }
      else
         return FALSE;
   }
   
   /**
    * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
    * o false en caso de fallo.
    * @param type $sql
    * @return type
    */
   public function select($sql)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         else
            self::$errors[] = pg_last_error(self::$link);
         
         self::$t_selects++;
      }
      return $resultado;
   }
   
   /**
    * Ejecuta una sentencia SQL de tipo select, pero con paginación,
    * y devuelve un array con los resultados o false en caso de fallo.
    * Limit es el número de elementos que quieres que devuelva.
    * Offset es el número de resultado desde el que quieres que empiece.
    * @param string $sql
    * @param type $limit
    * @param type $offset
    * @return type
    */
   public function select_limit($sql, $limit = FS_ITEM_LIMIT, $offset = 0)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
         self::$history[] = $sql;
         $filas = pg_query(self::$link, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         else
            self::$errors[] = pg_last_error(self::$link);
         
         self::$t_selects++;
      }
      return $resultado;
   }
   
   /**
    * Ejecuta consultas SQL sobre la base de datos (inserts, updates o deletes).
    * Para hacer selects, mejor usar select() o selec_limit().
    * Por defecto se inicia una transacción, se ejecutan las consultas, y si todo
    * sale bien, se guarda, sino se deshace.
    * Se puede evitar este modo de transacción si se pone false
    * en el parametro transaccion.
    * @param type $sql
    * @param type $transaccion
    * @return boolean
    */
   public function exec($sql, $transaccion = TRUE)
   {
      $resultado = FALSE;
      if(self::$link)
      {
         self::$history[] = $sql;
         
         if($transaccion)
         {
            pg_query(self::$link, 'BEGIN TRANSACTION;');
         }
         
         $aux = pg_query(self::$link, $sql);
         if($aux)
         {
            pg_free_result($aux);
            
            if($transaccion)
            {
               pg_query(self::$link, 'COMMIT;');
            }
            
            $resultado = TRUE;
         }
         else
         {
            self::$errors[] = pg_last_error(self::$link).'. La secuencia ocupa la posición '.count(self::$history);
            
            if($transaccion)
            {
               pg_query(self::$link, 'ROLLBACK;');
            }
         }
         
         self::$t_transactions++;
      }
      return $resultado;
   }
   
   /**
    * Inicia una transacción SQL.
    */
   public function begin_transaction()
   {
      if(self::$link)
      {
         pg_query(self::$link, 'BEGIN TRANSACTION;');
      }
   }
   
   /**
    * Guarda los cambios de una transacción SQL.
    */
   public function commit()
   {
      if(self::$link)
      {
         pg_query(self::$link, 'COMMIT;');
      }
   }
   
   /**
    * Deshace los cambios de una transacción SQL.
    */
   public function rollback()
   {
      if(self::$link)
      {
         pg_query(self::$link, 'ROLLBACK;');
      }
   }
   
   /**
    * Devuelve TRUE si la secuancia solicitada existe.
    * @param type $seq
    * @return type
    */
   private function sequence_exists($seq)
   {
      return $this->select("SELECT * FROM pg_class where relname = '".$seq."';");
   }
   
   /**
    * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
    * @return boolean
    */
   public function lastval()
   {
      $aux = $this->select('SELECT lastval() as num;');
      if($aux)
      {
         return $aux[0]['num'];
      }
      else
         return FALSE;
   }
   
   /**
    * Escapa las comillas de la cadena de texto.
    * @param type $s
    * @return type
    */
   public function escape_string($s)
   {
      if(self::$link)
      {
         return pg_escape_string(self::$link, $s);
      }
      else
      {
         return $s;
      }
   }
   
   /**
    * Devuelve el estilo de fecha del motor de base de datos.
    * @return string
    */
   public function date_style()
   {
      return 'd-m-Y';
   }
   
   /**
    * Devuelve el SQL necesario para convertir la columna a entero.
    * @param type $col
    * @return type
    */
   public function sql_to_int($col)
   {
      return $col.'::integer';
   }
   
   /**
    * Compara dos arrays de columnas, devuelve una sentencia SQL
    * en caso de encontrar diferencias.
    * @param type $table_name
    * @param type $xml_cols
    * @param type $columnas
    * @return string
    */
   public function compare_columns($table_name, $xml_cols, $columnas)
   {
      $consulta = '';
      
      foreach($xml_cols as $col)
      {
         $encontrada = FALSE;
         if($columnas)
         {
            foreach($columnas as $col2)
            {
               if($col2['column_name'] == $col['nombre'])
               {
                  if( !$this->compare_data_types($col2, $col['tipo']) )
                  {
                     $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" TYPE '.$col['tipo'].';';
                  }
                  
                  if($col2['column_default'] != $col['defecto'])
                  {
                     if( is_null($col['defecto']) )
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" DROP DEFAULT;';
                     }
                     else
                     {
                        $this->default2check_sequence($table_name, $col['defecto'], $col['nombre']);
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" SET DEFAULT '.$col['defecto'].';';
                     }
                  }
                  
                  if($col2['is_nullable'] != $col['nulo'])
                  {
                     if($col['nulo'] == 'YES')
                     {
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" DROP NOT NULL;';
                     }
                     else
                        $consulta .= 'ALTER TABLE '.$table_name.' ALTER COLUMN "'.$col['nombre'].'" SET NOT NULL;';
                  }
                  
                  $encontrada = TRUE;
                  break;
               }
            }
         }
         if(!$encontrada)
         {
            $consulta .= 'ALTER TABLE '.$table_name.' ADD COLUMN "'.$col['nombre'].'" '.$col['tipo'];
            
            if($col['defecto'])
            {
               $consulta .= ' DEFAULT '.$col['defecto'];
            }
            
            if($col['nulo'] == 'NO')
            {
               $consulta .= ' NOT NULL';
            }
            
            $consulta .= ';';
         }
      }
      
      return $consulta;
   }
   
   /**
    * Compara los tipos de datos de una columna. Devuelve TRUE si son iguales.
    * @param type $v1
    * @param type $v2
    * @return boolean
    */
   private function compare_data_types($v1, $v2)
   {
      if(FS_CHECK_DB_TYPES != 1)
      {
         return TRUE;
      }
      else if( strtolower($v2) == 'serial' )
      {
         return TRUE;
      }
      else if( substr($v1['data_type'], 0, 4) == 'time' AND substr($v2, 0, 4) == 'time' )
      {
         return TRUE;
      }
      else if($v1['data_type'] == $v2)
      {
         return TRUE;
      }
      else if($v1['data_type'].'('.$v1['character_maximum_length'].')' == $v2)
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }
   
   /**
    * A partir del campo default del xml de una tabla
    * comprueba si se refiere a una secuencia, y si es así
    * comprueba la existencia de la secuencia. Si no la encuentra
    * la crea.
    * @param type $table_name
    * @param type $default
    * @param type $colname
    */
   private function default2check_sequence($table_name, $default, $colname)
   {
      /// ¿Se refiere a una secuencia?
      if( strtolower(substr($default, 0, 9)) == "nextval('" )
      {
         $aux = explode("'", $default);
         if( count($aux) == 3 )
         {
            /// ¿Existe esa secuencia?
            if( !$this->sequence_exists($aux[1]) )
            {
               /// ¿En qué número debería empezar esta secuencia?
               $num = 1;
               $aux_num = $this->select("SELECT MAX(".$colname."::integer) as num FROM ".$table_name.";");
               if($aux_num)
               {
                  $num += intval($aux_num[0]['num']);
               }
               
               $this->exec("CREATE SEQUENCE ".$aux[1]." START ".$num.";");
            }
         }
      }
   }
   
   /**
    * Compara dos arrays de restricciones, devuelve un array de sentencias SQL
    * en caso de encontrar diferencias.
    * @param type $table_name
    * @param type $c_nuevas
    * @param type $c_old
    * @param type $solo_eliminar
    * @return string
    */
   public function compare_constraints($table_name, $c_nuevas, $c_old, $solo_eliminar = FALSE)
   {
      $consulta = '';
      
      if($c_old)
      {
         /// comprobamos una a una las viejas
         foreach($c_old as $col)
         {
            $encontrado = FALSE;
            if($c_nuevas)
            {
               foreach($c_nuevas as $col2)
               {
                  if($col['restriccion'] == $col2['nombre'])
                  {
                     $encontrado = TRUE;
                     break;
                  }
               }
            }
            
            if(!$encontrado)
            {
               /// eliminamos la restriccion
               $consulta .= "ALTER TABLE ".$table_name." DROP CONSTRAINT ".$col['restriccion'].";";
            }
         }
      }
      
      if($c_nuevas AND !$solo_eliminar)
      {
         /// comprobamos una a una las nuevas
         foreach($c_nuevas as $col)
         {
            $encontrado = FALSE;
            if($c_old)
            {
               foreach($c_old as $col2)
               {
                  if($col['nombre'] == $col2['restriccion'])
                  {
                     $encontrado = TRUE;
                     break;
                  }
               }
            }
            
            if(!$encontrado)
            {
               /// añadimos la restriccion
               $consulta .= "ALTER TABLE ".$table_name." ADD CONSTRAINT ".$col['nombre']." ".$col['consulta'].";";
            }
         }
      }
      
      return $consulta;
   }
   
   /**
    * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
    * @param type $table_name
    * @param type $xml_columnas
    * @param type $xml_restricciones
    * @return type
    */
   public function generate_table($table_name, $xml_columnas, $xml_restricciones)
   {
      $consulta = 'CREATE TABLE '.$table_name.' (';
      
      $i = FALSE;
      foreach($xml_columnas as $col)
      {
         /// añade la coma al final
         if($i)
         {
            $consulta .= ', ';
         }
         else
            $i = TRUE;
         
         $consulta .= '"'.$col['nombre'].'" '.$col['tipo'];
         
         if($col['nulo'] == 'NO')
         {
            $consulta .= ' NOT NULL';
         }
         
         if($col['defecto'] AND !in_array($col['tipo'], array('serial', 'bigserial')))
         {
            $consulta .= ' DEFAULT '.$col['defecto'];
         }
      }
      
      return $consulta.' ); '.$this->compare_constraints($table_name, $xml_restricciones, FALSE);
   }
   
   /**
    * Debería realizar comprobaciones extra, pero en PostgreSQL no es necesario.
    * @param type $table_name
    * @return boolean
    */
   public function check_table_aux($table_name)
   {
      return TRUE;
   }
}
