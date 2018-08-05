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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of fs_divisa_tools
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_divisa_tools
{

    /**
     *
     * @var string
     */
    private static $coddivisa;

    /**
     *
     * @var array
     */
    private static $divisas;

    public function __construct($coddivisa = '')
    {
        if (!isset(self::$divisa_model)) {
            $divisa_model = new divisa();
            self::$divisas = $divisa_model->all();
            self::$coddivisa = $coddivisa;
        }
    }

    /**
     * Devuelve el símbolo de divisa predeterminado
     * o bien el símbolo de la divisa seleccionada.
     * @param string $coddivisa
     * @return string
     */
    public function simbolo_divisa($coddivisa = FALSE)
    {
        if ($coddivisa === FALSE) {
            $coddivisa = self::$coddivisa;
        }

        foreach (self::$divisas as $divisa) {
            if ($divisa->coddivisa == $coddivisa) {
                return $divisa->simbolo;
            }
        }

        return '?';
    }

    /**
     * Devuelve un string con el precio en el formato predefinido y con la
     * divisa seleccionada (o la predeterminada).
     * @param float $precio
     * @param string $coddivisa
     * @param string $simbolo
     * @param integer $dec nº de decimales
     * @return string
     */
    public function show_precio($precio = 0, $coddivisa = FALSE, $simbolo = TRUE, $dec = FS_NF0)
    {
        if ($coddivisa === FALSE) {
            $coddivisa = self::$coddivisa;
        }

        if (FS_POS_DIVISA == 'right') {
            if ($simbolo) {
                return number_format($precio, $dec, FS_NF1, FS_NF2) . ' ' . $this->simbolo_divisa($coddivisa);
            }

            return number_format($precio, $dec, FS_NF1, FS_NF2) . ' ' . $coddivisa;
        }

        if ($simbolo) {
            return $this->simbolo_divisa($coddivisa) . number_format($precio, $dec, FS_NF1, FS_NF2);
        }

        return $coddivisa . ' ' . number_format($precio, $dec, FS_NF1, FS_NF2);
    }

    /**
     * Devuelve un string con el número en el formato de número predeterminado.
     * @param float $num
     * @param integer $decimales
     * @param boolean $js
     * @return string
     */
    public function show_numero($num = 0, $decimales = FS_NF0, $js = FALSE)
    {
        if ($js) {
            return number_format($num, $decimales, '.', '');
        }

        return number_format($num, $decimales, FS_NF1, FS_NF2);
    }

    /**
     * Convierte el precio en euros a la divisa preterminada de la empresa.
     * Por defecto usa las tasas de conversión actuales, pero si se especifica
     * coddivisa y tasaconv las usará.
     * @param float $precio
     * @param string $coddivisa
     * @param float $tasaconv
     * @return float
     */
    public function euro_convert($precio, $coddivisa = NULL, $tasaconv = NULL)
    {
        if (self::$coddivisa == 'EUR') {
            return $precio;
        }

        if ($coddivisa !== NULL && $tasaconv !== NULL) {
            if (self::$coddivisa == $coddivisa) {
                return $precio * $tasaconv;
            }

            $original = $precio * $tasaconv;
            return $this->divisa_convert($original, $coddivisa, self::$coddivisa);
        }

        return $this->divisa_convert($precio, 'EUR', self::$coddivisa);
    }

    /**
     * Convierte un precio de la divisa_desde a la divisa especificada
     * @param float $precio
     * @param string $coddivisa_desde
     * @param string $coddivisa
     * @return float
     */
    public function divisa_convert($precio, $coddivisa_desde, $coddivisa)
    {
        if ($coddivisa_desde != $coddivisa) {
            $divisa = $divisa_desde = FALSE;

            /// buscamos las divisas en la lista
            foreach (self::$divisas as $div) {
                if ($div->coddivisa == $coddivisa) {
                    $divisa = $div;
                } else if ($div->coddivisa == $coddivisa_desde) {
                    $divisa_desde = $div;
                }
            }

            if ($divisa && $divisa_desde) {
                $precio = $precio / $divisa_desde->tasaconv * $divisa->tasaconv;
            }
        }

        return $precio;
    }
}
