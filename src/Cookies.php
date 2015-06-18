<?php
namespace jlawrence\lw;

/**
 * Cookies Class
 *
 * This is a simple module which will allow you to securely set and retrieve
 * cookies from the user.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 */
class Cookies
{
    /**
     * Hold reference to lw\Factory object
     */
    private $site;

    /**
     * Holds the public key
     */
    private $pubKey;

    /**
     * Holds the private key
     */
    private $privKey;

    //construct
    public function __construct (Factory $site, $cfg){
        $this->site = $site;
        $this->pubKey = $cfg['publicKey'];
        $this->privKey = $cfg['privateKey'];
    }

    /**
     * Set a cookie, encrypting it via the public key
     *
     * @param String $name Name of the cookie to set
     * @param String $data Cookie information
     */
    public function set($name, $data) {
        //encrypt the data first
        $data = $this->site->crypt->secureSend($data, $this->pubKey);
        setcookie($name, $data, time()+60*60*24*365, "/");
        $_COOKIE[$name] = $data;
    }

    /**
     * Set a plain-text cookie, allowing for expiration
     *
     * If no expiration is set, defaults a year
     *
     * @param String $name Name of the cookies to set
     * @param String $data The cookies data to set
     * @param Mixed $exp Expiration
     */
    public function plainSet($name, $data, $exp=null) {
        $exp = (is_null($exp)) ? (time()+60*60*24*365) : intval($exp);
        $_COOKIE[$name] = $data;
        setcookie($name, $data, $exp, "/");
    }

    /**
     * Gets the cookie information
     *
     * @param String $name The name of the cookie
     * @return String Decrypted cookie value
     */
    public function get($name) {
        $data = $_COOKIE[$name];
        $data = $this->site->crypt->secureReceive($data, $this->privKey);
        return $data;
    }

    /**
     * Deletes a cookie from client
     *
     * @param String $name Name of the cookie to remove
     */
    public function delete($name) {
        unset($_COOKIE[$name]);
        setcookie($name, '', time()-3600, "/");
    }

}
?>