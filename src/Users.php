<?php
namespace jlawrence\lw;
/**
 * User Class
 *
 * The user class.  Handles all things user-related, invluding logging in,
 * registering, cookie login, and logout.
 * 3/2/13 Fully updated to use PDO instead of MySQL.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 * @version $Id: users.class.php 20 2013-03-21 22:40:44Z jlawrence11 $
 */
class Users
{
    /**
     * Hold reference to LW_Site object
     */
    protected $site;

    /**
     * Array of logged in user info
     */
    protected $userInfo;

    /**
     * User ID of successfully logged in user
     */
    protected $userID;

    /**
     * Variable setting whether or not user is logged in
     */
    protected $loggedIn;

    /**
     * Whether or not the user wanted to be remembered
     */
    protected $rememberMe;

    /**
     * Whether or not user is an admin, for ease of access
     */
    protected $admin;

    /**
     * Constructing the object
     *
     * Will initialize the users class
     *
     * @uses LW_crypt
     * @uses LW_pdo
     * @uses LW_cookies
     */
    public function __construct (Factory $site){
        $this->site = $site;
        /*
         * Autoload dependencies
         */
        //$this->site->loadCores("pdo,crypt,template,cookies");

        //register the class' display function with the template wrapper
        $this->site->template->registerDisplay($this, 'user');
        //auto-set user to logged off
        $this->loggedIn = false;
        $this->admin = false;
        $this->rememberMe = false;
        //See if the cookies exist to log a user in.
        $this->cookieLogin();

    }

    /**
     * Returns the logged in user's ID
     */
    public function getUserId() {
        return $this->userID;
    }

    /**
     * Display handler
     *
     * Hooks to the template engine to determine which template to
     * user and what variables are included in to that template
     */
    public function getDisplay() {
        $ret = array();
        $ret['var_name'] = 'users';
        if($this->isLoggedIn()) {
            $ret['template_file'] = "user_logged_in.tpl";
            $vars = $this->getUserInfo();
        } else {
            $ret['template_file'] = "user_login_form.tpl";
            $vars = null;
        }
        $ret['vars'] = $vars;
        //$this->site->debug->warning('<pre>'. print_r($ret, true) .'</pre>');
        return $ret;
    }

    /**
     * Is there a user logged in?
     */
    public function isLoggedIn() {
        return $this->loggedIn;
    }

