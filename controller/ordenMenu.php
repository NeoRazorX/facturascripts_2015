<?php

include_once 'defines.inc';

require_model('fs_page.php');

/**
 * Description Ordenar menú
 *
 * @author alagoro
 */
class ordenMenu extends fs_controller {

    private $folders = [];
    private $paginas = [];

    public function __construct() {
        parent::__construct(__CLASS__, 'Ordenar menú', 'admin', FALSE, TRUE);
    }

    protected function private_core() {

        if (!is_null(filter_input(INPUT_POST, 'accion'))) {
            $accion = filter_input(INPUT_POST, 'accion');
            switch ($accion) {
                case AL_ACCION_GRABAR :
                    $this->guardar_orden();
                    break;

                default:
                    break;
            }
        }



        $mimenu = $this->user->get_menu();
        foreach ($mimenu as $menuitem) {
            if (!in_array($menuitem->folder, $this->folders))
                $this->folders[] = $menuitem->folder;
            $this->paginas[$menuitem->folder][] = ['name' => $menuitem->name, 'title' => $menuitem->title, 'orden' => $menuitem->orden];
        }
    }

    private function guardar_orden() {
        $this->template = FALSE;
        $resultado = [];
        $elementos = filter_input(INPUT_POST, 'elementos');
        if ($elementos) {
            $elementos = explode(',', $elementos);

            $page = new fs_page();
            foreach ($elementos as $orden => $elemento) {
                $page->save_orden($elemento, $orden);
            }
            $resultado['ERROR'] = 0;
            $resultado['MENSAJE'] = 'Elementos ordenados.';
        } else {
            $resultado['ERROR'] = 1;
            $resultado['MENSAJE'] = 'No hay elementos.';
        }
        return $resultado;
    }

    public function getMenuFolders() {
        return $this->folders;
    }

    public function getMenu($folder) {

        return isset($this->paginas[$folder]) ? $this->paginas[$folder] : [];
    }
   public function url()
   {
         return 'index.php?page=ordenMenu';
   }

}
