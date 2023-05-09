<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*

The following must be configured before running the script.

*/
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

use Aws\S3\S3Client;

// require the sdk from your composer vendor dir
require __DIR__.'/vendor/autoload.php';

// Instantiate the S3 class and point it at the desired host
$s3 = new S3Client([
    'region' => awsRegion,
    'version' => '2006-03-01',
    'endpoint' => awsEndpoint,
    'credentials' => [
        'key' => awsAccessKey,
        'secret' => awsSecretKey
    ],
    // Set the S3 class to use objects. arvanstorage.com/bucket
    // instead of bucket.objects. arvanstorage.com
    'use_path_style_endpoint' => true
]);

$listResponse = $s3->listBuckets();

$buckets = $listResponse['Buckets'];
foreach ($buckets as $bucket) {
    echo $bucket['Name'] . "\t" . $bucket['CreationDate'] . "\n";
}