    /**
     * Is the user an admin?
     */
    public function isAdmin() {
        if(($this->loggedIn===true) && ($this->admin == true)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check to see if user exists
     *
     * Checks to see if either the username or the email address
     * is already registered
     *
     * @param String $uname Username to check
     * @param String $email Email address to check
     * @return Boolean Whether or not user exists
     */
    public function userExists($uname, $email) {

        $param = array($uname, $email);
        $sql = "SELECT * FROM ". $this->site->pdo->TP ."users WHERE uname=? OR email=?";
        $this->site->pdo->query($sql, $param, true);
        $rows = $this->site->pdo->numRows();
        if($rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * User registration
     *
     * Will preform checks to make sure the user doesn't already exist, hash
     * the password, and insert it in to the database if no errors are found.
     *
     * @param String $uname Username to register
     * @param String $email Email address to associate
     * @param String $ufname User first name
     * @param String $ulname User last name
     * @param String $pass Plain-text password to use
     * @param String $birthday In string format
     * @param Boolean $eActivate Whether requires email activation
     * @param Integer $ulevel User level, defaults to 0
     * @param Integer $admin Admin or no (1 | 0), defaults no
     * @return Integer The uid of the registered user, or false on fail
     */
    public function registerUser($uname, $email, $ufname, $ulname, $pass, $birthday, $eActivate=true, $ulevel=0, $admin=0) {
        // mysql class functions are commented out in favor of following PDO methods
        if($this->userExists($uname, $email)) {
            return false;
        }

        $regTime = time();
        $bday = strtotime($birthday);
        $ulevel = intval($ulevel);
        $admin = intval($admin);
        $pass = $this->site->crypt->passHash($pass, $regTime);
        $active = ($eActivate==true) ? 0 : 1;
        $iArray = array(
            'uname' => $uname,
            'ufname' => $ufname,
            'ulname' => $ulname,
            'email' => $email,
            'ulevel' => $ulevel,
            'admin' => $admin,
            'birthday' => $bday,
            'regtime' => $regTime,
            'regactive' => $active,
            'pass' => $pass
        );
        //if(!$this->site->db->insert('users', $iArray)) {
        //    return false;
        //}
        if(!$this->site->pdo->insert('users', $iArray)) {
            return false;
        }
        //$uid = $this->site->db->getInsertID();
        $uid = $this->site->pdo->getInsertID();
        if($eActivate==true) {
            //$this->site->checkDependency("email", true);
            $this->site->email->setTemplate("reg_email.tpl");
            $hash = md5($this->site->crypt->generatePassword());
            $iArray = array(
                'uid' => $uid,
                'email' => $email,
                'uname' => $uname,
                'hash' => $hash
            );
            //$this->site->db->insert('user_activate', $iArray);
            $this->site->pdo->insert('user_activate', $iArray);
            $iArray = array(
                'url' => $this->site->url,
                'title' => $this->site->title,
                'email' => $email,
                'hash' => $hash
            );
            $this->site->email->send($email, "Welcome to ". $this->site->title ."!", $iArray);
        }
        return $uid;
    }

    /**
     * Activate user
     *
     * Will activate a user account based on the email/hash that was sent during
     * the registration process.
     *
     * @param String $email Email address
     * @param String $hash Hash that is connected to email address
     * @return Bool Whether successful or not.
     */
    public function activateUser($email, $hash) {
        // mysql class functions are commented out in favor of following PDO methods
        $tp = $this->site->pdo->TP;
        //$email = $this->site->db->sanitize($email);
        //$hash = $this->site->db->sanatize($hash);
        $param = array($email, $hash);
        //$query = "SELECT uid, email, hash, uname FROM {$tp}user_activate WHERE email='{$email}' AND hash='{$hash}' LIMIT 1";
        $query = "SELECT uid, email, hash, uname FROM {$tp}user_activate WHERE email=? AND hash=? LIMIT 1";
        //$result = $this->site->db->query($query);
        $result = $this->site->pdo->query($query, $param, true);
        if(!$result['uid']) {
            return false;
        }

        $query = "UPDATE {$tp}users SET regactive=1 WHERE id=". $result['uid'];
        //$r1 = $this->site->db->query($query);
        $r1     = $this->site->pdo->query($query);
        $query = "DELETE FROM {$tp}user_activate WHERE uid=". $result['uid'];
        //$r2 = $this->site->db->query($query);
        $r2 = $this->site->pdo->query($query);
        return (($r1 !== false) && ($r2 !== false));
    }

    /**
     * User login
     *
     * Will attempt to log in a user by their username and password,
     * if successfully will set encrypted cookies with the username
     * and password hash as well as a randomly generated string
     * to check against the database to allow cookie login/sessions.
     * If $remember is set, will remember the user for a year - or
     * until they log in from a different browser.
     *
     * @param String $user Username to log in
     * @param String $pass Plain-text password to identify the user
     * @param Int $remember Whether or not to remember the user for a year, or just current session
     * @return Int The user id, if the login was successful
     */
    public function login($user, $pass, $remember=0) {
        // mysql class functions are commented out in favor of following PDO methods
        //$user = $this->site->db->sanitize($user);
        //$pass = $this->site->db->sanitize($pass);

        $tp = $this->site->pdo->TP;
        //Get the registration time of the user trying to log in:
        //$regtime = $this->site->db->query("select regtime from {$tp}users where uname='$user' limit 1");
        $regtime = $this->site->pdo->query("select regtime from {$tp}users where uname=? limit 1", array($user), true);
        $regtime = $regtime['regtime'];
        $pHash = $this->site->crypt->passHash($pass, $regtime);
        $param = array($user, $pHash);
        //$sql = "SELECT id FROM {$tp}users WHERE uname='{$user}' AND pass='{$pHash}' AND regactive=1 LIMIT 1";
        $sql = "SELECT id FROM {$tp}users WHERE uname=? AND pass=? AND regactive=1 LIMIT 1";
        //$uid = $this->site->db->query($sql);
        $uid = $this->site->pdo->query($sql, $param, true);
        //If user/pass combo don't work, return false
        if(!$uid['id']) return false;

        $this->userID = $uid['id'];
        $this->loggedIn = true;
        $this->site->debug->notice("Successfully logged in '{$user}'");
        //if $remember, set the class variable
        if($remember!=0) {
            $this->rememberMe = true;
        }
        //Cookies must be enabled for the user to log in..at all
        $this->setupCookies($user, $pHash);
        $this->populateClass();
        $this->site->event->trigger('user.login');
        return $this->userID;
    }

    /**
     * Cookie Login
     *
     * Will check the cookies for the authentication information and the
     * cookiehash value to compare to the database.  If the cookiehash
     * has changed (user logged in from a different browser/machine),
     * will destroy the cookies, and not successfully log in.  This meothod
     * is part of the __construct() and is automatically called when the
     * class is loaded.
     */
    protected function cookieLogin() {
        // mysql class functions are commented out in favor of following PDO methods
        //The cookie hash doesn't exist as a cookie, no cookie login possible
        if(!isset($_COOKIE['userHash']) || ($_COOKIE['userHash']=="")) {
            $this->site->debug->notice("No cookie login");
            $this->destroyCookie();
            return false;
        }
        $c = $this->site->cookie->get('userLogin');
        $cArray = unserialize($c);
        if(!$cArray['user']) {
            //cookie didn't have a user variable, so no cookie login
            //destroy the cookie, just in case
            $this->site->debug->warning("'userHash' exists, but credentials did not.");
            $this->destroyCookie();
            return false;
        }
        $tp = $this->site->pdo->TP;
        $user = $cArray['user'];
        $pHash = $cArray['pass'];
        $this->rememberMe = $cArray['remember'];
        $cookieHash = $_COOKIE['userHash'];
        $param = array($user, $pHash, $cookieHash);
        //$sql = "SELECT id FROM {$tp}users WHERE uname='{$user}' AND pass='{$pHash}' AND cookieHash='{$cookieHash}' LIMIT 1";
        $sql = "SELECT id FROM {$tp}users WHERE uname=? AND pass=? AND cookieHash=? LIMIT 1";
        //$uid = $this->site->db->query($sql);

        $uid = $this->site->pdo->query($sql, $param, true);
        //echo "<pre>". print_r($param, true) ."</pre>";
        if(!$uid['id']) {
            //remove the cookie, it was no longer valid
            $this->site->debug->warning("Cookie information was invalid, destroyed it.". "<pre>". print_r($param, true) ."</pre>");
            $this->destroyCookie();
            $this->site->event->trigger('user.invalidCookie');
            return false;
        }

        $this->userID = $uid['id'];
        $this->loggedIn = true;
        $this->setupCookies($user, $pHash);
        $this->site->debug->notice("Successful login of '{$user}' via Cookies");
        $this->populateClass();
        $this->site->event->trigger('user.cookieLogin');
        return true;
    }

    /**
     * Log out the user
     *
     * Destroy the cookies associated with the user, and remove the
     * cookieHash from the database so it can't be abused
     */
    public function logout() {
        // mysql class functions are commented out in favor of following PDO methods
        if(!$this->isLoggedIn()) {
            //user isn't logged in, quietly exit
            return false;
        }
        $uid = $this->userID;
        //clear the cookieHash from the DB
        $sql = "UPDATE ". $this->site->pdo->TP ."users SET cookieHash='' WHERE id='{$uid}'";
        //$this->site->db->query($sql);
        $this->site->pdo->query($sql);
        unset($uid);
        //Destroy the cookies and clear out the class variables
        $this->destroyCookie();
        $this->userID = null;
        $this->userInfo = null;
        $this->admin = false;
        $this->loggedIn = false;
        $this->rememberMe = false;
        $this->site->event->trigger('user.logout');
        return true;
    }

    /**
     * Set cookies
     *
     * Will set the encrypted username, password hash cookie as well
     * as the plain-text cookieHash cookie for comparison on LW_users::cookieLogin()
     *
     * @param String $user Username to set
     * @param String $pHash Password hash to use
     */
    protected function setupCookies ($user, $pHash) {
        // mysql class functions are commented out in favor of following PDO methods
        $cHash = md5($this->site->crypt->generatePassword());
        $param = array($cHash, time(), $user);
        //sql to update the user's cookiehash to $cHash
        //$sql = "UPDATE ". $this->site->pdo->TP ."users SET cookieHash='{$cHash}' WHERE uname='{$user}'";
        $sql = "UPDATE ". $this->site->pdo->TP ."users SET cookieHash=?, lastlog=? WHERE uname=?";
        //$this->site->db->query($sql);
        $this->site->pdo->query($sql, $param);
        //Set the plain text cHash cookie based on the remember me
        if($this->rememberMe == true) {
            $this->site->cookie->plainSet('userHash', $cHash);
        } else {
            //expires at the end of the browser session
            $this->site->cookie->plainSet('userHash', $cHash, 0);
        }
        //Create the cookies array
        $cArray = array(
            'user' => $user,
            'pass' => $pHash,
            'remember' => $this->rememberMe
        );
        $c = serialize($cArray);
        //Public-key encrypt the $cArray while setting the cookie
        $this->site->cookie->set('userLogin', $c);
    }

    /**
     * Destroy Cookies
     *
     * Will destroy the cookies on the client's machine
     */
    protected function destroyCookie() {
        $this->site->cookie->delete('userLogin');
        $this->site->cookie->delete('userHash');
    }

    /**
     * Populate class
     *
     * Will populate the class variables with the logged-in user
     * information by way of the database
     */
    protected function populateClass() {
        // mysql class functions are commented out in favor of following PDO methods
        if(!$this->isLoggedIn()) {
            return false;
        }

        $uid = $this->userID;
        $tp = $this->site->pdo->TP;
        $sql = "SELECT id, uname, email, ufname, ulname, ulevel, admin, regtime, birthday, lastlog FROM {$tp}users WHERE id={$uid} LIMIT 1";
        //$user = $this->site->db->query($sql);
        $user = $this->site->pdo->query($sql, null, true);
        $this->admin = $user['admin'];
        $this->userInfo = $user;
        //$this->site->debug->warning("Populated user");
        return true;
    }

    /**
     * Get user information
     *
     * Public method to get the protected $userInfo array
     */
    public function getUserInfo() {
        return $this->userInfo;
    }
}