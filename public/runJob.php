<?php
$obj = new RunJob;
$obj->run();

class RunJob{
    private $env = array();
	private $c_minute = 0;
	private $c_hour = 0;
	private $c_dayOfMonth = 0;
	private $c_month = 0;
	private $c_dayOfWeek = 0;

	public function run(){
		$this->fillENV();
        if(sizeof($this->env["CRON_URL_LIST"]) == 0){
            $this->p('Cron job is empty!');
            exit();
        }
		$this->c_minute         = (int)date('i');
		$this->c_hour           = (int)date('H');
		$this->c_dayOfMonth     = (int)date('d');
		$this->c_month          = (int)date('m');
		$this->c_dayOfWeek      = (int)date('w');
		
		$this->p($this->c_minute);
		$this->p($this->c_hour);
		$this->p($this->c_dayOfMonth);
		$this->p($this->c_month);
		$this->p($this->c_dayOfWeek);
		
        foreach($this->env["CRON_URL_LIST"] as $jobItem){
    		$this->run_job($jobItem);
        }

	}
	
	private function run_job($job){
		$job_arr = explode(" ", $job);
		if(sizeof($job_arr) > 5){
			$cond = array();
			if($job_arr[0] == "*" || (int)$job_arr[0] == $this->c_minute)
				$cond[0] = true;
			else
				$cond[0] = false;
				
			if($job_arr[1] == "*" || (int)$job_arr[1] == $this->c_hour)
				$cond[1] = true;
			else
				$cond[1] = false;
				
			if($job_arr[2] == "*" || (int)$job_arr[2] == $this->c_dayOfMonth)
				$cond[2] = true;
			else
				$cond[2] = false;

			if($job_arr[3] == "*" || (int)$job_arr[3] == $this->c_month)
				$cond[3] = true;
			else
				$cond[3] = false;
				
			if($job_arr[4] == "*" || (int)$job_arr[4] == $this->c_dayOfWeek)
				$cond[4] = true;
			else
				$cond[4] = false;
				
			if($cond[0] && $cond[1] && $cond[2] && $cond[3] && $cond[4]){
				
				if (str_starts_with($job_arr[5], 'http')) {
					$this->p('Run... ' . $job);
					$jobContent = file_get_contents($job_arr[5]);
					$this->p('Job URL Response:<pre>' . $jobContent . '</pre>');
				}				
			}
		
		}

	}

    private function fillENV(){
        $this->env = $_SERVER;
        if (file_exists('.env')) {
            $this->env = array_merge(parse_ini_file('.env'), $this->env);
        }
        $this->env["CRON_URL_LIST"] = explode(",",$this->env["CRON_URL_LIST"]);
    }

	private function p($msg){
		echo '<b>' . date('d-m-Y H:i:s') . '</b> ' . $msg . '<br />';
	}
}