<?php
namespace jlawrence\lw;

/**
 * Sessions Class
 *
 * This is a core class that will help facilitate the use of sessions in a way
 * to prevent session riding.  Because this class hooks in to the user class
 * it will also prevent session hijacking by way of the user class cookies
 * and its methods.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 * @version $Id: sessions.class.php 34 2013-03-01 10:40:33Z jlawrence11 $
 */
class Sessions
{
    /**
     * Hold reference to LW_Site object
     */
    private $site;

    //construct
    public function __construct (Factory $site){
        session_start();
        $this->site = $site;
        //Now we'll set up the events we want to catch
        //-----
        //User login is elevated privileges, so we clear and start a new session
        $this->site->event->hook('user.login', array(&$this, 'clearSession'));
        //The following is performed every time this class is loaded, so we don't need it registered
        //$this->site->event->hook('user.cookieLogin', array(&$this, 'check'));
        //The next two a de-elevating privileges, so we clear out the session
        $this->site->event->hook('user.invalidCookie', array(&$this, 'clearSession'));
        $this->site->event->hook('user.logout', array(&$this, 'clearSession'));
        //since the session is started now, we'll preform the check now
        //if for no other reason than to make sure the the session variables
        //we check are set.
        $this->check();
    }

    /**
     * Assign session variables for checking
     *
     * Assigns a couple of session variables to check against when a change
     * of permissions happens. ie a user logs in, makes sure the sessions match
     * some variables.
     */
    private function setSession() {
        //Get the remote_addr and the user agent and assign them
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Check the session info and set it/destroy it as needed
     */
    public function check() {
        //check if we have the remote addr and user agent
        if(!isset($_SESSION['user_agent'])) {
            $this->setSession();
        }

        $iSet = (($_SESSION['user_agent'] == $_SERVER['HTTP_USER_AGENT']) && ($_SESSION['remote_addr'] == $_SERVER['REMOTE_ADDR']));

        //if the values don't match
        if(!$iSet) {
            //Clear the session to start a new one
            $this->clearSession();
        }
    }

    /**
     * Clear Session
     *
     * Will clear all session variables
     */
    public function clearSession() {
        foreach($_SESSION as $name => $value) {
            unset($_SESSION[$name]);
        }
        session_regenerate_id(true);
        $this->setSession();
    }

}