<?php

$nombre_archivo = "config.php";
error_reporting(E_ALL);
$errors = array();
$errors2 = array();
$db_type = 'MYSQL';
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'facturascripts';
$db_user = '';

function random_string($length = 10)
{
   return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function guarda_config($nombre_archivo)
{
   $archivo = fopen($nombre_archivo, "w");
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
   fwrite($archivo, "define('FS_DB_TYPE', '".$_REQUEST['db_type']."'); /// MYSQL o POSTGRESQL\n");
   fwrite($archivo, "define('FS_DB_HOST', '".$_REQUEST['db_host']."');\n");
   fwrite($archivo, "define('FS_DB_PORT', '".$_REQUEST['db_port']."'); /// MYSQL -> 3306, POSTGRESQL -> 5432\n");
   fwrite($archivo, "define('FS_DB_NAME', '".$_REQUEST['db_name']."');\n");
   fwrite($archivo, "define('FS_DB_USER', '".$_REQUEST['db_user']."'); /// MYSQL -> root, POSTGRESQL -> postgres\n");
   fwrite($archivo, "define('FS_DB_PASS', '".$_REQUEST['db_pass']."');\n");
   
   if($_REQUEST['db_type'] == 'MYSQL' AND $_POST['mysql_socket'] != '')
   {
      fwrite($archivo, "ini_set('mysqli.default_socket', '".$_POST['mysql_socket']."');\n");
   }
   
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Un directorio de nombre aleatorio para mejorar la seguridad del directorio temporal.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_TMP_NAME', '".random_string()."/');\n");
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
   fwrite($archivo, "define('FS_CACHE_HOST', '".$_REQUEST['cache_host']."');\n");
   fwrite($archivo, "define('FS_CACHE_PORT', '".$_REQUEST['cache_port']."');\n");
   fwrite($archivo, "define('FS_CACHE_PREFIX', '".$_REQUEST['cache_prefix']."');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/// caducidad (en segundos) de todas las cookies\n");
   fwrite($archivo, "define('FS_COOKIES_EXPIRE', 7776000);\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/// el número de elementos a mostrar en pantalla\n");
   fwrite($archivo, "define('FS_ITEM_LIMIT', 50);\n");
   fclose($archivo);
   
   header("Location: index.php");
   exit();
}

if( file_exists('config.php') )
{
   header('Location: index.php');
}
else if( floatval( substr(phpversion(), 0, 3) ) < 5.3 )
{
   $errors[] = 'php';
}
else if( !function_exists('mb_substr') )
{
   $errors[] = "mb_substr";
}
else if( !extension_loaded('simplexml') )
{
   $errors[] = "simplexml";
   $errors2[] = 'No se encuentra la extensión simplexml en tu instalación de PHP.'
           . ' Debes instalarla o activarla.';
   $errors2[] = 'Si usas Red Hat o derivados, instala el paquete php-xml.';
}
else if( !extension_loaded('openssl') )
{
   $errors[] = "openssl";
}
else if( !is_writable( getcwd() ) )
{
   $errors[] = "permisos";
}
else if( isset($_REQUEST['db_type']) )
{
   if($_REQUEST['db_type'] == 'MYSQL')
   {
      if( class_exists('mysqli') )
      {
         if($_POST['mysql_socket'] != '')
         {
            ini_set('mysqli.default_socket', $_POST['mysql_socket']);
         }
         
         // Omitimos el valor del nombre de la BD porque lo comprobaremos más tarde
         $connection = @new mysqli($_REQUEST['db_host'], $_REQUEST['db_user'], $_REQUEST['db_pass'], "", intval($_REQUEST['db_port']));
         if($connection->connect_error)
         {
            $errors[] = "db_mysql";
            $errors2[] = $connection->connect_error;
         }
         else
         {
            // Comprobamos que la BD exista, de lo contrario la creamos
            $db_selected = mysqli_select_db($connection, $_REQUEST['db_name']);
            if(!$db_selected)
            {
               $sqlCrearBD = "CREATE DATABASE `".$_REQUEST['db_name']."`;";
               if( !mysqli_query($connection, $sqlCrearBD) )
               {
                  $errors[] = "db_mysql";
                  $errors2[] = mysqli_error($connection);
               }
               else
               {
                  guarda_config($nombre_archivo);
               }
            }
            else
            {
               guarda_config($nombre_archivo);
            }
         }
      }
      else
      {
         $errors[] = "db_mysql";
         $errors2[] = 'No tienes instalada la extensión de PHP para MySQL.';
      }
   }
   else if($_REQUEST['db_type'] == 'POSTGRESQL')
   {
      if( function_exists('pg_connect') )
      {
         $connection = @pg_connect('host='.$_REQUEST['db_host'].' dbname='.$_REQUEST['db_name'].' port='.$_REQUEST['db_port'].
                 ' user='.$_REQUEST['db_user'].' password='.$_REQUEST['db_pass'] );
         if($connection)
         {
            guarda_config($nombre_archivo);
         }
         else
         {
            $errors[] = "db_postgresql";
            $errors2[] = 'No se puede conectar a la base de datos. Revisa los datos de usuario y contraseña.';
         }
      }
      else
      {
         $errors[] = "db_postgresql";
         $errors2[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
      }
   }
   
   $db_type = $_REQUEST['db_type'];
   $db_host = $_REQUEST['db_host'];
   $db_port = $_REQUEST['db_port'];
   $db_name = $_REQUEST['db_name'];
   $db_user = $_REQUEST['db_user'];
}

$system_info = 'facturascripts: '.file_get_contents('VERSION')."\n";
$system_info .= 'os: '.php_uname()."\n";
$system_info .= 'php: '.phpversion()."\n";

if( isset($_SERVER['REQUEST_URI']) )
{
   $system_info .= 'url: '.$_SERVER['REQUEST_URI']."\n------";
}
foreach($errors as $e)
{
   $system_info .= "\n" . $e;
}

$system_info = str_replace('"', "'", $system_info);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>FacturaScripts</title>
   <meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/AGPL." />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="shortcut icon" href="view/img/favicon.ico" />
   <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
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
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">FacturaScripts</a>
         </div>
         
         <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
               <li>
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Ayuda">
                     <span class="glyphicon glyphicon-question-sign hidden-xs"></span>
                     <span class="visible-xs">Ayuda</span>
                  </a>
                  <ul class="dropdown-menu">
                     <li><a href="//www.facturascripts.com/comm3/index.php?page=community_questions" target="_blank">Preguntas</a></li>
                     <li><a href="//www.facturascripts.com/comm3/index.php?page=community_errors" target="_blank">Errores</a></li>
                     <li><a href="//www.facturascripts.com/comm3/index.php?page=community_ideas" target="_blank">Sugerencias</a></li>
                     <li><a href="//www.facturascripts.com/comm3/index.php?page=community_all" target="_blank">Todo</a></li>
                     <li class="divider"></li>
                     <li>
                        <a href="#" id="b_feedback">
                           <span class="glyphicon glyphicon-send"></span> &nbsp; Informar...
                        </a>
                     </li>
                  </ul>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <form name="f_feedback" action="//www.facturascripts.com/comm3/index.php?page=community_feedback" method="post" target="_blank" class="form" role="form">
      <input type="hidden" name="feedback_info" value="<?php echo $system_info; ?>"/>
      <input type="hidden" name="feedback_type" value="error"/>
      <div class="modal" id="modal_feedback">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title">¿Necesitas ayuda?</h4>
               </div>
               <div class="modal-body">
                  <div class="form-group">
                     <label for="feedback_textarea">Detalla tu duda o problema:</label>
                     <textarea id="feedback_textarea" class="form-control" name="feedback_text" rows="6"></textarea>
                  </div>
                  <div class="form-group">
                     <label for="exampleInputEmail1">Tu email</label>
                     <input type="email" class="form-control" id="exampleInputEmail1" name="feedback_email" placeholder="Introduce tu email"/>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="submit" class="btn btn-sm btn-primary">
                     <span class="glyphicon glyphicon-send"></span> &nbsp; Enviar
                  </button>
               </div>
            </div>
         </div>
      </div>
   </form>

   <script type="text/javascript">
      function change_db_type() {
         if(document.f_configuracion_inicial.db_type.value == 'POSTGRESQL')
         {
            document.f_configuracion_inicial.db_port.value = '5432';
            if(document.f_configuracion_inicial.db_user.value == '')
            {
               document.f_configuracion_inicial.db_user.value = 'postgres';
            }
            $("#mysql_socket").hide();
         }
         else
         {
            document.f_configuracion_inicial.db_port.value = '3306';
            $("#mysql_socket").show();
         }
      }
      $(document).ready(function() {
         $("#f_configuracion_inicial").validate({
            rules: {
               db_type: { required: false},
               db_host: { required: true, minlength: 2},
               db_port: { required: true, minlength: 2},
               db_name: { required: true, minlength: 2},
               db_user: { required: true, minlength: 2},
               db_pass: { required: false},
               cache_host: { required: true, minlength: 2},
               cache_port: { required: true, minlength: 2},
               cache_prefix: { required: false, minlength: 2}
            },
            messages: {
               db_host: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_port: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_name: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_user: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               cache_host: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               cache_port: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
            }
         });
      });
   </script>
   
   <div class="container">
      <div class="row">
         <div class="col-lg-12">
            <div class="page-header">
               <h1>Bienvenido al instalador de FacturaScripts <?php echo file_get_contents('VERSION'); ?></h1>
            </div>
         </div>
      </div>
      
      <div class="row">
         <div class="col-lg-12">
            <?php
            foreach($errors as $err)
            {
               if($err == 'permisos')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Permisos de escritura:
               </div>
               <div class="panel-body">
                  <p>
                     La carpeta de FacturaScripts no tiene permisos de escritura. Sin esos
                     permisos, no funcionará FacturaScripts.
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (si usas Linux):</h4>
                  <pre>sudo chmod -R o+w <?php echo dirname(__FILE__); ?></pre>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (instalación en hosting):</h4>
                  <p>Intenta dar permisos de escritura desde el cliente FTP o desde el cPanel.</p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'php')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Versión de PHP obsoleta:
               </div>
               <div class="panel-body">
                  <p>
                     FacturaScripts necesita PHP 5.3 o superior, y tú estás usando <?php echo phpversion() ?>.
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución:</h4>
                  <p>
                     Muchos hostings ofrecen PHP 5.1, 5.2 y 5.3. Pero hay que seleccionar PHP 5.3
                     desde el panel de control.
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'mb_substr')
               {
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
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (en Linux):</h4>
                  <p>Instala el paquete php-mbstring.</p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Hosting:</h4>
                  <p>
                     Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                     Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                     Nosotros recomendamos
                     <a href="http://www.loading.es/clientes/aff.php?aff=857" target="_blank">Loading.es</a>
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'openssl')
               {
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
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Hosting:</h4>
                  <p>
                     Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                     Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                     Nosotros recomendamos
                     <a href="http://www.loading.es/clientes/aff.php?aff=857" target="_blank">Loading.es</a>
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Servidor personal:</h4>
                  <p>
                     Es muy raro que en una instalación propia de PHP ya sea en Linux o en Windows
                     con uno de estos empaquetados Apache+PHP+MySQL no traiga de serie OpenSSL.
                     <a href="#" data-toggle="modal" data-target="#modal_feedback">Informanos</a>
                     de qué tienes instalado e intentaremos ofrecerte la mejor solución.
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'db_mysql')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Acceso a base de datos MySQL:
               </div>
               <div class="panel-body">
                  <ul>
                   <?php
                   foreach($errors2 as $err2)
                      echo "<li>".$err2."</li>";
                   ?>
                  </ul>
               </div>
            </div>
                  <?php
               }
               else if($err == 'db_postgresql')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Acceso a base de datos PostgreSQL:
               </div>
               <div class="panel-body">
                  <ul>
                   <?php
                   foreach($errors2 as $err2)
                      echo "<li>".$err2."</li>";
                   ?>
                  </ul>
               </div>
            </div>
                  <?php
               }
               else
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Error:
               </div>
               <div class="panel-body">
                  <ul>
                   <?php
                   if($errors2)
                   {
                       foreach($errors2 as $err2)
                       {
                          echo "<li>".$err2."</li>";
                       }
                   }
                   else
                   {
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
         <div class="col-lg-10">
            <h3>Antes de empezar...</h3>
            <p>
               Recuerda que tienes el menú de ayuda arriba a la derecha. Si encuentras cualquier problema,
               haz clic en <b>informar...</b> y describe tu duda, sugerencia o el error que has encontrado.
               No sabemos hacer software perfecto, pero con tu ayuda nos podemos acercar cada vez más ;-)
            </p>
            <p>
               Y si quieres saber más, no olvides seguir a nuestro desarrollador principal
               en su canal de youtube.
            </p>
            <a href="https://www.youtube.com/user/NeoRazorX" target="_blank" class="btn btn-sm btn-danger">
               <span class="glyphicon glyphicon-facetime-video"></span> &nbsp; FacturaScripts en YouTube
            </a>
         </div>
         <div class="col-lg-2">
            <div class="thumbnail">
               <img src="view/img/help-menu.png" alt="ayuda"/>
            </div>
         </div>
      </div>
      
      <div class="row">
         <div class="col-lg-12">
            <form name="f_configuracion_inicial" id="f_configuracion_inicial" action="install.php" class="form" role="form" method="post">
               <div class="panel panel-primary">
                  <div class="panel-heading">
                     <h3 class="panel-title">
                        <span class="badge">1</span> &nbsp; Configuración de la base de datos
                     </h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Tipo de servidor SQL:
                        <select name="db_type" class="form-control" onchange="change_db_type()">
                           <option value="MYSQL"<?php if($db_type=='MYSQL') { echo ' selected=""'; } ?>>MySQL</option>
                           <option value="POSTGRESQL"<?php if($db_type=='POSTGRESQL') { echo ' selected=""'; } ?>>PostgreSQL</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Servidor:
                        <input class="form-control" type="text" name="db_host" value="<?php echo $db_host; ?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto:
                        <input class="form-control" type="number" name="db_port" value="<?php echo $db_port; ?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Nombre base de datos:
                        <input class="form-control" type="text" name="db_name" value="<?php echo $db_name; ?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Usuario:
                        <input class="form-control" type="text" name="db_user" value="<?php echo $db_user; ?>" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Contraseña:
                        <input class="form-control" type="password" name="db_pass" value="" autocomplete="off"/>
                     </div>
                     <div id="mysql_socket" class="form-group col-lg-4 col-md-4 col-sm-4">
                        Socket (opcional):
                        <input class="form-control" type="text" name="mysql_socket" value="" autocomplete="off"/>
                     </div>
                  </div>
               </div>
                  
               <div class="panel panel-info" id="panel_configuracion_inicial_cache">
                  <div class="panel-heading">
                     <h3 class="panel-title">
                        <span class="badge">2</span> &nbsp; Configuración Memcache (opcional)
                     </h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Servidor:
                        <input class="form-control" type="text" name="cache_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto:
                        <input class="form-control" type="number" name="cache_port" value="11211" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Prefijo:
                        <input class="form-control" type="text" name="cache_prefix" value="<?php echo random_string(8) ?>_" autocomplete="off"/>
                     </div>
                  </div>
               </div>
               
               <div class="text-right">
                  <button id="submit_button" class="btn btn-sm btn-primary" type="submit">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                     &nbsp; Guardar y empezar
                  </button>
               </div>
            </form>
         </div>
      </div>
      
      <div class="row" style="margin-bottom: 20px;">
         <div class="col-lg-12 col-md-12 col-sm-12 text-center">
            <hr/>
            <small>
               Creado con <a target="_blank" href="//www.facturascripts.com">FacturaScripts</a>
            </small>
         </div>
      </div>
   </div>
</body>
</html>