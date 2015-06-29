<?php
/**
 * Created by: Jon Lawrence on 2015-03-20 12:42 AM
 */

namespace jlawrence\lw;


class Factory implements CoreInterface\FactoryBase
{
    public $baseDir;
    public $url;
    public $title;
    protected $configArray;

    /**
     * variables to hold core modules
     */
    public $event;
    public $session;
    public $debug;
    public $crypt;
    public $pdo;
    public $cookie;
    public $template;
    public $email;
    public $users;

    /**
     * @param String $iniFile Ini file to load (full path)
     */
    public function __construct($iniFile)
    {
        $bt = debug_backtrace();
        $this->baseDir = dirname($bt[0]['file']). DIRECTORY_SEPARATOR;
        //echo $this->baseDir;
        $this->event = new Events($this);
        $this->session = new Sessions($this);

        $this->debug = new Debug($this);
        $this->debug->notice("Loaded Debug Module");

        $this->loadIni($iniFile);
        $this->debug->notice("Loaded INI File");

        $this->url = $this->configArray['Site']['url'];
        $this->title = $this->configArray['Site']['title'];

        $this->crypt = new Crypt($this->configArray['Crypt']);
        $this->debug->notice("Loaded Crypt module");

        $this->cookie = new Cookies($this, $this->configArray['Cookies']);
        $this->debug->notice("Loaded Cookies module");

        $this->pdo = new Pdo($this, $this->configArray['Pdo']);
        $this->debug->notice("Loaded Pdo module");

        $this->template = new Template($this, $this->configArray['Template']);
        $this->debug->notice("Loaded Template module");

        $this->email = new Email($this, $this->configArray['Email']);
        $this->debug->notice("Loaded Email module");

        $this->users = new Users($this);
        $this->debug->notice("Loaded User module");
    }

    protected function loadIni($iniFile)
    {
        $cfg = parse_ini_file($iniFile, true);
        $this->configArray = $cfg;
    }
} 