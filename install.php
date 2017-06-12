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

$nombre_archivo = "config.php";
error_reporting(E_ALL);
$errors = array();
$errors2 = array();
$db_type = 'MYSQL';
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'facturascripts';
$db_user = '';

function random_string($length = 20) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function guarda_config($nombre_archivo) {
    $archivo = fopen($nombre_archivo, "w");
    if ($archivo) {
        fwrite($archivo, "<?php\n");
        fwrite($archivo, "/*\n");
        fwrite($archivo, " * Configuración de la base de datos.\n");
        fwrite($archivo, " * type: postgresql o mysql (mysql está en fase experimental).\n");
        fwrite($archivo, " * host: la ip del ordenador donde está la base de datos.\n");
        fwrite($archivo, " * port: el puerto de la base de datos.\n");
        fwrite($archivo, " * name: el nombre de la base de datos.\n");
        fwrite($archivo, " * user: el usuario para conectar a la base de datos\n");
        fwrite($archivo, " * pass: la contraseña del usuario.\n");
        fwrite($archivo, " * history: TRUE si quieres ver todas las consultas que se hacen en cada página.\n");
        fwrite($archivo, " */\n");
        fwrite($archivo, "define('FS_DB_TYPE', '" . filter_input(INPUT_POST, 'db_type') . "'); /// MYSQL o POSTGRESQL\n");
        fwrite($archivo, "define('FS_DB_HOST', '" . filter_input(INPUT_POST, 'db_host') . "');\n");
        fwrite($archivo, "define('FS_DB_PORT', '" . filter_input(INPUT_POST, 'db_port') . "'); /// MYSQL -> 3306, POSTGRESQL -> 5432\n");
        fwrite($archivo, "define('FS_DB_NAME', '" . filter_input(INPUT_POST, 'db_name') . "');\n");
        fwrite($archivo, "define('FS_DB_USER', '" . filter_input(INPUT_POST, 'db_user') . "'); /// MYSQL -> root, POSTGRESQL -> postgres\n");
        fwrite($archivo, "define('FS_DB_PASS', '" . filter_input(INPUT_POST, 'db_pass') . "');\n");

        if (filter_input(INPUT_POST, 'db_type') == 'MYSQL' AND filter_input(INPUT_POST, 'mysql_socket') != '') {
            fwrite($archivo, "ini_set('mysqli.default_socket', '" . filter_input(INPUT_POST, 'mysql_socket') . "');\n");
        }

        fwrite($archivo, "\n");
        fwrite($archivo, "/*\n");
        fwrite($archivo, " * Un directorio de nombre aleatorio para mejorar la seguridad del directorio temporal.\n");
        fwrite($archivo, " */\n");
        fwrite($archivo, "define('FS_TMP_NAME', '" . random_string(20) . "/');\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/*\n");
        fwrite($archivo, " * En cada ejecución muestra todas las sentencias SQL utilizadas.\n");
        fwrite($archivo, " */\n");
        fwrite($archivo, "define('FS_DB_HISTORY', FALSE);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/*\n");
        fwrite($archivo, " * Habilita el modo demo, para pruebas.\n");
        fwrite($archivo, " * Este modo permite hacer login con cualquier usuario y la contraseña demo,\n");
        fwrite($archivo, " * además deshabilita el límite de una conexión por usuario.\n");
        fwrite($archivo, " */\n");
        fwrite($archivo, "define('FS_DEMO', FALSE);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/*\n");
        fwrite($archivo, " * Configuración de memcache.\n");
        fwrite($archivo, " * Host: la ip del servidor donde está memcached.\n");
        fwrite($archivo, " * port: el puerto en el que se ejecuta memcached.\n");
        fwrite($archivo, " * prefix: prefijo para las claves, por si tienes varias instancias de\n");
        fwrite($archivo, " * FacturaScripts conectadas al mismo servidor memcache.\n");
        fwrite($archivo, " */\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "define('FS_CACHE_HOST', '" . filter_input(INPUT_POST, 'cache_host') . "');\n");
        fwrite($archivo, "define('FS_CACHE_PORT', '" . filter_input(INPUT_POST, 'cache_port') . "');\n");
        fwrite($archivo, "define('FS_CACHE_PREFIX', '" . filter_input(INPUT_POST, 'cache_prefix') . "');\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/// caducidad (en segundos) de todas las cookies\n");
        fwrite($archivo, "define('FS_COOKIES_EXPIRE', 604800);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/// el número de elementos a mostrar en pantalla\n");
        fwrite($archivo, "define('FS_ITEM_LIMIT', 50);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/// desactiva el poder modificar plugins (añadir, descargar y eliminar)\n");
        fwrite($archivo, "define('FS_DISABLE_MOD_PLUGINS', FALSE);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/// desactiva el poder añadir plugins manualmente\n");
        fwrite($archivo, "define('FS_DISABLE_ADD_PLUGINS', FALSE);\n");
        fwrite($archivo, "\n");
        fwrite($archivo, "/// desactiva el poder eliminar plugins manualmente\n");
        fwrite($archivo, "define('FS_DISABLE_RM_PLUGINS', FALSE);\n");

        if (filter_input(INPUT_POST, 'proxy_type')) {
            fwrite($archivo, "\n");
            fwrite($archivo, "define('FS_PROXY_TYPE', '" . filter_input(INPUT_POST, 'proxy_type') . "');\n");
            fwrite($archivo, "define('FS_PROXY_HOST', '" . filter_input(INPUT_POST, 'proxy_host') . "');\n");
            fwrite($archivo, "define('FS_PROXY_PORT', '" . filter_input(INPUT_POST, 'proxy_port') . "');\n");
        }

        fclose($archivo);

        header("Location: index.php");
        exit();
    } else {
        $errors[] = "permisos";
    }
}

if (file_exists('config.php')) {
    header('Location: index.php');
} else if (floatval(substr(phpversion(), 0, 3)) < 5.3) {
    $errors[] = 'php';
} else if (floatval('3,1') >= floatval('3.1')) {
    $errors[] = "floatval";
    $errors2[] = 'El separador de decimales de esta versión de PHP no es el punto,'
            . ' como sucede en las instalaciones estándar. Debes corregirlo.';
} else if (!function_exists('mb_substr')) {
    $errors[] = "mb_substr";
} else if (!extension_loaded('simplexml')) {
    $errors[] = "simplexml";
    $errors2[] = 'No se encuentra la extensión simplexml en tu instalación de PHP.'
            . ' Debes instalarla o activarla.';
    $errors2[] = 'Linux: instala el paquete <b>php-xml</b> y reinicia el Apache.';
} else if (!extension_loaded('openssl')) {
    $errors[] = "openssl";
} else if (!extension_loaded('zip')) {
    $errors[] = "ziparchive";
} else if (!is_writable(getcwd())) {
    $errors[] = "permisos";
} else if (filter_input(INPUT_POST, 'db_type')) {
    if (filter_input(INPUT_POST, 'db_type') == 'MYSQL') {
        if (class_exists('mysqli')) {
            if (filter_input(INPUT_POST, 'mysql_socket') != '') {
                ini_set('mysqli.default_socket', filter_input(INPUT_POST, 'mysql_socket'));
            }

            // Omitimos el valor del nombre de la BD porque lo comprobaremos más tarde
            $connection = @new mysqli(filter_input(INPUT_POST, 'db_host'), filter_input(INPUT_POST, 'db_user'), filter_input(INPUT_POST, 'db_pass'), "", intval(filter_input(INPUT_POST, 'db_port')));
            if ($connection->connect_error) {
                $errors[] = "db_mysql";
                $errors2[] = $connection->connect_error;
            } else {
                // Comprobamos que la BD exista, de lo contrario la creamos
                $db_selected = mysqli_select_db($connection, filter_input(INPUT_POST, 'db_name'));
                if ($db_selected) {
                    guarda_config($nombre_archivo);
                } else {
                    $sqlCrearBD = "CREATE DATABASE `" . filter_input(INPUT_POST, 'db_name') . "`;";
                    if (mysqli_query($connection, $sqlCrearBD)) {
                        guarda_config($nombre_archivo);
                    } else {
                        $errors[] = "db_mysql";
                        $errors2[] = mysqli_error($connection);
                    }
                }
            }
        } else {
            $errors[] = "db_mysql";
            $errors2[] = 'No tienes instalada la extensión de PHP para MySQL.';
        }
    } else if (filter_input(INPUT_POST, 'db_type') == 'POSTGRESQL') {
        if (function_exists('pg_connect')) {
            $connection = @pg_connect('host=' . filter_input(INPUT_POST, 'db_host') . ' port=' . filter_input(INPUT_POST, 'db_port') . ' user=' . filter_input(INPUT_POST, 'db_user') . ' password=' . filter_input(INPUT_POST, 'db_pass'));
            if ($connection) {
                // Comprobamos que la BD exista, de lo contrario la creamos
                $connection2 = @pg_connect('host=' . filter_input(INPUT_POST, 'db_host') . ' port=' . filter_input(INPUT_POST, 'db_port') . ' dbname=' . filter_input(INPUT_POST, 'db_name')
                                . ' user=' . filter_input(INPUT_POST, 'db_user') . ' password=' . filter_input(INPUT_POST, 'db_pass'));

                if ($connection2) {
                    guarda_config($nombre_archivo);
                } else {
                    $sqlCrearBD = 'CREATE DATABASE "' . filter_input(INPUT_POST, 'db_name') . '";';
                    if (pg_query($connection, $sqlCrearBD)) {
                        guarda_config($nombre_archivo);
                    } else {
                        $errors[] = "db_postgresql";
                        $errors2[] = 'Error al crear la base de datos.';
                    }
                }
            } else {
                $errors[] = "db_postgresql";
                $errors2[] = 'No se puede conectar a la base de datos. Revisa los datos de usuario y contraseña.';
            }
        } else {
            $errors[] = "db_postgresql";
            $errors2[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
        }
    }

    $db_type = filter_input(INPUT_POST, 'db_type');
    $db_host = filter_input(INPUT_POST, 'db_host');
    $db_port = filter_input(INPUT_POST, 'db_port');
    $db_name = filter_input(INPUT_POST, 'db_name');
    $db_user = filter_input(INPUT_POST, 'db_user');
}

$system_info = 'facturascripts: ' . file_get_contents('VERSION') . "\n";
$system_info .= 'os: ' . php_uname() . "\n";
$system_info .= 'php: ' . phpversion() . "\n";

if (isset($_SERVER['REQUEST_URI'])) {
    $system_info .= 'url: ' . $_SERVER['REQUEST_URI'] . "\n------";
}
foreach ($errors as $e) {
    $system_info .= "\n" . $e;
}

$system_info = str_replace('"', "'", $system_info);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>FacturaScripts</title>
        <meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/LGPL." />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="shortcut icon" href="view/img/favicon.ico" />
        <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
        <link rel="stylesheet" href="view/css/font-awesome.min.css" />
        <link rel="stylesheet" href="view/css/datepicker.css" />
        <link rel="stylesheet" href="view/css/custom.css" />
        <script type="text/javascript" src="view/js/jquery.min.js"></script>
        <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="view/js/bootstrap-datepicker.js" charset="UTF-8"></script>
        <script type="text/javascript" src="view/js/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="view/js/base.js"></script>
        <script type="text/javascript" src="view/js/jquery.validate.min.js"></script>
    </head>
    <body>
        <nav class="navbar navbar-default" role="navigation" style="margin: 0px;">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Menú</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="index.php">FacturaScripts</a>
                </div>
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <span class="hidden-xs">
                                    <i class="fa fa-question-circle" aria-hidden="true"></i>&nbsp; Ayuda
                                </span>
                                <span class="visible-xs">Ayuda</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="https://www.facturascripts.com/documentacion" target="_blank">
                                        <i class="fa fa-book" aria-hidden="true"></i>&nbsp; Documentación
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.facturascripts.com/contacto" target="_blank">
                                        <i class="fa fa-shield" aria-hidden="true"></i>&nbsp; Soporte oficial
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.facturascripts.com/errores" target="_blank">
                                        <i class="fa fa-bug" aria-hidden="true"></i>&nbsp; Errores
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="#" id="b_feedback">
                                        <i class="fa fa-edit" aria-hidden="true"></i>&nbsp; Informar de error...
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <form name="f_feedback" action="https://www.facturascripts.com/comm3/index.php?page=community_feedback" method="post" target="_blank" class="form" role="form">
            <input type="hidden" name="feedback_info" value="<?php echo $system_info; ?>"/>
            <input type="hidden" name="feedback_type" value="error"/>
            <div class="modal" id="modal_feedback">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">
                                <i class="fa fa-edit" aria-hidden="true"></i> Informar de error...
                            </h4>
                            <p class="help-block">
                                Usa este formulario para informarnos de cualquier error o duda que hayas encontrado.
                                Para facilitarnos el trabajo este formulario también nos informa de la versión de
                                FacturaScripts que usas, versión de php, etc...
                            </p>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <textarea class="form-control" name="feedback_text" rows="6" placeholder="Detalla tu duda o problema..."></textarea>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope" aria-hidden="true"></i>
                                    </span>
                                    <input type="email" class="form-control" name="feedback_email" placeholder="Introduce tu email"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-send" aria-hidden="true"></i>&nbsp; Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script type="text/javascript">
            function change_db_type() {
                if (document.f_configuracion_inicial.db_type.value == 'POSTGRESQL')
                {
                    document.f_configuracion_inicial.db_port.value = '5432';
                    if (document.f_configuracion_inicial.db_user.value == '')
                    {
                        document.f_configuracion_inicial.db_user.value = 'postgres';
                    }
                    $("#mysql_socket").hide();
                } else
                {
                    document.f_configuracion_inicial.db_port.value = '3306';
                    $("#mysql_socket").show();
                }
            }
            $(document).ready(function () {
                $("#f_configuracion_inicial").validate({
                    rules: {
                        db_type: {required: false},
                        db_host: {required: true, minlength: 2},
                        db_port: {required: true, minlength: 2},
                        db_name: {required: true, minlength: 2},
                        db_user: {required: true, minlength: 2},
                        db_pass: {required: false},
                        cache_host: {required: true, minlength: 2},
                        cache_port: {required: true, minlength: 2},
                        cache_prefix: {required: false, minlength: 2}
                    },
                    messages: {
                        db_host: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        },
                        db_port: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        },
                        db_name: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        },
                        db_user: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        },
                        cache_host: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        },
                        cache_port: {
                            required: "El campo es obligatorio.",
                            minlength: $.validator.format("Requiere mínimo {0} carácteres!")
                        }
                    }
                });
            });
        </script>

        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-header">
                        <h1>
                            <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                            Bienvenido al instalador de FacturaScripts
                            <small><?php echo file_get_contents('VERSION'); ?></small>
                        </h1>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <?php
                    foreach ($errors as $err) {
                        if ($err == 'permisos') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    Permisos de escritura:
                                </div>
                                <div class="panel-body">
                                    <p>
                                        La carpeta de FacturaScripts no tiene permisos de escritura.
                                        Estos permisos son necesarios para el sistema de plantillas,
                                        instalar plugins, actualizaciones, etc...
                                    </p>
                                    <h3>
                                        <i class="fa fa-linux" aria-hidden="true"></i> Linux
                                    </h3>
                                    <pre>sudo chmod -R o+w <?php echo dirname(__FILE__); ?></pre>
                                    <p class="help-block">
                                        Este comando soluciona el problema en el 95% de los casos, pero
                                        puedes optar por una solución más restrictiva, simplemente es necesario
                                        que Apache (o PHP) pueda leer y escribir en la carpeta.
                                    </p>
                                    <h3>
                                        <i class="fa fa-lock" aria-hidden="true"></i> Fedora / CentOS / Red Hat
                                    </h3>
                                    <p class="help-block">
                                        La configuración por defecto de estas distribuciones, en concreto SELinux,
                                        bloquea cualquier intento de comprobar si la carpeta tiene permisos de escritura.
                                        Desactiva o modifica la configuración de SELinux para el correcto funcionamiento
                                        de FacturaScripts.
                                    </p>
                                    <h3>
                                        <i class="fa fa-globe" aria-hidden="true"></i> Hosting
                                    </h3>
                                    <p class="help-block">
                                        Intenta dar permisos de escritura desde el cliente <b>FTP</b> o desde el <b>cPanel</b>.
                                    </p>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'php') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    Versión de PHP obsoleta:
                                </div>
                                <div class="panel-body">
                                    <p>
                                        FacturaScripts necesita PHP <b>5.3</b> o superior.
                                        Tú estás usando la versión <b><?php echo phpversion() ?></b>.
                                    </p>
                                    <h3>Soluciones:</h3>
                                    <ul>
                                        <li>
                                            <p class="help-block">
                                                Muchos hostings ofrecen <b>varias versiones de PHP</b>. Ve al panel de control
                                                de tu hosting y selecciona la versión de PHP más alta.
                                            </p>
                                        </li>
                                        <li>
                                            <p class="help-block">
                                                Busca un proveedor de hosting más completo, que son la mayoría. Mira en nuestra sección de
                                                <a href="https://www.facturascripts.com/descargar?nube=TRUE" target="_blank">Hostings recomendados</a>.
                                            </p>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'mb_substr') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    No se encuentra la función mb_substr():
                                </div>
                                <div class="panel-body">
                                    <p>
                                        FacturaScripts necesita la extensión mbstring para poder trabajar con caracteres
                                        no europeos (chinos, coreanos, japonenes y rusos).
                                    </p>
                                    <h3>
                                        <i class="fa fa-linux" aria-hidden="true"></i> Linux
                                    </h3>
                                    <p class="help-block">
                                        Instala el paquete <b>php-mbstring</b> y reinicia el Apache.
                                    </p>
                                    <h3>
                                        <i class="fa fa-globe" aria-hidden="true"></i> Hosting
                                    </h3>
                                    <p class="help-block">
                                        Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                                        Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                                        Mira en nuestra sección de
                                        <a href="https://www.facturascripts.com/descargar?nube=TRUE" target="_blank">Hostings recomendados</a>.
                                    </p>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'openssl') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    No se encuentra la extensión OpenSSL:
                                </div>
                                <div class="panel-body">
                                    <p>
                                        FacturaScripts necesita la extensión OpenSSL para poder descargar plugins,
                                        actualizaciones y enviar emails.
                                    </p>
                                    <h3>
                                        <i class="fa fa-globe" aria-hidden="true"></i> Hosting
                                    </h3>
                                    <p class="help-block">
                                        Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                                        Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                                        Mira en nuestra sección de
                                        <a href="https://www.facturascripts.com/descargar?nube=TRUE" target="_blank">Hostings recomendados</a>.
                                    </p>
                                    <h3>
                                        <i class="fa fa-windows" aria-hidden="true"></i> Windows
                                    </h3>
                                    <p class="help-block">
                                        Ofrecemos una versión de FacturaScripts para Windows <b>con todo</b> el software necesario
                                        (como OpenSSL) ya incluido de serie. Puedes encontrala en nuestra sección de
                                        <a href="https://www.facturascripts.com/descargar?windows=TRUE" target="_blank">descargas</a>.
                                        Si decides utilizar <b>un empaquetado distinto</b>, y este no incluye lo necesario, deberás
                                        buscar ayuda en los foros o el soporte de los creadores de ese empaquetado.
                                    </p>
                                    <h3>
                                        <i class="fa fa-linux" aria-hidden="true"></i> Linux
                                    </h3>
                                    <p class="help-block">
                                        Es muy raro que una instalación propia de PHP en Linux no incluya OpenSSL.
                                        Intenta instalar el paquete <b>php-openssl</b> con tu gestor de paquetes
                                        y reinicia el Apache. Para más información consulta la ayuda o los foros
                                        de la distribución Linux que utilices.
                                    </p>
                                    <h3>
                                        <i class="fa fa-apple" aria-hidden="true"></i> Mac
                                    </h3>
                                    <p class="help-block">
                                        Es raro que un empaquetado Apache+PHP+MySQL para Mac no incluya OpenSSL.
                                        Nosotros ofrecemos varios empaquetados con todo lo necesario en nuestra sección de
                                        <a href="https://www.facturascripts.com/descargar?mac=TRUE" target="_blank">descargas</a>.
                                    </p>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'ziparchive') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    No se encuentra la extensión ZipArchive:
                                </div>
                                <div class="panel-body">
                                    <p>
                                        FacturaScripts necesita la extensión ZipArchive para poder
                                        descomprimir plugins y actualizaciones.
                                    </p>
                                    <h3>
                                        <i class="fa fa-linux" aria-hidden="true"></i> Linux
                                    </h3>
                                    <p class="help-block">Instala el paquete <b>php-zip</b> y reinicia el Apache.</p>
                                    <h3>
                                        <i class="fa fa-globe" aria-hidden="true"></i> Hosting
                                    </h3>
                                    <p class="help-block">
                                        Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                                        Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                                        Mira en nuestra sección de
                                        <a href="https://www.facturascripts.com/descargar?nube=TRUE" target="_blank">Hostings recomendados</a>.
                                    </p>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'db_mysql') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    Acceso a base de datos MySQL:
                                </div>
                                <div class="panel-body">
                                    <ul>
                                        <?php
                                        foreach ($errors2 as $err2)
                                            echo "<li>" . $err2 . "</li>";
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                        } else if ($err == 'db_postgresql') {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    Acceso a base de datos PostgreSQL:
                                </div>
                                <div class="panel-body">
                                    <ul>
                                        <?php
                                        foreach ($errors2 as $err2)
                                            echo "<li>" . $err2 . "</li>";
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    Error:
                                </div>
                                <div class="panel-body">
                                    <ul>
                                        <?php
                                        if ($errors2) {
                                            foreach ($errors2 as $err2) {
                                                echo "<li>" . $err2 . "</li>";
                                            }
                                        } else {
                                            echo "<li>Error desconocido.</li>";
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <b>Antes de empezar...</b>
                    <p class="help-block">
                        Recuerda que tienes el <b>menú de ayuda</b> arriba a la derecha. Si encuentras cualquier problema,
                        haz clic en <b>informar de error...</b> y describe tu duda, sugerencia o el error que has encontrado.
                        No sabemos hacer software perfecto, pero con tu ayuda nos podemos acercar cada vez más ;-)
                        <br/><br/>
                        Y recuerda que tienes una sección especialmente dedicada a la <b>instalación</b> en nuestra
                        documentación oficial:
                    </p>
                    <a href="https://www.facturascripts.com/documentacion#instalacion" target="_blank" class="btn btn-sm btn-info">
                        <i class="fa fa-book"></i>&nbsp; Documentación
                    </a>
                    <br/>
                    <br/>
                </div>
            </div>

            <form name="f_configuracion_inicial" id="f_configuracion_inicial" action="install.php" class="form" role="form" method="post">
                <div class="row">
                    <div class="col-sm-12">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#db" aria-controls="db" role="tab" data-toggle="tab">
                                    <i class="fa fa-database"></i>&nbsp;
                                    Base de datos
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#cache" aria-controls="cache" role="tab" data-toggle="tab">
                                    <i class="fa fa-wrench"></i>&nbsp;
                                    Avanzado
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#licencia" aria-controls="licencia" role="tab" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i>&nbsp;
                                    Licencia
                                </a>
                            </li>
                        </ul>
                        <br/>
                    </div>
                </div>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="db">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Tipo de servidor SQL:
                                    <select name="db_type" class="form-control" onchange="change_db_type()">
                                        <option value="MYSQL"<?php
                                        if ($db_type == 'MYSQL') {
                                            echo ' selected=""';
                                        }
                                        ?>>MySQL</option>
                                        <option value="POSTGRESQL"<?php
                                        if ($db_type == 'POSTGRESQL') {
                                            echo ' selected=""';
                                        }
                                        ?>>PostgreSQL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Servidor:
                                    <input class="form-control" type="text" name="db_host" value="<?php echo $db_host; ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Puerto:
                                    <input class="form-control" type="number" name="db_port" value="<?php echo $db_port; ?>" autocomplete="off"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Nombre base de datos:
                                    <input class="form-control" type="text" name="db_name" value="<?php echo $db_name; ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Usuario:
                                    <input class="form-control" type="text" name="db_user" value="<?php echo $db_user; ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    Contraseña:
                                    <input class="form-control" type="password" name="db_pass" value="" autocomplete="off"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div id="mysql_socket" class="form-group">
                                    Socket:
                                    <input class="form-control" type="text" name="mysql_socket" value="" placeholder="opcional" autocomplete="off"/>
                                    <p class="help-block">
                                        Solamente en algunos hostings es necesario especificar el socket de MySQL.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="cache">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Mencached</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p class="help-block">
                                            Este apartado es totalmente <b>opcional</b>. Si tienes instalado memcached,
                                            puedes especificar aquí la ruta, puerto y prefijo a utilizar. Si no,
                                            déjalo como está.
                                        </p>
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Servidor:
                                                    <input class="form-control" type="text" name="cache_host" value="localhost" autocomplete="off"/>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Puerto:
                                                    <input class="form-control" type="number" name="cache_port" value="11211" autocomplete="off"/>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Prefijo:
                                                    <input class="form-control" type="text" name="cache_prefix" value="<?php echo random_string(8); ?>_" autocomplete="off"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Proxy</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Tipo de Proxy:
                                                    <select class='form-control' name="proxy_type">
                                                        <option value="">Sin proxy</option>
                                                        <option value="">------</option>
                                                        <option value="HTTP">HTTP</option>
                                                        <option value="HTTPS">HTTPS</option>
                                                        <option value="SOCKS5">SOCKS5</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Servidor:
                                                    <input class="form-control" type="text" name="proxy_host" placeholder="192.168.1.1" autocomplete="off"/>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    Puerto:
                                                    <input class="form-control" type="number" name="proxy_port" placeholder="8080" autocomplete="off"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="licencia">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <pre><?php echo file_get_contents('COPYING'); ?></pre>
                                    <p>
                                        FacturaScripts también incluye una versión modificada de
                                        <a href="https://github.com/feulf/raintpl/tree/65493157073ff0f313a67fe2ee42139b3eaa7f5a">RainTPL</a>
                                        que también tiene licencia <a href="raintpl/LICENSE.txt">LGPL</a>, así como
                                        <a href="https://github.com/PHPMailer/PHPMailer/">phpmailer</a> con la misma licencia
                                        <a href="extras/phpmailer/LICENSE">LGPL</a>.
                                        <br/>
                                        Para la parte gráfica se incluye el framewrowk <a href="http://getbootstrap.com">Bootstrap</a>, con licencia
                                        <a href="https://github.com/twbs/bootstrap/blob/master/LICENSE">MIT</a> y
                                        <a href="http://fontawesome.io">font-awesome</a> también con licencia <a href="http://fontawesome.io/license">MIT</a>.
                                        <br/>
                                        Y por último, pero no menos importante, también incluye <a href="https://github.com/jquery/jquery">jQuery</a>,
                                        con licencia <a href="https://github.com/jquery/jquery/blob/master/LICENSE.txt">MIT</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 text-right">
                        <button id="submit_button" class="btn btn-sm btn-primary" type="submit">
                            <i class="fa fa-check" aria-hidden="true"></i>&nbsp; Aceptar
                        </button>
                    </div>
                </div>
            </form>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-sm-12 text-center">
                    <hr/>
                    <small>
                        &COPY; 2013-2017 <a target="_blank" href="https://www.facturascripts.com">FacturaScripts</a>
                    </small>
                </div>
            </div>
        </div>
    </body>
</html>