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
 * Esta clase sólo sirve para que los modelos sepan que elementos son los
 * predeterminados para la sesión. Pero para guardar los valores hay que usar
 * las funciones fs_controller::save_lo_que_sea()
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_default_items
{

    private static $default_page;
    private static $showing_page;
    private static $codejercicio;
    private static $codalmacen;
    private static $coddivisa;
    private static $codpago;
    private static $codimpuesto;
    private static $codpais;
    private static $codserie;

    public function codejercicio()
    {
        return self::$codejercicio;
    }

    public function set_codejercicio($cod)
    {
        self::$codejercicio = $cod;
    }

    public function codalmacen()
    {
        return self::$codalmacen;
    }

    public function set_codalmacen($cod)
    {
        self::$codalmacen = $cod;
    }

    public function coddivisa()
    {
        return self::$coddivisa;
    }

    public function set_coddivisa($cod)
    {
        self::$coddivisa = $cod;
    }

    public function codpago()
    {
        return self::$codpago;
    }

    public function set_codpago($cod)
    {
        self::$codpago = $cod;
    }

    public function codimpuesto()
    {
        return self::$codimpuesto;
    }

    public function set_codimpuesto($cod)
    {
        self::$codimpuesto = $cod;
    }

    public function codpais()
    {
        return self::$codpais;
    }

    public function set_codpais($cod)
    {
        self::$codpais = $cod;
    }

    public function codserie()
    {
        return self::$codserie;
    }

    public function set_codserie($cod)
    {
        self::$codserie = $cod;
    }

    public function default_page()
    {
        return self::$default_page;
    }

    public function set_default_page($name)
    {
        self::$default_page = $name;
    }

    public function showing_page()
    {
        return self::$showing_page;
    }

    public function set_showing_page($name)
    {
        self::$showing_page = $name;
    }
}
