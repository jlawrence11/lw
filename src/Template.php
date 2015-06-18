<?php
namespace jlawrence\lw;

/**
 * Template Class
 *
 * This class is a 'wrapper' for smarty.  We don't extend smarty as we don't
 * want anything using Smarty directly, we want the templating functions
 * to be passed through this class so that if a programmer wants to change
 * the templating engine, it can easily be replaced (modular design).
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 */
class Template
{
    /**
     * Hold reference to Factory object
     */
    private $site;

    /**
     * Holds the smarty object
     */
    private $smarty;

    /**
     * Hold registered templates to be loaded
     */
    private $rDisplay;

    /**
     * Hold assigned variables for the template
     */
    private $displayVars;

    /**
     * Holds the header information array
     */
    private $header = array();

    /**
     * Holds the every-page title from the config
     */
    private $title;
    private $url;

    /**
     * Holds the template dir
     */
    private $templateDir;

    /**
     * Stores the body template
     */
    private $bodyTemp;

    /**
     * Template Constructor
     *
     * Will initialize the template class
     */
    public function __construct (Factory $site, $cfg){
        $this->site = $site;
        $this->title = $this->site->title;
        $this->url = $this->site->url;
        $temp = $site->baseDir . $cfg['templateDir'] . DIRECTORY_SEPARATOR;
        $comp = $temp . $cfg['compileDir'] . DIRECTORY_SEPARATOR;
        //echo "<pre>". print_r($cfg, true) ."\n". $temp ."\n". $comp . "</pre>";

        $this->smarty = new \Smarty();
        $this->smarty->setCompileDir($comp);
        $this->templateDir = $temp;
        $this->bodyTemp = "";
    }

    /**
     * Register Display
     *
     * Allows classes to register to be called when the display happens
     *
     * @param Object $objRef The reference to the class that wants to be called
     * @param String $varName The variable name that the return from the method will be put in to
     */
    public function registerDisplay(& $objRef, $varName) {
        $this->rDisplay[$varName] = $objRef;
    }

    /**
     * Set a template for the body (otherwise, programmer needs to assign it manually)
     *
     * @param String $tName The template filename
     */
    public function bodyTemplate($tName) {
        $this->bodyTemp = $tName;
    }

    /**
     * Get lone template
     *
     * This moethod exists solely for the implimentation of email templates
     * so that the site programmer can use the same templating functions
     * but leave the available variables out of the main page template.
     *
     * @param String $tFile The template filename to use
     * @param Array $vars The associative array of variables to be assigned
     * @return String HTML template result
     */
    public function loneTemplate($tFile,  $vars) {
        //Create a new smarty object just for this function
        $smarty = new \Smarty();
        $smarty->setCompileDir($this->smarty->getCompileDir());
        $smarty->debugging = true;
        foreach($vars as $name => $val) {
            $smarty->assign($name, $val);
        }
        $smarty->assign('baseUrl', $this->url);

//        echo '<pre>'. $this->templateDir . $tFile ."\n";
//        var_dump($smarty);
//        echo '</pre>';

        $ret = $smarty->fetch($this->templateDir . $tFile);
        //$smarty->display($this->templateDir . $tFile);
        unset($smarty);
//        echo 'here';
        return $ret;
    }

    /**
     * Add Header
     *
     * Adds a line to the template $header variable
     *
     * @param String $head Line to add to the template variable
     */
    public function addHeader($head) {
        $this->header[] = $head;
    }

    /**
     * Add headers
     *
     * Adds multiple (by array) headers to the $header variable
     *
     * @param Array $heads Array of headers to add
     */
    public function addHeaders($heads) {
        if(!is_array($heads)) {
            return;
        }
        foreach($heads as $head) {
            $this->header[] = $head;
        }
    }

    /**
     * Assign variables
     *
     * Will assign variables that will be used when display is called.
     *
     * @param String $name Name of variable to use
     * @param String $value Value/Data to assign to the variable
     */
    public function assign ($name, $value) {
        $this->displayVars[$name] = $value;
    }

    /**
     * Display
     *
     * The display method for the template wrapper.  Will assign the variables
     * to Smarty, call the registered displays and assign their results to either
     * a display variable, or load a template based on the returns.  Will then
     * load 'main.tpl' from the template directory and output it to the browser
     */
    public function display() {
        $this->smarty->assign('baseUrl', $this->url);
        foreach($this->rDisplay as $name => $obj) {
            $tmp = call_user_func(array(&$obj, 'getDisplay'));
            $tName = $tmp['template_file'];
            $varName = $tmp['var_name'];
            $myVars = $tmp['vars'];
            if(!isset($tmp['raw']) || $tmp['raw'] !== true) {
                $this->smarty->assign($varName, $myVars);
                $mTmp = $this->smarty->fetch($this->templateDir . $tName);
                $this->smarty->assign($name, $mTmp);
                //echo $mTmp;
            } else {
                if(isset($tmp['output'])) {
                    $this->smarty->assign($name, $tmp['output']);
                }
                //echo $tmp['output'];
            }
        }
        $headers = implode("\n", $this->header);
        $this->smarty->assign('headers', $headers);

        //now loop through the display variables and assign them to smarty
        foreach($this->displayVars as $name => $value) {
            $this->smarty->assign($name, $value);
        }

        if($this->bodyTemp != "") {
            $body = $this->smarty->fetch($this->templateDir . $this->bodyTemp);
            $this->smarty->assign('body', $body);
        }
        $this->smarty->display($this->templateDir . "main.tpl");
    }

}