<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if( !defined('FS_TMP_NAME') )
{
   define('FS_TMP_NAME', '');
}

if(FS_TMP_NAME != '' AND !file_exists('tmp/'.FS_TMP_NAME) )
{
   if( !file_exists('tmp') )
   {
      mkdir('tmp');
   }
   
   mkdir('tmp/'.FS_TMP_NAME);
}

if( !defined('FS_COMMUNITY_URL') )
{
   define('FS_COMMUNITY_URL', '//www.facturascripts.com/community');
}

if( file_exists('tmp/'.FS_TMP_NAME.'config2.ini') )
{
   $GLOBALS['config2'] = parse_ini_file('tmp/'.FS_TMP_NAME.'config2.ini');
   
   if( !isset($GLOBALS['config2']['cost_is_average']) )
   {
      $GLOBALS['config2']['cost_is_average'] = '1';
   }
   
   if( !isset($GLOBALS['config2']['nf0']) )
   {
      $GLOBALS['config2']['nf0'] = 2;
      $GLOBALS['config2']['nf1'] = '.';
      $GLOBALS['config2']['nf2'] = ' ';
      $GLOBALS['config2']['pos_divisa'] = 'right';
   }
   
   if( !isset($GLOBALS['config2']['homepage']) )
   {
      $GLOBALS['config2']['homepage'] = 'admin_home';
   }
   
   if( !isset($GLOBALS['config2']['check_db_types']) )
   {
      $GLOBALS['config2']['check_db_types'] = 'false';
   }
   
   if( !isset($GLOBALS['config2']['stock_negativo']) )
   {
      $GLOBALS['config2']['stock_negativo'] = 0;
      $GLOBALS['config2']['ventas_sin_stock'] = 1;
   }
   
   if( !isset($GLOBALS['config2']['precio_compra']) )
   {
      $GLOBALS['config2']['precio_compra'] = 'coste';
      $GLOBALS['config2']['ip_whitelist'] = '*';
   }
}
else
{
   $GLOBALS['config2'] = array(
       'zona_horaria' => 'Europe/Madrid',
       'nf0' => 2,
       'nf1' => '.',
       'nf2' => ' ',
       'pos_divisa' => 'right',
       'nfactura_cli' => 1,
       'albaran' => 'albarán',
       'albaranes' => 'albaranes',
       'cifnif' => 'CIF/NIF',
       'pedido' => 'pedido',
       'pedidos' => 'pedidos',
       'presupuesto' => 'presupuesto',
       'presupuestos' => 'presupuestos',
       'provincia' => 'provincia',
       'apartado' => 'apartado',
       'cost_is_average' => 1,
       'precio_compra' => 'coste',
       'homepage' => 'admin_home',
       'check_db_types' => 'false',
       'stock_negativo' => 0,
       'ventas_sin_stock' => 1,
       'ip_whitelist' => '*'
   );
}

foreach($GLOBALS['config2'] as $i => $value)
{
   if($i == 'zona_horaria')
   {
      date_default_timezone_set($value);
   }
   else
   {
      define('FS_'.strtoupper($i), $value);
   }
}

if( !file_exists('plugins') )
{
   mkdir('plugins');
   chmod('plugins', octdec(777));
}

/// Cargamos la lista de plugins activos
$GLOBALS['plugins'] = array();
if( file_exists('tmp/enabled_plugins.list') )
{
   $list = explode(',', file_get_contents('tmp/enabled_plugins.list'));
   if($list)
   {
      foreach($list as $f)
      {
         if( file_exists('plugins/'.$f) )
         {
            $GLOBALS['plugins'][] = $f;
         }
      }
   }
}