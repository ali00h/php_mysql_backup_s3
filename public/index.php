<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
$obj = new LogJob();


class LogJob{

    private $env = array();
    private $logDir = "log/";

    public function __construct() {
        $this->fillENV();
        $this->checkSecretKey();

        ini_set('date.timezone', $this->env["TIME_ZONE"]);
        set_time_limit(0);
        
        $this->showLastLog();

    }

    private function showLastLog(){
        $filePath = $this->logDir . "cron_last_log.inc";
        $myfile = fopen($filePath, "r") or die("Unable to open file!");
        $fileContent = fread($myfile,filesize($filePath));
        $fileContent = str_replace("<?php /*", "", $fileContent);
        $fileContent = str_replace("*/?>", "", $fileContent);        
        fclose($myfile);        
        echo nl2br($fileContent);
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

    private function printLog($msg){
        echo $msg . "<br />";
    }	    
}
?>