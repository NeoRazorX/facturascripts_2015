<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Controlador para modificar el rol de usuarios.
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class admin_rol extends fs_controller
{

    public $allow_delete;
    public $rol;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Editar rol', 'admin', FALSE, FALSE);
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->admin;

        if (fs_filter_input_req('codrol')) {
            $fs_rol = new fs_rol();
            $this->rol = $fs_rol->get(fs_filter_input_req('codrol'));
        }

        if ($this->rol) {
            if (filter_input(INPUT_POST, 'descripcion')) {
                $this->modify();
            } else if (filter_input(INPUT_GET, 'aplicar')) {
                $this->aplicar_permisos();
            }
        } else {
            $this->new_error_msg("Rol no encontrado.", 'error', FALSE, FALSE);
        }
    }

    public function all_pages()
    {
        $returnlist = array();

        /// Obtenemos la lista de páginas. Todas
        foreach ($this->menu as $m) {
            $m->enabled = FALSE;
            $m->allow_delete = FALSE;
            $returnlist[] = $m;
        }

        /// Completamos con la lista de accesos del rol
        $access = $this->rol->get_accesses();
        foreach ($returnlist as $i => $value) {
            foreach ($access as $a) {
                if ($value->name == $a->fs_page) {
                    $returnlist[$i]->enabled = TRUE;
                    $returnlist[$i]->allow_delete = $a->allow_delete;
                    break;
                }
            }
        }

        /// ordenamos por nombre
        usort($returnlist, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $returnlist;
    }

    public function all_users()
    {
        $returnlist = array();

        /// Obtenemos la lista de páginas. Todas
        foreach ($this->user->all() as $u) {
            $u->included = FALSE;
            $returnlist[] = $u;
        }

        /// Completamos con la lista de usuarios del rol
        $users = $this->rol->get_users();
        foreach ($returnlist as $i => $value) {
            foreach ($users as $a) {
                if ($value->nick == $a->fs_user) {
                    $returnlist[$i]->included = TRUE;
                    break;
                }
            }
        }

        return $returnlist;
    }

    private function modify()
    {
        $this->rol->descripcion = filter_input(INPUT_POST, 'descripcion');

        if ($this->rol->save()) {
            $allow_delete = filter_input(INPUT_POST, 'allow_delete', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $enabled = filter_input(INPUT_POST, 'enabled', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            /// para cada página, comprobamos si hay que darle acceso o no
            foreach ($this->all_pages() as $p) {
                /**
                 * Creamos un objeto fs_rol_access con los datos del rol y la página.
                 * Si tiene acceso guardamos, sino eliminamos. Así no tenemos que comprobar uno a uno
                 * si ya estaba en la base de datos. Eso lo hace el modelo.
                 */
                $a = new fs_rol_access(array('codrol' => $this->rol->codrol, 'fs_page' => $p->name, 'allow_delete' => FALSE));
                if ($allow_delete) {
                    $a->allow_delete = in_array($p->name, $allow_delete);
                }

                if (!$enabled) {
                    /**
                     * No se ha marcado ningún checkbox de autorizado, así que eliminamos el acceso
                     * a todas las páginas. Una a una.
                     */
                    $a->delete();
                } else if (in_array($p->name, $enabled)) {
                    /// la página ha sido marcada como autorizada.
                    $a->save();
                } else {
                    /// la página no está marcada como autorizada.
                    $a->delete();
                }
            }

            /// para cada usuario, comprobamos si hay que incluirlo o no
            $idusers = filter_input(INPUT_POST, 'iuser', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            foreach ($this->all_users() as $u) {
                /**
                 * Creamos un objeto fs_rol_user con los datos del rol y el usuario.
                 * Si tiene acceso guardamos, sino eliminamos. Así no tenemos que comprobar uno a uno
                 * si ya estaba en la base de datos. Eso lo hace el modelo.
                 */
                $a = new fs_rol_user(array('codrol' => $this->rol->codrol, 'fs_user' => $u->nick));

                if (!$idusers) {
                    /**
                     * No se ha marcado ningún checkbox de autorizado, así que eliminamos la relación
                     * con todos los usuarios, uno a uno.
                     */
                    $a->delete();
                } else if (in_array($u->nick, $idusers)) {
                    /// el usuario ha sido marcado como incluido.
                    $a->save();
                } else {
                    /// el usuario no está marcado como incluido.
                    $a->delete();
                }
            }

            $this->new_message('Datos guardados. Recuerda pulsar el botón aplicar.');
        } else {
            $this->new_error_msg('Error al guardar los datos.');
        }
    }

    private function aplicar_permisos()
    {
        $usuarios = array();
        foreach ($this->all_users() as $usu) {
            if ($usu->included) {
                $usuarios[] = $usu;
            }
        }

        /// primero eliminamos los permisos de todos los usuarios del rol
        foreach ($usuarios as $usu) {
            foreach ($usu->get_accesses() as $a) {
                $a->delete();
            }
        }

        /// ahora aplicamos los permisos del rol
        $nump = 0;
        $permisos = $this->all_pages();
        foreach ($usuarios as $usu) {
            foreach ($permisos as $p) {
                if ($p->enabled) {
                    $a = new fs_access();
                    $a->fs_user = $usu->nick;
                    $a->fs_page = $p->name;
                    $a->allow_delete = $p->allow_delete;
                    $a->save();
                    $nump++;
                }
            }
        }

        /// ahora, para cada usuario, aplicamos los permisos del resto sus roles
        foreach ($usuarios as $usu) {
            foreach ($this->rol->all_for_user($usu->nick) as $rol) {
                if ($rol->codrol != $this->rol->codrol) {
                    foreach ($rol->get_accesses() as $p) {
                        $a = new fs_access();
                        $a->fs_user = $usu->nick;
                        $a->fs_page = $p->fs_page;
                        $a->allow_delete = $p->allow_delete;
                        $a->save();
                        $nump++;
                    }
                }
            }
        }

        $this->new_message($nump . ' permisos aplicados correctamente.');
    }
}
