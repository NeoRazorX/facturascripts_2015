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

namespace FacturaScripts\model;

/**
 * Una divisa (moneda) con su símbolo y su tasa de conversión respecto al euro.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class divisa extends \fs_model {

    /**
     * Clave primaria. Varchar (3).
     * @var string 
     */
    public $coddivisa;
    public $descripcion;

    /**
     * Tasa de conversión respecto al euro.
     * @var float
     */
    public $tasaconv;

    /**
     * Tasa de conversión respecto al euro (para compras).
     * @var float
     */
    public $tasaconv_compra;

    /**
     * código ISO 4217 en número: http://en.wikipedia.org/wiki/ISO_4217
     * @var string
     */
    public $codiso;
    public $simbolo;

    public function __construct($d = FALSE) {
        parent::__construct('divisas');
        if ($d) {
            $this->coddivisa = $d['coddivisa'];
            $this->descripcion = $d['descripcion'];
            $this->tasaconv = floatval($d['tasaconv']);
            $this->codiso = $d['codiso'];
            $this->simbolo = $d['simbolo'];

            if ($this->simbolo == '' AND $this->coddivisa == 'EUR') {
                $this->simbolo = '€';
                $this->save();
            }

            if (is_null($d['tasaconv_compra'])) {
                $this->tasaconv_compra = floatval($d['tasaconv']);

                /// forzamos guardar para asegurarnos que siempre hay una tasa para compras
                $this->save();
            } else
                $this->tasaconv_compra = floatval($d['tasaconv_compra']);
        }
        else {
            $this->coddivisa = NULL;
            $this->descripcion = '';
            $this->tasaconv = 1.00;
            $this->tasaconv_compra = 1.00;
            $this->codiso = NULL;
            $this->simbolo = '?';
        }
    }

    public function install() {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (coddivisa,descripcion,tasaconv,tasaconv_compra,codiso,simbolo)"
                . " VALUES ('EUR','EUROS','1','1','978','€')"
                . ",('ARS','PESOS (ARG)','16.684','16.684','32','AR$')"
                . ",('CLP','PESOS (CLP)','704.0227','704.0227','152','CLP$')"
                . ",('COP','PESOS (COP)','3140.6803','3140.6803','170','CO$')"
                . ",('DOP','PESOS DOMINICANOS','49.7618','49.7618','214','RD$')"
                . ",('GBP','LIBRAS ESTERLINAS','0.865','0.865','826','£')"
                . ",('HTG','GOURDES','72.0869','72.0869','322','G')"
                . ",('MXN','PESOS (MXN)','23.3678','23.3678','484','MX$')"
                . ",('PAB','BALBOAS','1.128','1.128','590','B')"
                . ",('PEN','NUEVOS SOLES','3.736','3.736','604','S/.')"
                . ",('USD','DÓLARES EE.UU.','1.129','1.129','840','$')"
                . ",('VEF','BOLÍVARES','10.6492','10.6492','937','Bs')";
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url() {
        return 'index.php?page=admin_divisas';
    }

    /**
     * Devuelve TRUE si esta es la divisa predeterminada de la empresa
     * @return boolean
     */
    public function is_default() {
        return ( $this->coddivisa == $this->default_items->coddivisa() );
    }

    /**
     * Devuelve la divisa con coddivsa = $cod
     * @param string $cod
     * @return boolean|\FacturaScripts\model\divisa
     */
    public function get($cod) {
        $divisa = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE coddivisa = " . $this->var2str($cod) . ";");
        if ($divisa) {
            return new \divisa($divisa[0]);
        }
        
        return FALSE;
    }

    /**
     * Devuelve TRUE si la divisa existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->coddivisa)) {
            return FALSE;
        }
        
        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE coddivisa = " . $this->var2str($this->coddivisa) . ";");
    }

    /**
     * Comprueba los datos de la divisa, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;
        $this->descripcion = $this->no_html($this->descripcion);
        $this->simbolo = $this->no_html($this->simbolo);

        if (!preg_match("/^[A-Z0-9]{1,3}$/i", $this->coddivisa)) {
            $this->new_error_msg("Código de divisa no válido.");
        } else if (isset($this->codiso) AND ! preg_match("/^[A-Z0-9]{1,3}$/i", $this->codiso)) {
            $this->new_error_msg("Código ISO no válido.");
        } else if ($this->tasaconv == 0) {
            $this->new_error_msg('La tasa de conversión no puede ser 0.');
        } else if ($this->tasaconv_compra == 0) {
            $this->new_error_msg('La tasa de conversión para compras no puede ser 0.');
        } else {
            $status = TRUE;
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET descripcion = " . $this->var2str($this->descripcion) .
                        ", tasaconv = " . $this->var2str($this->tasaconv) .
                        ", tasaconv_compra = " . $this->var2str($this->tasaconv_compra) .
                        ", codiso = " . $this->var2str($this->codiso) .
                        ", simbolo = " . $this->var2str($this->simbolo) .
                        "  WHERE coddivisa = " . $this->var2str($this->coddivisa) . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (coddivisa,descripcion,tasaconv,tasaconv_compra,codiso,simbolo)" .
                        " VALUES (" . $this->var2str($this->coddivisa) .
                        "," . $this->var2str($this->descripcion) .
                        "," . $this->var2str($this->tasaconv) .
                        "," . $this->var2str($this->tasaconv_compra) .
                        "," . $this->var2str($this->codiso) .
                        "," . $this->var2str($this->simbolo) . ");";
            }

            return $this->db->exec($sql);
        }
        
        return FALSE;
    }

    /**
     * Elimina esta divisa
     * @return boolean
     */
    public function delete() {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE coddivisa = " . $this->var2str($this->coddivisa) . ";");
    }

    /**
     * Limpiamos la caché
     */
    private function clean_cache() {
        $this->cache->delete('m_divisa_all');
    }

    /**
     * Devuelve un array con todas las divisas.
     * @return \divisa
     */
    public function all() {
        /// leemos la lista de la caché
        $listad = $this->cache->get_array('m_divisa_all');
        if (empty($listad)) {
            /// si no está en caché, leemos de la base de datos
            $data = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY coddivisa ASC;");
            if ($data) {
                foreach ($data as $d) {
                    $listad[] = new \divisa($d);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_divisa_all', $listad);
        }

        return $listad;
    }

}
