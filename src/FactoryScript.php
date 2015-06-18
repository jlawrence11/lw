<?php
/**
 * Created by: Jon Lawrence on 2015-06-18 7:21 AM
 */

namespace jlawrence\lw;


class FactoryScript extends Factory
{
    //Includes all core modules except users, templates, sessions, and cookies
    public function __construct($iniFile)
    {
        $this->debug = new Debug($this);
        $this->debug->notice("Loaded Debug Module");

        $cfg = parse_ini_file($iniFile, true);
        $this->configArray = $cfg;
        $this->debug->notice("Loaded INI File");

        $this->url = $this->configArray['Site']['url'];
        $this->title = $this->configArray['Site']['title'];

        $this->crypt = new Crypt($this->configArray['Crypt']);
        $this->debug->notice("Loaded Crypt module");

        $this->pdo = new Pdo($this, $this->configArray['Pdo']);
        $this->debug->notice("Loaded Pdo module");
    }

} 