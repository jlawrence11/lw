<?php
/**
 * Created by: Jon Lawrence on 2015-06-18 7:34 AM
 */
use jlawrence\lw;
require "../autoload.php";

$base = new lw\FactoryScript("../cnf/site.ini");

$keys = $base->crypt->makeKeyPair();

$public = trim($keys['publicKey']);
$private = trim($keys['privateKey']);

echo "Copy the following in to your ini file for the cookies module to work properly<br>\n";

echo "<pre>[Cookies]\npublicKey = \"{$public}\"\n\nprivateKey = \"{$private}\"\n\n</pre>";