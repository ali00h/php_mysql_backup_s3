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
        $this->checkSecretKey();

        ini_set('date.timezone', $this->env["TIME_ZONE"]);
        set_time_limit(0);

        $this->makeDir($this->tempDir);
    }

    public function startDBBackup(){
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

    private function checkSecretKey(){
        if(isset($this->env["BACKUP_URL_SecretKey"]) && !empty($this->env["BACKUP_URL_SecretKey"])){
            if(!isset($_GET['sk']) || $this->env["BACKUP_URL_SecretKey"] != $_GET['sk']) {
                $this->printLog("Permission Error!");
                exit();
            }
        }
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
            $fileList = $this->getAllS3Objects($this->env["AWS_BACKUP_DIRECTORY"] . 'dbs/' . $dbname . '/');
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

    private function getAllS3Objects($dirInS3){
        $objectList = array();
        try {
            $objectsListResponse = $this->s3->listObjects(array('Bucket' => $this->env["AWS_Bucket"], 'Prefix' => $dirInS3));
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

    private function makeDir($path)
    {
        return is_dir($path) || mkdir($path);
    }

    public function startSyncDirectory($sourceDir,$s3Dir,$extension,$max_file_count = 0){
        if (!$this->endsWith($sourceDir, '/'))
            $sourceDir = $sourceDir . '/';

        if (!$this->endsWith($s3Dir, '/'))
            $s3Dir = $s3Dir . '/';


        $glob_path = $sourceDir . '*.' . $extension;

        $files = glob($glob_path);
        if($max_file_count > 0) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $files = array_slice($files,0, $max_file_count);
        }

        $files_in_local = array();
        foreach($files as $file){
            $files_in_local[] = basename($file);
        }

        $this->initS3();

        $objects_in_s3 = $this->getAllS3Objects($s3Dir);
        $files_in_s3 = array();
        foreach($objects_in_s3 as $obj){
            if(pathinfo($obj, PATHINFO_EXTENSION) == $extension)
                $files_in_s3[] = basename($obj);
        }

        $only_in_local = array_diff($files_in_local,$files_in_s3);
        $only_in_s3 = array_diff($files_in_s3,$files_in_local);

        /*
        $this->printLog("local:");
        print_r($files_in_local);
        $this->printLog("s3:");
        print_r($files_in_s3);
        $this->printLog("only_in_local:");
        print_r($only_in_local);
        $this->printLog("only_in_s3:");
        print_r($only_in_s3);
        */


        //upload $only_in_local
        $this->printLog(sizeof($only_in_local) . ' items must be uploaded!');
        foreach ($only_in_local as $item_local) {
            $this->putInS3($sourceDir . $item_local,$s3Dir . $item_local);
            $this->printLog('<b>' . $sourceDir . $item_local . '</b> was uploaded to <b>' . $s3Dir . $item_local . '</b>');
        }

        //remove from s3 $only_in_s3
        $this->printLog(sizeof($only_in_s3) . ' items must be removed!');
        foreach ($only_in_s3 as $item_s3) {
            $this->removeObjectFromS3($s3Dir . $item_s3);
            $this->printLog('<b>' . $s3Dir . $item_s3 . '</b> removed from s3');
        }


    }

    public function endsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);
        if(!$length)
        {
            return true;
        }

        return mb_substr($haystack, -$length) === $needle;
    }
}