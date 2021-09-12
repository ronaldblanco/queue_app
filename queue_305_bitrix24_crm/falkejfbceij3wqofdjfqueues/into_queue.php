<?php

//Get ENV
	$env = file_get_contents('../../../.env', true);
	$env = explode("\n",$env);
	$getEnv = [];
	foreach($env as $data){
		$data = explode("=",$data);
		$getEnv[$data[0]] = $data[1];
	}
	$env = $getEnv;
	unset($getEnv);

if(isset($_GET['KEY']) && $_GET['KEY'] === $env['key']){

	date_default_timezone_set('America/New_York');

	class MyDB extends SQLite3
	{
    	function __construct()
    	{
        	$this->open(__DIR__.'/db/queue');
    	}
	}
	
	$name = isset($_GET['NAME']) ? $_GET['NAME'] : '';
	$last_name = isset($_GET['LAST_NAME']) ? $_GET['LAST_NAME'] : '';
	$language = isset($_GET['LANGUAGE']) ? $_GET['LANGUAGE'] : '';
	$email = isset($_GET['EMAIL']) ? $_GET['EMAIL'] : '';
	$phone = isset($_GET['PHONE']) ? $_GET['PHONE'] : '';
	//$web = isset($_GET['WEB']) ? $_GET['WEB'] : '';
	$comment = isset($_GET['COMMENT']) ? $_GET['COMMENT'] : '';
	//$address = isset($_GET['ADDRESS']) ? $_GET['ADDRESS'] : '';
	//$address_2 = isset($_GET['ADDRESS_2']) ? $_GET['ADDRESS_2'] : '';
	//$city = isset($_GET['CITY']) ? $_GET['CITY'] : '';
	//$postal = isset($_GET['POSTAL']) ? $_GET['POSTAL'] : '';
	//$state = isset($_GET['STATE']) ? $_GET['STATE'] : '';
	//$country = isset($_GET['COUNTRY']) ? $_GET['COUNTRY'] : '';
	$source = isset($_GET['SOURCE']) ? $_GET['SOURCE'] : 'OTHER';
	$source_desc = isset($_GET['SOURCE_D']) ? $_GET['SOURCE_D'] : '';
	
	$comment = trim(preg_replace('/\s+/', ' ', $comment));
	if(strpos($email, "{{") !== false || strpos($email, "}}") !== false) $email = "";
	if(strpos($phone, "{{") !== false || strpos($phone, "}}") !== false) $phone = "";
	$name = str_replace("'","",$name);
	$last_name = str_replace("'","",$last_name);
			
	$url_to_save = "?NAME=".$name."&LAST_NAME=".$last_name."&LANGUAGE=".$language."&EMAIL=".$email."&PHONE=".$phone."&SOURCE=".$source."&SOURCE_D=".$source_desc."&KEY=".$_GET['KEY']."&COMMENT=".str_replace("'","",str_replace("#","",str_replace("{","",str_replace("}","",$comment))));

	$db = new MyDB();

	$execution = $db->exec("INSERT INTO apicalls VALUES('".$url_to_save."',0,'','".date("Y-m-d h:m:s")."')");

	$result = "";
	if($execution){
		//echo "URL added to the queue succesfully!";
		$result = "URL added to the queue succesfully!";
		
	} else {
		//echo "An error happen with the url!";
		$sended_url = str_replace(" ", "%20",str_replace("?", "",$url_to_save));
		$bitrix24_crm = $env['bitrixaddcontact']."?".$sended_url;
		$get_bitrix24_result = file_get_contents($bitrix24_crm);
		if($get_bitrix24_result){
			$result = "An error happen with the url in queue. Trying Bitrix24 directly then and responded:".$get_bitrix24_result."!";
		} else {
			$result = "An error happen with the url for the queue and Bitrix24 directly!";
			sleep(60); //to report failure to the sender!
		}
				
	}

	//log information:
	$content = date("F j, Y, g:i a")."||".$url_to_save."||".$result."||".$_SERVER['REMOTE_ADDR']."\n";
	file_put_contents("log_add_to_queue.txt", print_r($content, true), FILE_APPEND);
	
	echo $result;
	
} else echo "Access Denied!";

?>