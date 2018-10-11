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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'base/fs_ip_filter.php';

/**
 * Description of fs_login
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_login
{

    /**
     *
     * @var string
     */
    private $ban_message;

    /**
     *
     * @var fs_cache
     */
    private $cache;

    /**
     *
     * @var fs_core_log
     */
    private $core_log;

    /**
     *
     * @var fs_ip_filter
     */
    private $ip_filter;

    /**
     *
     * @var fs_user
     */
    private $user_model;

    public function __construct()
    {
        $this->ban_message = 'Tendrás que esperar ' . fs_ip_filter::BAN_SECONDS . ' segundos antes de volver a intentar entrar.';
        $this->cache = new fs_cache();
        $this->core_log = new fs_core_log();
        $this->ip_filter = new fs_ip_filter();
        $this->user_model = new fs_user();
    }

    public function change_user_passwd()
    {
        $ip = fs_get_ip();
        $nick = filter_input(INPUT_POST, 'user');
        if ($this->ip_filter->is_banned($ip)) {
            $this->ip_filter->set_attempt($ip);
            $this->core_log->new_error('Tu IP ha sido baneada, ' . $nick . '. ' . $this->ban_message);
            $this->core_log->save('Tu IP ha sido baneada, ' . $nick . '. ' . $this->ban_message);
            return FALSE;
        }

        if (!$this->ip_filter->in_white_list($ip)) {
            $this->core_log->new_error('No puedes acceder desde esta IP, ' . $nick . '.');
            $this->core_log->save('No puedes acceder desde esta IP, ' . $nick . '.', 'login', TRUE);
            return FALSE;
        }

        $new_password = filter_input(INPUT_POST, 'new_password');
        $new_password2 = filter_input(INPUT_POST, 'new_password2');
        if ($new_password != $new_password2) {
            $this->core_log->new_error('Las contraseñas no coinciden, ' . $nick);
            return FALSE;
        }

        if ($new_password == '') {
            $this->core_log->new_error('Tienes que escribir una contraseña nueva, ' . $nick);
            return FALSE;
        }

        $db_password = filter_input(INPUT_POST, 'db_password');
        if ($db_password != FS_DB_PASS) {
            $this->ip_filter->set_attempt($ip);
            $this->core_log->new_error('La contraseña de la base de datos es incorrecta, ' . $nick);
            return FALSE;
        }

        $user = $this->user_model->get($nick);
        if ($user) {
            $user->set_password($new_password);
            if ($user->save()) {
                $this->core_log->new_message('Contraseña cambiada correctamente para ' . $nick);
                return TRUE;
            }

            $this->core_log->new_error('Imposible cambiar la contraseña del usuario ' . $nick);
        }

        return FALSE;
    }

    /**
     * 
     * @param fs_user $controller_user
     *
     * @return boolean
     */
    public function log_in(&$controller_user)
    {
        $ip = fs_get_ip();
        $nick = filter_input(INPUT_POST, 'user');
        if ($this->ip_filter->is_banned($ip)) {
            $this->core_log->new_error('Tu IP ha sido baneada, ' . $nick . '. ' . $this->ban_message);
            $this->core_log->save('Tu IP ha sido baneada, ' . $nick . '. ' . $this->ban_message, 'login', TRUE);
            return FALSE;
        }

        $password = filter_input(INPUT_POST, 'password');
        if ($nick && $password) {
            if (FS_DEMO) {
                /// en el modo demo nos olvidamos de la contraseña
                return $this->log_in_demo($controller_user, $nick);
            }

            $this->ip_filter->set_attempt($ip);
            return $this->log_in_user($controller_user, $nick, $password, $ip);
        }

        if (filter_input(INPUT_COOKIE, 'user') && filter_input(INPUT_COOKIE, 'logkey')) {
            return $this->log_in_cookie($controller_user);
        }

        return FALSE;
    }

    /**
     * Gestiona el cierre de sesión
     * @param boolean $rmuser eliminar la cookie del usuario
     */
    public function log_out($rmuser = FALSE)
    {
        $path = '/';
        if (filter_input(INPUT_SERVER, 'REQUEST_URI')) {
            $aux = parse_url(str_replace('/index.php', '', filter_input(INPUT_SERVER, 'REQUEST_URI')));
            if (isset($aux['path'])) {
                $path = $aux['path'];
                if (substr($path, -1) != '/') {
                    $path .= '/';
                }
            }
        }

        /// borramos las cookies
        if (filter_input(INPUT_COOKIE, 'logkey')) {
            setcookie('logkey', '', time() - FS_COOKIES_EXPIRE);
            setcookie('logkey', '', time() - FS_COOKIES_EXPIRE, $path);
            if ($path != '/') {
                setcookie('logkey', '', time() - FS_COOKIES_EXPIRE, '/');
            }
        }

        /// ¿Eliminamos la cookie del usuario?
        if ($rmuser && filter_input(INPUT_COOKIE, 'user')) {
            setcookie('user', '', time() - FS_COOKIES_EXPIRE);
            setcookie('user', '', time() - FS_COOKIES_EXPIRE, $path);
        }

        /// guardamos el evento en el log
        $this->core_log->save('El usuario ha cerrado la sesión.', 'login');
    }

    /**
     * 
     * @param fs_user $controller_user
     *
     * @return bool
     */
    private function log_in_cookie(&$controller_user)
    {
        $nick = filter_input(INPUT_COOKIE, 'user');
        $user = $this->user_model->get($nick);
        if ($user && $user->enabled) {
            $logkey = filter_input(INPUT_COOKIE, 'logkey');
            if ($user->log_key == $logkey) {
                $user->logged_on = TRUE;
                $user->update_login();
                $this->save_cookie($user);
                $controller_user = $user;
            } else if (!is_null($user->log_key)) {
                $this->core_log->new_message('¡Cookie no válida! Alguien ha accedido a esta cuenta desde otro PC con IP: '
                    . $user->last_ip . ". Si has sido tú, ignora este mensaje.");
                $this->log_out();
            }
        } else {
            $this->core_log->new_error('¡El usuario ' . $nick . ' no existe o está desactivado!');
            $this->log_out(TRUE);
            $this->user_model->clean_cache(TRUE);
            $this->cache->clean();
        }

        return $controller_user->logged_on;
    }

    /**
     * 
     * @param fs_user $controller_user
     * @param string  $email
     *
     * @return bool
     */
    private function log_in_demo(&$controller_user, $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->core_log->new_error('Email no válido: ' . $email);
            return FALSE;
        }

        $aux = explode('@', $email);
        $nick = substr($aux[0], 0, 12);
        if ($nick == 'admin') {
            $nick .= $this->random_string(7);
        }

        $user = $this->user_model->get($nick);
        if (!$user) {
            $user = new fs_user();
            $user->nick = $nick;
            $user->set_password('demo');
            $user->email = $email;

            /// creamos un agente para asociarlo
            $agente = new agente();
            $agente->codagente = $agente->get_new_codigo();
            $agente->nombre = $nick;
            $agente->apellidos = 'Demo';
            $agente->email = $email;

            if ($agente->save()) {
                $user->codagente = $agente->codagente;
            }
        }

        $user->new_logkey();
        if ($user->save()) {
            $this->save_cookie($user);
            $controller_user = $user;
        }

        return $controller_user->logged_on;
    }

    /**
     * 
     * @param fs_user $controller_user
     * @param string  $nick
     * @param string  $password
     * @param string  $ip
     *
     * @return boolean
     */
    private function log_in_user(&$controller_user, $nick, $password, $ip)
    {
        $user = $this->user_model->get($nick);
        if (!$user) {
            $this->core_log->new_error('El usuario o contraseña no coinciden!');
            $this->user_model->clean_cache(TRUE);
            $this->cache->clean();
            return FALSE;
        }

        if (!$user->enabled) {
            $this->core_log->new_error('El usuario ' . $user->nick . ' está desactivado, habla con tu administrador!');
            $this->core_log->save('El usuario ' . $user->nick . ' está desactivado, habla con tu administrador!', 'login', TRUE);
            $this->user_model->clean_cache(TRUE);
            $this->cache->clean();
            return FALSE;
        }

        /**
         * En versiones anteriores se guardaban las contraseñas siempre en
         * minúsculas, por eso, para dar compatibilidad comprobamos también
         * en minúsculas.
         */
        if ($user->password != sha1($password) && $user->password != sha1(mb_strtolower($password, 'UTF8'))) {
            $this->core_log->new_error('¡Contraseña incorrecta! (' . $nick . ')');
            $this->core_log->save('¡Contraseña incorrecta! (' . $nick . ')', 'login', TRUE);
            return FALSE;
        }

        $user->new_logkey();

        if (!$user->admin && !$this->ip_filter->in_white_list($ip)) {
            $this->core_log->new_error('No puedes acceder desde esta IP.');
            $this->core_log->save('No puedes acceder desde esta IP.', 'login', TRUE);
        } else if ($user->save()) {
            $this->save_cookie($user);

            /// añadimos el mensaje al log
            $this->core_log->save('Login correcto.', 'login');

            /// limpiamos la lista de IPs
            $this->ip_filter->clear();

            $controller_user = $user;
            return $controller_user->logged_on;
        }

        $this->core_log->new_error('Imposible guardar los datos de usuario.');
        $this->cache->clean();
        return FALSE;
    }

    /**
     * 
     * @param fs_user $user
     */
    private function save_cookie($user)
    {
        setcookie('user', $user->nick, time() + FS_COOKIES_EXPIRE);
        setcookie('logkey', $user->log_key, time() + FS_COOKIES_EXPIRE);
    }

    /**
     * Devuelve un string aleatorio de longitud $length
     *
     * @param integer $length la longitud del string
     *
     * @return string la cadena aleatoria
     */
    private function random_string($length = 30)
    {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
}
