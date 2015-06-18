<?php
namespace jlawrence\lw;
/**
 * Events Class
 *
 * This class with handle the 'hooking' and 'triggering' of events for the
 * LW system.  This will load before the debug class automatically and allow
 * other classes to 'hook' in to specific events such as 'log in' and 'log out'
 * as well as other user-defined triggers.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 * @version $Id: events.class.php 34 2013-03-01 10:40:33Z jlawrence11 $
 */
class Events
{
    /**
     * Hold reference to LW_Site object
     */
    private $site;

    /**
     * Holds the hooks in the system
     */
    private $hooks;

    //construct
    public function __construct (Factory $site){
        $this->site = $site;
        //use of $cfg array goes here
    }

    /**
     * Hook in to an event
     *
     * Core classes can trigger events that other classes can 'hook' in to.
     * This method allos for the hooking based on the event name, and a callback
     * that can be defined from another core class in the form of:
     * array(& $this, 'methodName') to call when an event happens
     *
     * @param String $eventName Event you want to hook in to
     * @param Mixed $callback Callback to use when event happens
     */
    public function hook($eventName, $callback) {
        //make sure the callback is valid
        if(is_callable($callback)) {
            $this->hooks[$eventName][] = $callback;
        }
    }

    /**
     * Trigger event
     *
     * Core classes can trigger events that other classes can hook in to, this
     * method is used to trigger an event.
     *
     * @param String $eventName event name to trigger
     * @return Array An array of responses from hooks, if any.
     */
    public function trigger($eventName){
        $hooks = isset($this->hooks[$eventName]) ? $this->hooks[$eventName] : null;
        //if no hooks are registered for the event, quit function
        if(!is_array($hooks)) {
            return false;
        }
        $ret = array();
        foreach($hooks as $callback) {
            $ret[] = call_user_func($callback);
        }
        return $ret;
    }

}