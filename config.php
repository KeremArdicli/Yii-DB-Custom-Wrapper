<?php

/**
 * BEFORE START ANYTHING SET YOUR DBNAME HERE OR SOME ENV VARIABLE
 * REST WILL BE DONE AUTOMATICALLY. tableNames.php FILE WILL BE AUTOMATICALLY
 * GENERATED WITH THE NAMES OF THE TABLES IN THE DATABASE
 * 
 */
define("DBNAME", "your_db_name_here");

require("vendor/autoload.php");

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;

// Dsn.
$dsn = (new Dsn('mysql', '127.0.0.1', DBNAME, '3306', ['charset' => 'utf8mb4']))->asString();

// PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Schema cache.
$schemaCache = new SchemaCache($arrayCache);

// PDO driver.
$pdoDriver = new Driver($dsn, 'root', '');
// $pdoDriver = new Driver($dsn, 'plazabutce', 'Ar4$88g3l');

// Connection.
$db = new Connection($pdoDriver, $schemaCache);