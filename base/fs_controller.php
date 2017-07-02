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

require_once 'base/fs_core_log.php';
require_once 'base/fs_cache.php';
require_once 'base/fs_db2.php';
require_once 'base/fs_default_items.php';
require_once 'base/fs_ip_filter.php';
require_once 'base/fs_model.php';

require_model('agente.php');
require_model('divisa.php');
require_model('empresa.php');
require_model('fs_access.php');
require_model('fs_page.php');
require_model('fs_user.php');
require_model('fs_extension.php');
require_model('fs_log.php');
require_model('fs_var.php');

/**
 * La clase principal de la que deben heredar todos los controladores
 * (las páginas) de FacturaScripts.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fs_controller {

    /**
     * Este objeto permite acceso directo a la base de datos.
     * @var fs_db2
     */
    protected $db;

    /**
     * Este objeto permite interactuar con memcache
     * @var fs_cache
     */
    protected $cache;

    /**
     * Este objeto contiene los mensajes, errores y consejos volcados por controladores,
     * modelos y base de datos.
     * @var fs_core_log 
     */
    private $core_log;

    /**
     * Nombre del controlador (lo utilizamos en lugar de __CLASS__ porque __CLASS__
     * en las funciones de la clase padre es el nombre de la clase padre).
     * @var string 
     */
    protected $class_name;

    /**
     * Permite calcular cuanto tarda en procesarse la página.
     * @var string 
     */
    private $uptime;

    /**
     * Listado con los últimos cambios en documentos.
     * @var array 
     */
    private $last_changes;

    /**
     * Almecena el simbolo de la divisa predeterminada de la empresa.
     * @var array
     */
    private $simbolo_divisas;

    /**
     * Indica si FacturaScripts está actualizado o no.
     * @var boolean 
     */
    private $fs_updated;

    /**
     * El usuario que ha hecho login
     * @var fs_user
     */
    public $user;

    /**
     * El elemento del menú de esta página
     * @var fs_page
     */
    public $page;

    /**
     * Contiene el menú de FacturaScripts
     * @var array
     */
    protected $menu;

    /**
     * Indica que archivo HTML hay que cargar
     * @var string|false 
     */
    public $template;

    /**
     * Esta variable contiene el texto enviado como parámetro query por cualquier formulario,
     * es decir, se corresponde con $_REQUEST['query']
     * @var string
     */
    public $query;

    /**
     * La empresa
     * @var empresa
     */
    public $empresa;

    /**
     * Permite consultar los parámetros predeterminados para series, divisas, forma de pago, etc...
     * @var fs_default_items 
     */
    public $default_items;

    /**
     * Listado de extensiones de la página
     * @var array 
     */
    public $extensions;

    /**
     * @param string $name sustituir por __CLASS__
     * @param string $title es el título de la página, y el texto que aparecerá en el menú
     * @param string $folder es el menú dónde quieres colocar el acceso directo
     * @param boolean $admin OBSOLETO
     * @param boolean $shmenu debe ser TRUE si quieres añadir el acceso directo en el menú
     * @param boolean $important debe ser TRUE si quieres que aparezca en el menú de destacado
     */
    public function __construct($name = __CLASS__, $title = 'home', $folder = '', $admin = FALSE, $shmenu = TRUE, $important = FALSE) {
        $tiempo = explode(' ', microtime());
        $this->uptime = $tiempo[1] + $tiempo[0];
        $this->simbolo_divisas = array();
        $this->extensions = array();

        $this->class_name = $name;
        $this->core_log = new fs_core_log($name);
        $this->cache = new fs_cache();
        $this->db = new fs_db2();

        if ($this->db->connect()) {
            $this->user = new fs_user();
            $this->check_fs_page($name, $title, $folder, $shmenu, $important);

            $this->empresa = new empresa();
            $this->default_items = new fs_default_items();

            /// cargamos las extensiones
            $fsext = new fs_extension();
            foreach ($fsext->all() as $ext) {
                /// Cargamos las extensiones para este controlador o para todos
                if (in_array($ext->to, array(NULL, $name))) {
                    $this->extensions[] = $ext;
                }
            }

            if (filter_input(INPUT_GET, 'logout')) {
                $this->template = 'login/default';
                $this->log_out();
            } else if (filter_input(INPUT_POST, 'new_password') AND filter_input(INPUT_POST, 'new_password2') AND filter_input(INPUT_POST, 'user')) {
                $this->fs_change_user_passwd();
            } else if (!$this->log_in()) {
                $this->template = 'login/default';
                $this->public_core();
            } else if ($this->user->have_access_to($this->page->name)) {
                if ($name == __CLASS__) {
                    $this->template = 'index';
                } else {
                    $this->template = $name;
                    $this->set_default_items();
                    $this->pre_private_core();
                    $this->private_core();
                }
            } else if ($name == '') {
                $this->template = 'index';
            } else {
                $this->template = 'access_denied';
                $this->user->clean_cache(TRUE);
                $this->empresa->clean_cache();
            }
        } else {
            $this->template = 'no_db';
            $this->new_error_msg('¡Imposible conectar con la base de datos <b>' . FS_DB_NAME . '</b>!');
        }
    }

    private function pre_private_core() {
        $this->query = '';
        if (filter_input(INPUT_POST, 'query')) {
            $this->query = filter_input(INPUT_POST, 'query');
        } else if (filter_input(INPUT_GET, 'query')) {
            $this->query = filter_input(INPUT_GET, 'query');
        }

        /// quitamos extensiones de páginas a las que el usuario no tenga acceso
        foreach ($this->extensions as $i => $value) {
            if ($value->type != 'config') {
                if (!$this->user->have_access_to($value->from)) {
                    unset($this->extensions[$i]);
                }
            }
        }
    }

    /**
     * Procesa los datos de la página o entrada en el menú
     * @param string $name
     * @param string $title
     * @param string $folder
     * @param boolean $shmenu
     * @param boolean $important
     */
    private function check_fs_page($name, $title, $folder, $shmenu, $important) {
        /// cargamos los datos de la página o entrada del menú actual
        $this->page = new fs_page(
                array(
            'name' => $name,
            'title' => $title,
            'folder' => $folder,
            'version' => $this->version(),
            'show_on_menu' => $shmenu,
            'important' => $important,
            'orden' => 100
                )
        );

        /// ahora debemos comprobar si guardar o no
        if ($name) {
            $page = $this->page->get($name);
            if ($page) {
                /// la página ya existe ¿Actualizamos?
                if ($page->title != $title OR $page->folder != $folder OR $page->show_on_menu != $shmenu OR $page->important != $important) {
                    $page->title = $title;
                    $page->folder = $folder;
                    $page->show_on_menu = $shmenu;
                    $page->important = $important;
                    $page->save();
                }

                $this->page = $page;
            } else {
                /// la página no existe, guardamos.
                $this->page->save();
            }
        }
    }

    /**
     * Cambia la contraseña del usuario.
     */
    private function fs_change_user_passwd() {
        $db_password = filter_input(INPUT_POST, 'db_password');
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        $ipFilter = new fs_ip_filter();
        $nick = filter_input(INPUT_POST, 'user');
        $new_password = filter_input(INPUT_POST, 'new_password');
        $new_password2 = filter_input(INPUT_POST, 'new_password2');

        if ($ipFilter->isBanned($ip)) {
            $ipFilter->setAttempt($ip);
            $this->new_error_msg('Tu IP ha sido baneada ' . $nick . '. '
                    . 'Tendrás que esperar 10 minutos antes de volver a intentar entrar.');
        } else if ($new_password != $new_password2) {
            $this->new_error_msg('Las contraseñas no coinciden ' . $nick);
        } else if ($new_password == '') {
            $this->new_error_msg('Tienes que escribir una contraseña nueva ' . $nick);
        } else if ($db_password != FS_DB_PASS) {
            $ipFilter->setAttempt($ip);
            $this->new_error_msg('La contraseña de la base de datos es incorrecta ' . $nick);
        } else {
            $suser = $this->user->get($nick);
            if ($suser) {
                $suser->set_password($new_password);
                if ($suser->save()) {
                    $this->new_message('Contraseña cambiada correctamente ' . $nick);
                } else
                    $this->new_error_msg('Imposible cambiar la contraseña del usuario ' . $nick);
            }
        }

        $this->template = 'login/default';
    }

    /**
     * Devuelve la versión de FacturaScripts
     * @return string versión de FacturaScripts
     */
    public function version() {
        if (file_exists('VERSION')) {
            return trim(file_get_contents('VERSION'));
        }

        return '0';
    }

    /**
     * Cierra la conexión con la base de datos.
     */
    public function close() {
        $this->db->close();
    }

    private function new_log_msg($tipo, $detalle, $alerta = FALSE) {
        $fslog = new fs_log();
        $fslog->tipo = $tipo;
        $fslog->detalle = $detalle;
        $fslog->ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        $fslog->alerta = $alerta;
        if ($this->user) {
            $fslog->usuario = $this->user->nick;
        }

        $fslog->save();
    }

    /**
     * Muestra al usuario un mensaje de error
     * @param string $msg el mensaje a mostrar
     */
    public function new_error_msg($msg = '', $tipo = 'error', $alerta = FALSE, $guardar = TRUE) {
        if ($msg && $this->class_name == $this->core_log->controller_name()) {
            /// solamente nos interesa mostrar los mensajes del controlador que inicia todo
            $this->core_log->new_error($msg);
        }

        if ($msg && $guardar) {
            $this->new_log_msg($tipo, $msg, $alerta);
        }
    }

    /**
     * Devuelve la lista de errores
     * @return array lista de errores
     */
    public function get_errors() {
        return $this->core_log->get_errors();
    }

    /**
     * Muestra un mensaje al usuario
     * @param string $msg
     * @param boolean $save
     * @param string $tipo
     * @param boolean $alerta
     */
    public function new_message($msg = '', $save = FALSE, $tipo = 'msg', $alerta = FALSE) {
        if ($msg && $this->class_name == $this->core_log->controller_name()) {
            /// solamente nos interesa mostrar los mensajes del controlador que inicia todo
            $this->core_log->new_message($msg);
        }

        if ($msg && $save) {
            $this->new_log_msg($tipo, $msg, $alerta);
        }
    }

    /**
     * Devuelve la lista de mensajes
     * @return array lista de mensajes
     */
    public function get_messages() {
        return $this->core_log->get_messages();
    }

    /**
     * Muestra un consejo al usuario
     * @param string $msg el consejo a mostrar
     */
    public function new_advice($msg = '') {
        if ($msg AND $this->class_name == $this->core_log->controller_name()) {
            /// solamente nos interesa mostrar los mensajes del controlador que inicia todo
            $this->core_log->new_advice($msg);
        }
    }

    /**
     * Devuelve la lista de consejos
     * @return array lista de consejos
     */
    public function get_advices() {
        return $this->core_log->get_advices();
    }

    /**
     * Devuelve la URL de esta página (index.php?page=LO-QUE-SEA)
     * @return string
     */
    public function url() {
        return $this->page->url();
    }

    /**
     * Devuelve TRUE si el usuario realmente tiene acceso a esta página
     * @return boolean
     */
    private function log_in() {
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        $ipFilter = new fs_ip_filter();
        $nick = filter_input(INPUT_POST, 'user');
        $password = filter_input(INPUT_POST, 'password');

        if ($ipFilter->isBanned($ip)) {
            $ipFilter->setAttempt($ip);
            $this->new_error_msg('Tu IP ha sido baneada. Tendrás que esperar 10 minutos antes de volver a intentar entrar.', 'login', TRUE);
        } else if ($nick AND $password) {
            if (FS_DEMO) { /// en el modo demo nos olvidamos de la contraseña
                $this->login_demo($nick);
            } else {
                $user = $this->user->get($nick);
                if ($user AND $user->enabled) {
                    /**
                     * En versiones anteriores se guardaban las contraseñas siempre en
                     * minúsculas, por eso, para dar compatibilidad comprobamos también
                     * en minúsculas.
                     */
                    if ($user->password == sha1($password) OR $user->password == sha1(mb_strtolower($password, 'UTF8'))) {
                        $user->new_logkey();

                        if (!$user->admin AND ! $ipFilter->inWhiteList($ip)) {
                            $this->new_error_msg('No puedes acceder desde esta IP.', 'login', TRUE);
                        } else if ($user->save()) {
                            setcookie('user', $user->nick, time() + FS_COOKIES_EXPIRE);
                            setcookie('logkey', $user->log_key, time() + FS_COOKIES_EXPIRE);
                            $this->user = $user;
                            $this->load_menu();

                            /// añadimos el mensaje al log
                            $this->new_log_msg('login', 'Login correcto.');
                        } else {
                            $this->new_error_msg('Imposible guardar los datos de usuario.');
                            $this->cache->clean();
                        }
                    } else {
                        $this->new_error_msg('¡Contraseña incorrecta! (' . $nick . ')', 'login', TRUE);
                        $ipFilter->setAttempt($ip);
                    }
                } else if ($user AND ! $user->enabled) {
                    $this->new_error_msg('El usuario ' . $user->nick . ' está desactivado, habla con tu administrador!', 'login', TRUE);
                    $this->user->clean_cache(TRUE);
                    $this->cache->clean();
                } else {
                    $this->new_error_msg('El usuario o contraseña no coinciden!');
                    $this->user->clean_cache(TRUE);
                    $this->cache->clean();
                }
            }
        } else if (filter_input(INPUT_COOKIE, 'user') AND filter_input(INPUT_COOKIE, 'logkey')) {
            $nick = filter_input(INPUT_COOKIE, 'user');
            $logkey = filter_input(INPUT_COOKIE, 'logkey');

            $user = $this->user->get($nick);
            if ($user AND $user->enabled) {
                if ($user->log_key == $logkey) {
                    $user->logged_on = TRUE;
                    $user->update_login();
                    setcookie('user', $user->nick, time() + FS_COOKIES_EXPIRE);
                    setcookie('logkey', $user->log_key, time() + FS_COOKIES_EXPIRE);
                    $this->user = $user;
                    $this->load_menu();
                } else if (!is_null($user->log_key)) {
                    $this->new_message('¡Cookie no válida! Alguien ha accedido a esta cuenta desde otro PC con IP: '
                            . $user->last_ip . ". Si has sido tú, ignora este mensaje.");
                    $this->log_out();
                }
            } else {
                $this->new_error_msg('¡El usuario ' . $nick . ' no existe o está desactivado!');
                $this->log_out(TRUE);
                $this->user->clean_cache(TRUE);
                $this->cache->clean();
            }
        }

        return $this->user->logged_on;
    }

    private function login_demo($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $aux = explode('@', $email);
            $nick = substr($aux[0], 0, 12);
            if ($nick == 'admin') {
                $nick .= $this->random_string(7);
            }

            $user = $this->user->get($nick);
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
                setcookie('user', $user->nick, time() + FS_COOKIES_EXPIRE);
                setcookie('logkey', $user->log_key, time() + FS_COOKIES_EXPIRE);
                $this->user = $user;
                $this->load_menu();
            }
        } else {
            $this->new_error_msg('Email no válido');
        }
    }

    /**
     * Gestiona el cierre de sesión
     * @param boolean $rmuser eliminar la cookie del usuario
     */
    private function log_out($rmuser = FALSE) {
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
        if ($rmuser AND filter_input(INPUT_COOKIE, 'user')) {
            setcookie('user', '', time() - FS_COOKIES_EXPIRE);
            setcookie('user', '', time() - FS_COOKIES_EXPIRE, $path);
        }

        /// guardamos el evento en el log
        $this->new_log_msg('login', 'El usuario ha cerrado la sesión.');
    }

    /**
     * Devuelve la duración de la ejecución de la página
     * @return string un string con la duración de la ejecución
     */
    public function duration() {
        $tiempo = explode(" ", microtime());
        return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
    }

    /**
     * Devuelve el número de consultas SQL (SELECT) que se han ejecutado
     * @return integer
     */
    public function selects() {
        return $this->db->get_selects();
    }

    /**
     * Devuleve el número de transacciones SQL que se han ejecutado
     * @return integer
     */
    public function transactions() {
        return $this->db->get_transactions();
    }

    /**
     * Devuelve el listado de consultas SQL que se han ejecutados
     * @return array lista de consultas SQL
     */
    public function get_db_history() {
        return $this->core_log->get_sql_history();
    }

    /**
     * Carga el menú de facturaScripts
     * @param boolean $reload TRUE si quieres recargar
     */
    protected function load_menu($reload = FALSE) {
        $this->menu = $this->user->get_menu($reload);
    }

    /**
     * Devuelve la lista de menús
     * @return array lista de menús
     */
    public function folders() {
        $folders = array();
        foreach ($this->menu as $m) {
            if ($m->folder != '' AND $m->show_on_menu AND ! in_array($m->folder, $folders)) {
                $folders[] = $m->folder;
            }
        }
        return $folders;
    }

    /**
     * Devuelve la lista de elementos de un menú seleccionado
     * @param string $f el menú seleccionado
     * @return array lista de elementos del menú
     */
    public function pages($f = '') {
        $pages = array();
        foreach ($this->menu as $p) {
            if ($f == $p->folder AND $p->show_on_menu AND ! in_array($p, $pages)) {
                $pages[] = $p;
            }
        }
        return $pages;
    }

    /**
     * Función que se ejecuta si el usuario no ha hecho login
     */
    protected function public_core() {
        
    }

    /**
     * Esta es la función principal que se ejecuta cuando el usuario ha hecho login
     */
    protected function private_core() {
        
    }

    /**
     * Redirecciona a la página predeterminada para el usuario
     */
    public function select_default_page() {
        if ($this->db->connected()) {
            if ($this->user->logged_on) {
                if (is_null($this->user->fs_page)) {
                    $page = 'admin_home';

                    /*
                     * Cuando un usuario no tiene asignada una página por defecto,
                     * se selecciona la primera página del menú.
                     */
                    foreach ($this->menu as $p) {
                        if ($p->show_on_menu) {
                            $page = $p->name;
                            if ($p->important) {
                                break;
                            }
                        }
                    }
                } else {
                    $page = $this->user->fs_page;
                }

                header('Location: index.php?page=' . $page);
            }
        }
    }

    /**
     * Establecemos los elementos por defecto, pero no se guardan.
     * Para guardarlos hay que usar las funciones fs_controller::save_lo_que_sea().
     * La clase fs_default_items sólo se usa para indicar valores
     * por defecto a los modelos.
     */
    private function set_default_items() {
        /// gestionamos la página de inicio
        if (filter_input(INPUT_GET, 'default_page')) {
            if (filter_input(INPUT_GET, 'default_page') == 'FALSE') {
                $this->default_items->set_default_page(NULL);
                $this->user->fs_page = NULL;
            } else {
                $this->default_items->set_default_page($this->page->name);
                $this->user->fs_page = $this->page->name;
            }

            $this->user->save();
        } else if (is_null($this->default_items->default_page())) {
            $this->default_items->set_default_page($this->user->fs_page);
        }

        if (is_null($this->default_items->showing_page())) {
            $this->default_items->set_showing_page($this->page->name);
        }

        $this->default_items->set_codejercicio($this->empresa->codejercicio);

        if (filter_input(INPUT_COOKIE, 'default_almacen')) {
            $this->default_items->set_codalmacen(filter_input(INPUT_COOKIE, 'default_almacen'));
        } else {
            $this->default_items->set_codalmacen($this->empresa->codalmacen);
        }

        if (filter_input(INPUT_COOKIE, 'default_formapago')) {
            $this->default_items->set_codpago(filter_input(INPUT_COOKIE, 'default_formapago'));
        } else {
            $this->default_items->set_codpago($this->empresa->codpago);
        }

        if (filter_input(INPUT_COOKIE, 'default_impuesto')) {
            $this->default_items->set_codimpuesto(filter_input(INPUT_COOKIE, 'default_impuesto'));
        }

        $this->default_items->set_codpais($this->empresa->codpais);
        $this->default_items->set_codserie($this->empresa->codserie);
        $this->default_items->set_coddivisa($this->empresa->coddivisa);
    }

    /**
     * Establece un ejercicio como predeterminado para este usuario.
     * @deprecated since version 2015.039
     * @param string $cod el código del ejercicio
     */
    protected function save_codejercicio($cod) {
        $this->new_error_msg('fs_controller::save_codejercicio() es una función obsoleta.');
    }

    /**
     * Establece un almacén como predeterminado para este usuario.
     * @param string $cod el código del almacén
     */
    protected function save_codalmacen($cod) {
        setcookie('default_almacen', $cod, time() + FS_COOKIES_EXPIRE);
        $this->default_items->set_codalmacen($cod);
    }

    /**
     * Establece un cliente como predeterminado para este usuario.
     * @deprecated since version 2015.039
     * @param string $cod el código del cliente
     */
    protected function save_codcliente($cod) {
        $this->new_error_msg('fs_controller::save_codcliente() es una función obsoleta.');
    }

    /**
     * Establece una divisa como predeterminada para este usuario.
     * @deprecated since version 2015.039
     * @param string $cod el código de la divisa
     */
    protected function save_coddivisa($cod) {
        $this->new_error_msg('fs_controller::save_coddivisa() es una función obsoleta.');
    }

    /**
     * Establece una familia como predeterminada para este usuario.
     * @deprecated since version 2015.039
     * @param string $cod el código de la familia
     */
    protected function save_codfamilia($cod) {
        $this->new_error_msg('fs_controller::save_codfamilia() es una función obsoleta.');
    }

    /**
     * Establece una forma de pago como predeterminada para este usuario.
     * @param string $cod el código de la forma de pago
     */
    protected function save_codpago($cod) {
        setcookie('default_formapago', $cod, time() + FS_COOKIES_EXPIRE);
        $this->default_items->set_codpago($cod);
    }

    /**
     * Establece un impuesto (IVA) como predeterminado para este usuario.
     * @param string $cod el código del impuesto
     */
    protected function save_codimpuesto($cod) {
        setcookie('default_impuesto', $cod, time() + FS_COOKIES_EXPIRE);
        $this->default_items->set_codimpuesto($cod);
    }

    /**
     * Devuelve la fecha actual
     * @return string la fecha en formato día-mes-año
     */
    public function today() {
        return date('d-m-Y');
    }

    /**
     * Devuelve la hora actual
     * @return string la hora en formato hora:minutos:segundos
     */
    public function hour() {
        return Date('H:i:s');
    }

    /**
     * Devuelve un string aleatorio de longitud $length
     * @param integer $length la longitud del string
     * @return string la cadena aleatoria
     */
    public function random_string($length = 30) {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    /**
     * He detectado que algunos navegadores, en algunos casos, envían varias veces la
     * misma petición del formulario. En consecuencia se crean varios modelos (asientos,
     * albaranes, etc...) con los mismos datos, es decir, duplicados.
     * Para solucionarlo añado al formulario un campo petition_id con una cadena
     * de texto aleatoria. Al llamar a esta función se comprueba si esa cadena
     * ya ha sido almacenada, de ser así devuelve TRUE, así no hay que gabar los datos,
     * si no, se almacena el ID y se devuelve FALSE.
     * @param string $id el identificador de la petición
     * @return boolean TRUE si la petición está duplicada
     */
    protected function duplicated_petition($id) {
        $ids = $this->cache->get_array('petition_ids');
        if (in_array($id, $ids)) {
            return TRUE;
        } else {
            $ids[] = $id;
            $this->cache->set('petition_ids', $ids, 300);
            return FALSE;
        }
    }

    /**
     * Devuelve información del sistema para el informe de errores
     * @return string la información del sistema
     */
    public function system_info() {
        $txt = 'facturascripts: ' . $this->version() . "\n";

        if ($this->db->connected()) {
            if ($this->user->logged_on) {
                $txt .= 'os: ' . php_uname() . "\n";
                $txt .= 'php: ' . phpversion() . "\n";
                $txt .= 'database type: ' . FS_DB_TYPE . "\n";
                $txt .= 'database version: ' . $this->db->version() . "\n";

                if (FS_FOREIGN_KEYS == 0) {
                    $txt .= "foreign keys: NO\n";
                }

                if ($this->cache->connected()) {
                    $txt .= "memcache: YES\n";
                    $txt .= 'memcache version: ' . $this->cache->version() . "\n";
                } else {
                    $txt .= "memcache: NO\n";
                }

                if (function_exists('curl_init')) {
                    $txt .= "curl: YES\n";
                } else {
                    $txt .= "curl: NO\n";
                }

                $txt .= 'plugins: ' . join(',', $GLOBALS['plugins']) . "\n";

                if ($this->check_for_updates()) {
                    $txt .= "updated: NO\n";
                }

                if (filter_input(INPUT_SERVER, 'REQUEST_URI')) {
                    $txt .= 'url: ' . filter_input(INPUT_SERVER, 'REQUEST_URI') . "\n------";
                }
            }
        } else {
            $txt .= 'os: ' . php_uname() . "\n";
            $txt .= 'php: ' . phpversion() . "\n";
            $txt .= 'database type: ' . FS_DB_TYPE . "\n";
        }

        foreach ($this->get_errors() as $e) {
            $txt .= "\n" . $e;
        }

        return str_replace('"', "'", $txt);
    }

    /**
     * Devuelve el símbolo de divisa predeterminado
     * o bien el símbolo de la divisa seleccionada.
     * @param string $coddivisa
     * @return string
     */
    public function simbolo_divisa($coddivisa = FALSE) {
        if ($coddivisa === FALSE) {
            $coddivisa = $this->empresa->coddivisa;
        }

        if (isset($this->simbolo_divisas[$coddivisa])) {
            return $this->simbolo_divisas[$coddivisa];
        }

        $divisa = new divisa();
        $divi0 = $divisa->get($coddivisa);
        if ($divi0) {
            $this->simbolo_divisas[$coddivisa] = $divi0->simbolo;
            return $divi0->simbolo;
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
    public function show_precio($precio = 0, $coddivisa = FALSE, $simbolo = TRUE, $dec = FS_NF0) {
        if ($coddivisa === FALSE) {
            $coddivisa = $this->empresa->coddivisa;
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
    public function show_numero($num = 0, $decimales = FS_NF0, $js = FALSE) {
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
    public function euro_convert($precio, $coddivisa = NULL, $tasaconv = NULL) {
        if ($this->empresa->coddivisa == 'EUR') {
            return $precio;
        } else if ($coddivisa AND $tasaconv) {
            if ($this->empresa->coddivisa == $coddivisa) {
                return $precio * $tasaconv;
            } else {
                $original = $precio * $tasaconv;
                return $this->divisa_convert($original, $coddivisa, $this->empresa->coddivisa);
            }
        }

        return $this->divisa_convert($precio, 'EUR', $this->empresa->coddivisa);
    }

    /**
     * Convierte un precio de la divisa_desde a la divisa especificada
     * @param float $precio
     * @param string $coddivisa_desde
     * @param string $coddivisa
     * @return float
     */
    public function divisa_convert($precio, $coddivisa_desde, $coddivisa) {
        if ($coddivisa_desde != $coddivisa) {
            $div0 = new divisa();
            $divisa_desde = $div0->get($coddivisa_desde);
            if ($divisa_desde) {
                $divisa = $div0->get($coddivisa);
                if ($divisa) {
                    $precio = $precio / $divisa_desde->tasaconv * $divisa->tasaconv;
                }
            }
        }

        return $precio;
    }

    /**
     * Añade un elemento a la lista de cambios del usuario.
     * @param string $txt texto descriptivo.
     * @param string $url URL del elemento (albarán, factura, artículos...).
     * @param boolean $nuevo TRUE si el elemento es nuevo, FALSE si se ha modificado.
     */
    public function new_change($txt, $url, $nuevo = FALSE) {
        $this->get_last_changes();
        if (count($this->last_changes) > 0) {
            if ($this->last_changes[0]['url'] == $url) {
                $this->last_changes[0]['nuevo'] = $nuevo;
            } else
                array_unshift($this->last_changes, array('texto' => ucfirst($txt), 'url' => $url, 'nuevo' => $nuevo, 'cambio' => date('d-m-Y H:i:s')));
        } else
            array_unshift($this->last_changes, array('texto' => ucfirst($txt), 'url' => $url, 'nuevo' => $nuevo, 'cambio' => date('d-m-Y H:i:s')));

        /// sólo queremos 10 elementos
        $num = 10;
        foreach ($this->last_changes as $i => $value) {
            if ($num > 0) {
                $num--;
            } else {
                unset($this->last_changes[$i]);
            }
        }

        $this->cache->set('last_changes_' . $this->user->nick, $this->last_changes);
    }

    /**
     * Devuelve la lista con los últimos cambios del usuario.
     * @return array
     */
    public function get_last_changes() {
        if (!isset($this->last_changes)) {
            $this->last_changes = $this->cache->get_array('last_changes_' . $this->user->nick);
        }

        return $this->last_changes;
    }

    /**
     * Elimina la lista con los últimos cambios del usuario.
     */
    public function clean_last_changes() {
        $this->last_changes = array();
        $this->cache->delete('last_changes_' . $this->user->nick);
    }

    /**
     * Devuelve TRUE si hay actualizaciones pendientes (sólo si eres admin).
     * @return boolean
     */
    public function check_for_updates() {
        if (!isset($this->fs_updated)) {
            $this->fs_updated = FALSE;

            if ($this->user->admin) {
                $desactivado = FALSE;
                if (defined('FS_DISABLE_MOD_PLUGINS')) {
                    $desactivado = FS_DISABLE_MOD_PLUGINS;
                }

                if ($desactivado) {
                    $this->fs_updated = FALSE;
                } else {
                    $fsvar = new fs_var();
                    $this->fs_updated = $fsvar->simple_get('updates');
                }
            }
        }

        return $this->fs_updated;
    }

    /**
     * Busca en la lista de plugins activos, en orden inverso de prioridad
     * (el último plugin activo tiene más prioridad que el primero)
     * y nos devuelve la ruta del archivo javascript que le solicitamos.
     * Así usamos el archivo del plugin con mayor prioridad.
     * @param string $filename
     * @return string
     */
    public function get_js_location($filename) {
        foreach ($GLOBALS['plugins'] as $plugin) {
            if (file_exists('plugins/' . $plugin . '/view/js/' . $filename)) {
                return FS_PATH . 'plugins/' . $plugin . '/view/js/' . $filename . '?updated=' . date('YmdH');
            }
        }

        /// si no está en los plugins estará en el núcleo
        return FS_PATH . 'view/js/' . $filename . '?updated=' . date('YmdH');
    }

    /**
     * Devuelve el tamaño máximo permitido para subir archivos.
     * @return integer
     */
    public function get_max_file_upload() {
        return fs_get_max_file_upload();
    }

}
