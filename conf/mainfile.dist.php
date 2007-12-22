<?php


    // Database
    // Choose the database to be used
    define('OVMS_DB_TYPE', 'mysql');

    // Database Hostname
    // Hostname of the database server. If you are unsure, 'localhost' works in most cases.
    define('OVMS_DB_HOST', 'localhost');

   //this port is used while connect
    define('OVMS_DB_PORT', '3306');

    // Database Username
    // Your database user account on the host
    define('OVMS_DB_USER', 'ovms_live');

    // Database Password
    // Password for your database user account
    define('OVMS_DB_PASS', 'a');

    // Database Name
    // The name of database on the host. The installer will attempt to create the database if not exist
    define('OVMS_DB_NAME', 'ufo');

    //this pass_c was used to connect database by new user
    define('OVMS_DB_PASS_C', 'a');

    //this name_c was used to connect database by new user
    define('OVMS_DB_NAME_C', 'ovms_live');

    if(!defined('OVMS_ROOT_PATH')){
        define('OVMS_ROOT_PATH','/var/www/html');
    }


?>
