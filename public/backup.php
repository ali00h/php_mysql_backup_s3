<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require __DIR__ . '/MySqlBackupS3.php';
$backupInstance = new MySqlBackupS3();


exit();


ini_set('date.timezone', 'Asia/Tehran');

define('awsAccessKey', 'b571ad2c-9988-4f15-aedf-c8e48dad088f'); // required
define('awsSecretKey', '7576f2a2668b583eb1d33e053f5fe6926f289e3af568708928b099b4fe36ceac'); // required
define('awsBucket', 'ali00hbackup'); // required
define('awsEndpoint', 'https://s3.ir-thr-at1.arvanstorage.ir/');
define('awsRegion', 's3.ir-thr-at1.arvanstorage.ir');

// Will this script run "weekly", "daily", or "hourly"?
define('schedule','daily'); // required


backupDBs($_SERVER["MYSQL_HOST"],$_SERVER["MYSQL_USERNAME"],$_SERVER["MYSQL_PASSWORD"],'my-project101-backup','');



// don't kill the long running script
set_time_limit(0);

if (!defined('date.timezone')) {
    ini_set('date.timezone', 'America/Los_Angeles');
}

if (!defined('debug')) {
    define('debug', false);
}

if (!defined('awsEndpoint')) {
    define('awsEndpoint', 's3.amazonaws.com');
}

if (!defined('mysqlDumpOptions')) {
    define('mysqlDumpOptions', '--quote-names --quick --add-drop-table --add-locks --allow-keywords --disable-keys --extended-insert --single-transaction --create-options --comments --net_buffer_length=16384');
}



// Instantiate the S3 class and point it at the desired host
