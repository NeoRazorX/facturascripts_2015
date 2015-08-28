<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *public $mostrar;
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('agente.php');
require_model('fs_log.php');
require_model('fs_user.php');


class admin_info extends fs_controller
{
   public $mostrar;
   public $desde;
   public $hasta;
   public $offset;
   public $order;
   public $fs_log;
   public $resultados;
   public $fs_user;
   public $num_resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Información del sistema', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $fsvar = new fs_var();
      $cron_vars = $fsvar->array_get( array('cron_exists' => FALSE, 'cron_lock' => FALSE, 'cron_error' => FALSE) );
      $this->agente = new agente();
      if( isset($_GET['fix']) )
      {
         $cron_vars['cron_error'] = FALSE;
         $cron_vars['cron_lock'] = FALSE;
         $fsvar->array_save($cron_vars);
      }
       
       $this->desde = '';
       $this->hasta = '';
       $this->fs_user = '';
       $fs_log = new fs_log();
       $user = new fs_user();
       $this->num_resultados = '';
       
        $this->offset = 0;      
        if( isset($_REQUEST['offset']) )
        {
           $this->offset = intval($_REQUEST['offset']);
        }
       
        if( isset($_GET['order']) )
         {
            if($_GET['order'] == 'fecha_asc')
            {
               $this->order = 'fecha ASC';
            }
            if($_GET['order'] == 'fecha_desc')
            {
               $this->order = 'fecha DESC';
            }
 
         setcookie('admin_info_order', $this->order, time()+FS_COOKIES_EXPIRE);
        }
        else
             $this->order = 'fecha DESC';
        
       if( isset($_GET['mostrar']) )
        {
         $this->mostrar = $_GET['mostrar'];
         setcookie('admin_info_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
         }
       
      if( !$cron_vars['cron_exists'] )
      {
         $this->new_advice('Nunca se ha ejecutado el <a href="http://www.facturascripts.com/comm3/index.php?page=community_item&tag=cron" target="_blank">cron</a>,'
                 . ' te perderás algunas características interesantes de FacturaScripts.');
      }
      else if( $cron_vars['cron_error'] )
      {
         $this->new_error_msg('Parece que ha habido un error con el cron. Haz clic <a href="'.$this->url().'&fix=TRUE">aquí</a> para corregirlo.');
      }
      else if( $cron_vars['cron_lock'] )
      {
         $this->new_advice('Se está ejecutando el cron.');
      }
      
      if( isset($_GET['clean_cache']) )
      {
         /// borramos los archivos php del directorio tmp
         foreach( scandir(getcwd().'/tmp') as $f)
         {
            if( substr($f, -4) == '.php' )
               unlink('tmp/'.$f);
         }
         
         if( $this->cache->clean() )
         {
            $this->new_message("Cache limpiada correctamente.");
         }
      }
      if( isset($_REQUEST['query']) OR isset($_REQUEST['fs_user']) )
            {
               $this->mostrar = 'buscar';
               $this->desde = $_REQUEST['desde'];
               $this->hasta = $_REQUEST['hasta'];
               $this->fs_user = $_REQUEST['fs_user'];
               $this->buscar();
            }
      else
      {
           $order = $this->order;
           $offset = $this->offset;
           $this->resultados = $fs_log->all($order,$offset);
      }
      
   }
   
   public function linux()
   {
      return (php_uname('s') == 'Linux');
   }
   
   public function uname()
   {
      return php_uname();
   }
   
   public function php_version()
   {
      return phpversion();
   }
   
   public function cache_version()
   {
      return $this->cache->version();
   }
   
   public function sys_uptime()
   {
      system('uptime');
   }
   
   public function sys_df()
   {
      system('df -h');
   }
   
   public function sys_free()
   {
      system('free -m');
   }
   
   public function fs_db_name()
   {
      return FS_DB_NAME;
   }
   
   public function fs_db_version()
   {
      return $this->db->version();
   }
   
   public function get_locks()
   {
      return $this->db->get_locks();
   }
   
   public function get_db_tables()
   {
      return $this->db->list_tables();
   }
   
   public function get_fs_log()
   {
      $fslog = new fs_log();
      return $fslog->all();
   }
   
   private function buscar()
   {
      $this->resultados = array();
      $this->num_resultados = 0;
      $query = $this->agente->no_html( strtolower($this->query) );
      $sql = " FROM fs_logs ";
      $where = 'WHERE ';
      
      if($this->query != '')
      {
         $sql .= $where;
         if( is_numeric($query) )
         {
            $sql .= "(detalle LIKE '%".$query."%')";
         }
         else
         {
            $sql .= "(lower(detalle) LIKE '%".$query."%')";
         }
         $where = ' AND ';
      }
      
      if($this->fs_user != '')
      {
         $sql .= $where."usuario = ".$this->agente->var2str($this->fs_user);
         $where = ' AND ';
      }
     
      if($this->desde != '')
      {
         $sql .= $where."fecha >= ".$this->agente->var2str($this->desde);
         $where = ' AND ';
      }
      
      if($this->hasta != '')
      {
         $sql .= $where."fecha <= ".$this->agente->var2str($this->hasta);
         $where = ' AND ';
      }
      
        $data2 = $this->db->select_limit("SELECT *".$sql." ORDER BY ".$this->order, FS_ITEM_LIMIT, $this->offset);
         if($data2)
         {
            foreach($data2 as $d)
            {
               $this->resultados[] = new fs_log($d);
            }
         }
      
   }
    public function anterior_url()
   {
      $url = '';
      
      if($this->offset > 0)
      {
         $url = $this->url()."&mostrar=".$this->mostrar
                 ."&query=".$this->query
                 ."&fs_user=".$this->fs_user
                 ."&desde=".$this->desde
                 ."&hasta=".$this->hasta
                 ."&offset=".($this->offset-FS_ITEM_LIMIT);
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      
      if( count($this->resultados) == FS_ITEM_LIMIT )
      {
         $url = $this->url()."&mostrar=".$this->mostrar
                 ."&query=".$this->query
                 ."&fs_user=".$this->fs_user
                 ."&desde=".$this->desde
                 ."&hasta=".$this->hasta
                 ."&offset=".($this->offset+FS_ITEM_LIMIT);
      }
      
      return $url;
   }
}
