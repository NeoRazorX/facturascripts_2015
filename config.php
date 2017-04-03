<?php
/*
 * Configuración de la base de datos.
 * type: postgresql o mysql (mysql está en fase experimental).
 * host: la ip del ordenador donde está la base de datos.
 * port: el puerto de la base de datos.
 * name: el nombre de la base de datos.
 * user: el usuario para conectar a la base de datos
 * pass: la contraseña del usuario.
 * history: TRUE si quieres ver todas las consultas que se hacen en cada página.
 */
define('FS_DB_TYPE', 'MYSQL'); /// MYSQL o POSTGRESQL
define('FS_DB_HOST', 'localhost');
define('FS_DB_PORT', '3306'); /// MYSQL -> 3306, POSTGRESQL -> 5432
define('FS_DB_NAME', 'facturasneo');
define('FS_DB_USER', 'facturasneo'); /// MYSQL -> root, POSTGRESQL -> postgres
define('FS_DB_PASS', 'neorazorpa$$');

/*
 * Un directorio de nombre aleatorio para mejorar la seguridad del directorio temporal.
 */
define('FS_TMP_NAME', 'DnZChPs6j3aW5KXRIF2U/');

/*
 * En cada ejecución muestra todas las sentencias SQL utilizadas.
 */
define('FS_DB_HISTORY', FALSE);

/*
 * Habilita el modo demo, para pruebas.
 * Este modo permite hacer login con cualquier usuario y la contraseña demo,
 * además deshabilita el límite de una conexión por usuario.
 */
define('FS_DEMO', FALSE);

/*
 * Configuración de memcache.
 * Host: la ip del servidor donde está memcached.
 * port: el puerto en el que se ejecuta memcached.
 * prefix: prefijo para las claves, por si tienes varias instancias de
 * FacturaScripts conectadas al mismo servidor memcache.
 */

define('FS_CACHE_HOST', 'localhost');
define('FS_CACHE_PORT', '11211');
define('FS_CACHE_PREFIX', '6hiQrfRV_');

/// caducidad (en segundos) de todas las cookies
define('FS_COOKIES_EXPIRE', 7776000);

/// el número de elementos a mostrar en pantalla
define('FS_ITEM_LIMIT', 50);

/// desactiva el poder modificar plugins (añadir, descargar y eliminar)
define('FS_DISABLE_MOD_PLUGINS', FALSE);

/// desactiva el poder añadir plugins manualmente
define('FS_DISABLE_ADD_PLUGINS', FALSE);

/// desactiva el poder eliminar plugins manualmente
define('FS_DISABLE_RM_PLUGINS', FALSE);
