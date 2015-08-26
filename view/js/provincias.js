/*  
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved. 
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
   {value: 'Ourensa'},
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
