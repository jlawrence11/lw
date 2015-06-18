<?php
/**
 * Set up the user's table and admin user
 *
 * This file will create a new table for users, using the variables
 * following this comment block will also create a user for admin purpose
 * DELETE this off the server RIGHT after you run it.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage install
 */
/*
 * DO NOT KEEP THIS ON YOUR SERVER
 */
use jlawrence\lw;
//autoload!
require_once "../autoload.php";
//initialize the site with required modules
$site = new lw\FactoryScript("../cnf/site.ini");
$time = time();
$user = array(
    'uname' => "AdminUserName",
    'ufname' => "John",
    'ulname' => "Smith",
    'email' => "john@smith.com",
    'ulevel' => 100,
    'admin' => 1,
    'regtime' => $time,
    'lastlog' => $time,
    'regactive' => 1,
    'pass' => $site->crypt->passHash('myPassWord', $time)
);


$table = array(
    'id' => ' int unsigned not null auto_increment primary key',
    'uname' => 'varchar(50) not null unique',
    'email' => 'varchar(100) not null',
    'ufname' => 'varchar(25)',
    'ulname' => 'varchar(25)',
    'pass' => 'varchar(130) not null',
    'ulevel' => "int not null default '0'",
    'admin' => "tinyint not null default '0'",
    'regtime' => 'bigint unsigned not null',
    'regactive' => "tinyint unsigned default '0'",
    'birthday' => "bigint unsigned default null",
    'cookieHash' => "varchar(130) default null",
    'lastlog' => "bigint unsigned default null"
);

$hTable = array(
    'uid' => 'int unsigned not null primary key',
    'email' => 'varchar(100) not null',
    'uname' => 'varchar(50) not null',
    'hash' => 'varchar(35) not null'
);

$site->pdo->createTable('users', $table);
$site->pdo->createTable('user_activate', $hTable);
$site->pdo->insert('users', $user);

$site->debug->error("Showing debug:");
