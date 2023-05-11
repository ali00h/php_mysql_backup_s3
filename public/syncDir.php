<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require __DIR__ . '/MySqlBackupS3.php';
$backupInstance = new MySqlBackupS3();

/*
You can sync your directory to s3 directory.
for example in next line. temp directory in local machine would be synced to manualBackups in S3 Storage and only txt files would be synced.
and maximum count of files in storage was 2, the oldest file would be removed automatically.
*/
$backupInstance->startSyncDirectory('temp/','manualBackups/','txt',2);
