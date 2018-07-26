<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (!file_exists('config.php')) {
    die('Archivo config.php no encontrado. No puedes actualizar sin instalar.');
}

define('FS_FOLDER', __DIR__);

/// ampliamos el límite de ejecución de PHP a 5 minutos
@set_time_limit(300);

require_once 'config.php';
require_once 'base/fs_updater.php';

/**
 * Registramos la función para capturar los fatal error.
 * Información importante a la hora de depurar errores.
 */
register_shutdown_function("fatal_handler");

$updater = new fs_updater();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Actualizador de FacturaScripts</title>
        <meta name="description" content="Script de actualización de FacturaScripts." />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="generator" content="FacturaScripts" />
        <link rel="shortcut icon" href="view/img/favicon.ico" />
        <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
        <link rel="stylesheet" href="view/css/font-awesome.min.css" />
        <script type="text/javascript" src="view/js/jquery.min.js"></script>
        <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
    </head>
    <body>
        <br/>
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <a href="index.php?page=admin_home&updated=TRUE" class="btn btn-sm btn-default">
                        <span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
                        <span class="hidden-xs">&nbsp;Panel de control</span>
                    </a>
                    <a href="https://www.facturascripts.com/comm3/index.php?page=community_tus_plugins" target="_blank" class="btn btn-sm btn-default">
                        <i class="fa fa-key" aria-hidden="true"></i>
                        <span class="hidden-xs">&nbsp;Claves</span>
                    </a>
                    <div class="page-header">
                        <h1>
                            <span class="glyphicon glyphicon-upload" aria-hidden="true"></span> Actualizador de FacturaScripts
                        </h1>
                    </div>
                    <?php
                    if (count($updater->core_log->get_errors()) > 0) {
                        echo '<div class="alert alert-danger"><ul>';
                        foreach ($updater->core_log->get_errors() as $error) {
                            echo '<li>' . $error . '</li>';
                        }
                        echo '</ul></div>';
                    }

                    if (count($updater->core_log->get_messages()) > 0) {
                        echo '<div class="alert alert-info"><ul>';
                        foreach ($updater->core_log->get_messages() as $msg) {
                            echo '<li>' . $msg . '</li>';
                        }
                        echo '</ul></div>';

                        if ($updater->btn_fin) {
                            echo '<a href="index.php?page=admin_home&updated=TRUE" class="btn btn-sm btn-info">'
                            . '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> &nbsp; Finalizar'
                            . '</a></br/></br/>';
                        }
                    }

                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-9">
                    <p class="help-block">
                        Este actualizador permite actualizar <b>tanto el núcleo</b> de FacturaScripts
                        <b>como sus plugins</b>, incluso los de pago y los privados.
                        Si hay una actualización del núcleo tendrás que actualizar antes de poder ver si
                        también hay actualizaciones de plugins.
                    </p>
                    <br/>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#actualizaciones" aria-controls="actualizaciones" role="tab" data-toggle="tab">
                                <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
                                <span class="hidden-xs">&nbsp;Actualizaciones</span>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#opciones" aria-controls="opciones" role="tab" data-toggle="tab">
                                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                                <span class="hidden-xs">&nbsp;Opciones</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="actualizaciones">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Nombre</th>
                                            <th class="text-left">Descripción</th>
                                            <th class="text-right">Versión</th>
                                            <th class="text-right">Nueva versión</th>
                                            <th></th>
                                        </tr>
                                    </thead>
<?php echo $updater->tr_updates; ?>
                                </table>
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="opciones">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Opción</th>
                                            <th></th>
                                        </tr>
                                    </thead>
<?php echo $updater->tr_options; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Financiación</h3>
                        </div>
                        <div class="panel-body">
                            <div class="progress">
                                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="width: 25%;">
                                    <span class="sr-only">25% Complete</span>
                                </div>
                            </div>
                            <p class="help-block">
                                Hemos activado la financiación colectiva de FacturaScripts
                                para que podáis colaborar en financiar la documentación,
                                planificación, diseño, programación y mantenimiento de
                                todo el proyecto, de forma que podamos desarrollar cada
                                vez más plugins y actualizaciones.
                            </p>
                            <a href="https://www.facturascripts.com/store/producto/patrocinar-facturascripts/" target="_blank" class="btn btn-success">
                                Aportar 5 €
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if (!isset($updater->updates)) {
            /// nada
        } else if ($updater->updates['plugins']) {
            foreach ($updater->check_for_plugin_updates() as $plug) {
                if ($plug['depago']) {

                    ?>
                    <form action="updater.php?idplugin=<?php echo $plug['idplugin'] . '&name=' . $plug['name']; ?>" method="post" class="form">
                        <div class="modal" id="modal_key_<?php echo $plug['name']; ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title">
                                            <i class="fa fa-key" aria-hidden="true"></i> Añadir clave de actualización
                                        </h4>
                                        <p class="help-block">Imprescindible para actualizar el plugin <b><?php echo $plug['name']; ?></b>.</p>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <div class="form-group">
                                                    Clave:
                                                    <input type="text" name="key" class="form-control" autocomplete="off" autofocus=""/>
                                                    <p class="help-block">
                                                        ¿No sabes cual es tu clave? Puedes consultarla pulsando el botón
                                                        <b>ver mis claves</b>.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <a href="https://www.facturascripts.com/comm3/index.php?page=community_tus_plugins" target="_blank" class="btn btn-sm btn-warning">
                                                    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                                                    <span class="hidden-xs">&nbsp;Ver mis claves</span>
                                                </a>
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                    <span class="hidden-xs">&nbsp;Añadir</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php
                }
            }
        }

        ?>
        <br/><br/>
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <hr/>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <small>
                        Creado con <a target="_blank" href="https://www.facturascripts.com">FacturaScripts</a>.
                    </small>
                </div>
                <div class="col-xs-6 text-right">
                    <span class="label label-default">
                        <span class="glyphicon glyphicon-time" aria-hidden="true"></span>
                        &nbsp; <?php echo $updater->duration(); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
        if (!FS_DEMO) {
            $url = 'https://www.facturascripts.com/comm3/index.php?page=community_stats'
                . '&add=TRUE&version=' . $updater->plugin_manager->version . '&plugins=' . implode(',', $updater->plugins);

            ?>
            <div style="display: none;">
                <iframe src="<?php echo $url; ?>" height="0"></iframe>
            </div>
            <?php
        }

        ?>
    </body>
</html>