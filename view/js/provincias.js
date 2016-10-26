/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

var provincia_list = [
   {value: 'A Coruña'},
   {value: 'Alava'},
   {value: 'Albacete'},
   {value: 'Alicante'},
   {value: 'Almería'},
   {value: 'Asturias'},
   {value: 'Ávila'},
   {value: 'Badajoz'},
   {value: 'Baleares'},
   {value: 'Barcelona'},
   {value: 'Burgos'},
   {value: 'Cáceres'},
   {value: 'Cádiz'},
   {value: 'Cantabria'},
   {value: 'Castellón'},
   {value: 'Ceuta'},
   {value: 'Ciudad Real'},
   {value: 'Córdoba'},
   {value: 'Cuenca'},
   {value: 'Girona'},
   {value: 'Granada'},
   {value: 'Guadalajara'},
   {value: 'Guipuzcoa'},
   {value: 'Huelva'},
   {value: 'Huesca'},
   {value: 'Jaen'},
   {value: 'León'},
   {value: 'Lleida'},
   {value: 'La Rioja'},
   {value: 'Lugo'},
   {value: 'Madrid'},
   {value: 'Málaga'},
   {value: 'Melilla'},
   {value: 'Murcia'},
   {value: 'Navarra'},
   {value: 'Ourense'},
   {value: 'Palencia'},
   {value: 'Las Palmas'},
   {value: 'Pontevedra'},
   {value: 'Salamanca'},
   {value: 'Segovia'},
   {value: 'Sevilla'},
   {value: 'Soria'},
   {value: 'Tarragona'},
   {value: 'Tenerife'},
   {value: 'Teruel'},
   {value: 'Toledo'},
   {value: 'Valencia'},
   {value: 'Valladolid'},
   {value: 'Vizcaya'},
   {value: 'Zamora'},
   {value: 'Zaragoza'},
];

$(document).ready(function() {
   $("#ac_provincia, #ac_provincia2").autocomplete({
      lookup: provincia_list,
   });
});
