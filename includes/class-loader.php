<?php
/**
 * Registra todas las acciones y filtros para el plugin
 */
class EnglishLine_Test_Loader {

    /**
     * El array de acciones registradas con WordPress.
     *
     * @var      array    $actions    Las acciones registradas con WordPress para ejecutar cuando el plugin se carga.
     */
    protected $actions;

    /**
     * El array de filtros registrados con WordPress.
     *
     * @var      array    $filters    Los filtros registrados con WordPress para ejecutar cuando el plugin se carga.
     */
    protected $filters;

    /**
     * Inicializa las colecciones usadas para mantener las acciones y filtros.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Añade una nueva acción al array de acciones que se van a registrar con WordPress.
     *
     * @param    string               $hook             El nombre de la acción de WordPress que está siendo registrada.
     * @param    object               $component        Una referencia a la instancia del objeto donde se define la acción.
     * @param    string               $callback         El nombre de la función definida en el $component.
     * @param    int      Optional    $priority         La prioridad a la que la función será ejecutada.
     * @param    int      Optional    $accepted_args    El número de argumentos que deben pasarse a la función $callback.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añade un nuevo filtro al array de filtros que se van a registrar con WordPress.
     *
     * @param    string               $hook             El nombre del filtro de WordPress que está siendo registrado.
     * @param    object               $component        Una referencia a la instancia del objeto donde se define el filtro.
     * @param    string               $callback         El nombre de la función definida en el $component.
     * @param    int      Optional    $priority         La prioridad a la que la función será ejecutada.
     * @param    int      Optional    $accepted_args    El número de argumentos que deben pasarse a la función $callback.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Una utilidad para registrar los hooks en la colección.
     *
     * @access   private
     * @param    array                $hooks            Colección de hooks a registrar con WordPress.
     * @param    string               $hook             El nombre del filtro de WordPress que está siendo registrado.
     * @param    object               $component        Una referencia a la instancia del objeto donde se define el filtro.
     * @param    string               $callback         El nombre de la función definida en el $component.
     * @param    int                  $priority         La prioridad a la que la función será ejecutada.
     * @param    int                  $accepted_args    El número de argumentos que deben pasarse a la función $callback.
     * @return   array                                  La colección de acciones con el nuevo hook registrado.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registra los filtros y acciones con WordPress.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}