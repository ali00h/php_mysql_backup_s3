<?php
use Aws\S3\S3Client;
require __DIR__ . '/vendor/autoload.php';

class MySqlBackupS3{

    private $env = array();
    private $s3 = false;
    private $tempDir = "temp/";
    private $backupLogs = array();

    public function __construct() {
        $this->fillENV();

        ini_set('date.timezone', $this->env["TIME_ZONE"]);
        set_time_limit(0);

        $this->backupLogs = array();
        $this->initS3();
        $this->checkDBConnection();
        $this->makeDBsBackup();
        $this->putDBsBackupToS3();
        $this->removeOldBackups();

    }

    private function fillENV(){
        $this->env = $_SERVER;
        if (file_exists('.env')) {
            $this->env = array_merge(parse_ini_file('.env'), $this->env);
        }
        $this->env["MYSQL_DATABASES"] = explode(",",$this->env["MYSQL_DATABASES"]);
    }

    private function putDBsBackupToS3(){
        //print_r($this->backupLogs);
        foreach ($this->backupLogs as $backupItem){
            $this->putInS3($backupItem['backup_file_path'],$backupItem['key_for_s3']);
            $this->printLog("<b>" . $backupItem['filename'] . "</b> was successfully put in the storage!");
            unlink($backupItem['backup_file_path']);
        }
    }

    private function makeDBsBackup(){
        foreach ($this->env["MYSQL_DATABASES"] as $dbname){
            $this->makeDBBackup($dbname);
        }
    }

    private function makeDBBackup($dbname){
        $dbhost = $this->env["MYSQL_HOST"];
        $dbport = $this->env["MYSQL_PORT"];
        $dbuser = $this->env["MYSQL_USERNAME"];
        $dbpass = $this->env["MYSQL_PASSWORD"];

        $set_date = date("Y-m-d-H-i-s");
        $filename = $dbname . '-' . $set_date . '.sql.gz';
        $backup_file = $this->tempDir . $filename;
        $command = "mysqldump --user='$dbuser' --password='$dbpass' --host='$dbhost' --port='$dbport' $dbname | gzip > $backup_file";

        //$this->printLog($command);
        system($command);
        $backup_filesize = filesize($backup_file);
        if($backup_filesize > 100)
        {
            $this->printLog("Backup for <b>" . $dbname . "</b> was created!");
            $this->backupLogs[] = array("backup_file_path"=>$backup_file,"backup_file_size"=>$backup_filesize,"key_for_s3"=>$this->getS3KeyPath($dbname,$filename),"filename"=>$filename);
            return true;
        }else{
            $this->printLog("Error in creating backup for <b>" . $dbname . "</b>.");
            return false;
        }

    }

    private function checkDBConnection(){
        $dbhost = $this->env["MYSQL_HOST"] . ':' . $this->env["MYSQL_PORT"];
        $dbuser = $this->env["MYSQL_USERNAME"];
        $dbpass = $this->env["MYSQL_PASSWORD"];
        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
        try {
            $mysqli = new mysqli($dbhost, $dbuser, $dbpass);
            if($mysqli->connect_error ) {
                $this->printLog("DB Connect failed: " . $mysqli->connect_error);
                exit();
            }
            $this->printLog("DB Connected successfully!");
            $mysqli->close();
        } catch (mysqli_sql_exception $e) {
            $this->printLog("DB Connect failed: " . $e->getMessage());
            exit();
        }

    }

    private function printLog($msg){
        echo $msg . "<br />";
    }

    private function removeOldBackups(){
        $max_count = (int)$this->env["AWS_MAX_BACKUP_COUNT_FOR_EACH_DB"];
        foreach ($this->env["MYSQL_DATABASES"] as $dbname) {
            $fileList = $this->getAllS3Objects($dbname);
            $removeCandidate = array();
            if (sizeof($fileList) > $max_count) {
                for ($i = 0; $i < (sizeof($fileList) - $max_count); $i++) $removeCandidate[] = $fileList[$i];
            }
            //print_r($removeCandidate);
            $removeCount = 0;
            foreach ($removeCandidate as $removeItem) {
                $this->removeObjectFromS3($removeItem);
                $removeCount++;
            }
            $this->printLog("<b>" . $removeCount . "</b> items was removed from <b>" . $dbname . "</b>");
        }
    }

    private function initS3()
    {
        if (!$this->s3) {

            $this->s3 = new S3Client([
                'region' => $this->env["AWS_Region"],
                'version' => '2006-03-01',
                'endpoint' => $this->env["AWS_Endpoint"],
                'credentials' => [
                    'key' => $this->env["AWS_AccessKey"],
                    'secret' => $this->env["AWS_SecretKey"]
                ],
                'use_path_style_endpoint' => true
            ]);
        }
    }

    private function getAllS3Backets(){
        $bucketsList = array();
        try {
            $listResponse = $this->s3->listBuckets();


            $buckets = $listResponse['Buckets'];
            foreach ($buckets as $bucket) {
                $bucketsList[] = $bucket['Name'];
            }
        } catch (S3Exception $e) {
            $this->printLog($e->getMessage());
        }
        return $bucketsList;
    }

    private function getAllS3Objects($dbname){
        $objectList = array();
        try {
            $objectsListResponse = $this->s3->listObjects(array('Bucket' => $this->env["AWS_Bucket"], 'Prefix' => $this->env["AWS_BACKUP_DIRECTORY"] . 'dbs/' . $dbname . '/'));
            $objects = $objectsListResponse['Contents'] ?? [];
            foreach ($objects as $object) {
                $objectList[] = $object['Key'];
            }
        } catch (S3Exception $e) {
            $this->printLog($e->getMessage());
        }
        return $objectList;
    }

    private function putInS3($sourceFile,$key_in_storage){
        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->env["AWS_Bucket"],
                'Key' => $key_in_storage,
                'SourceFile' => $sourceFile,
            ]);
        } catch (S3Exception $e) {
            $this->printLog($e->getMessage());
        }
    }

    private function removeObjectFromS3($key){
        try {
            $object = $this->s3->deleteObject([
                'Bucket' => $this->env["AWS_Bucket"],
                'Key' => $key]);
        } catch (AwsException $e) {
            $this->printLog( $e->getMessage());
        }
    }

    private function getS3KeyPath($dbname,$filename){
        return $this->env["AWS_BACKUP_DIRECTORY"] . 'dbs/' . $dbname . '/' . $filename;
    }
}