<?php

date_default_timezone_set('America/New_York');

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

if(isset($_SERVER['REMOTE_ADDR']) == false){ //Running from command allowed

$apiResponse = "Nothing to be send!";
$message = "";
//var_dump(__DIR__);
class MyDB extends SQLite3
{
    function __construct()
    {
		$location = $env['location'];
        $this->open(__DIR__.'/db/queue');
		//$this->open($location.'/db/queue');
    }
}

$db = new MyDB();

$result = $db->query('SELECT * FROM apicalls WHERE done = 0 LIMIT 10;'); //Select not procceced URL request; Max: 10, Min: 1, Default: 4 or 6
$sended_url = "No url to send!";
$finalapiResponse = "";

$mydata = array();
while($row = $result->fetchArray()) {
	//Mark as ready before to proceed (in cases of delays this will avoid too many repetitions of the same request!)
	array_push($mydata,$row); //Get all rows informations in array
	$update = $db->exec("UPDATE apicalls SET done = 1 WHERE url = '".$row['url']."' and done = 0;"); //Update row, kill repetite API calls if any
}

foreach($mydata as $row) { //Start normal operations with the URL
	//Operations with URL##########################
	//Mark as ready before to proceed (in cases of delays this will avoid to many repetitions of the same request!)
	//$update = $db->exec("UPDATE apicalls SET done = 1 WHERE url = '".$row['url']."' and done = 0;"); //Update row, kill repetite API calls if any
	$sended_url = str_replace(" ", "%20",str_replace("?", "",$row['url']));
	$bitrix24_crm = $env['bitrixaddcontact']."?".$sended_url;
			
	$cURLConnection = curl_init();
	curl_setopt($cURLConnection, CURLOPT_URL, $bitrix24_crm);
	curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	$apiResponse = curl_exec($cURLConnection);
			
	//var_dump($bitrix24_crm);
	//var_dump($apiResponse);
	//echo "Error:".strpos($apiResponse, "Integration Support by SwayPC");
	
	//################################################
	if(strpos($apiResponse, "Integration Support by SwayPC") !== false || $apiResponse === false){ //Error in the destination
		
		if($apiResponse === false) $apiResponse = "Destination server did not responded correctly! curl error:".curl_error($cURLConnection);		
		else $apiResponse = "Destination server did not responded correctly! apparently page does not exist!";
		
		//curl_close($cURLConnection);
		
		$message = date("F j, Y, g:i a")."||";
		$servername = $env['servername'];
		$username = $env['username'];
		$password = $env['password'];
		$dbname = $env['dbname'];
		$day = date("Y-m-d");
		
		$ready_data = array();
		$get_data = explode("&", str_replace("?", "", $sended_url));
		//var_dump($get_data);
		foreach($get_data as $data){
			$tmp = explode("=", $data);
			$ready_data[$tmp[0]] = $tmp[1];
		}
		//var_dump($ready_data);
		
		// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
  			//die("Connection failed: " . $conn->connect_error);
			$alert = 1;
			$message = $message."Connection failed: " . $conn->connect_error;
		} else {
			$message = $message."Connected successfully to Database!";
		}
	
		$sql = "INSERT INTO queue (date, sended_url, apiresponse, comment, name, last_name, email, phone, language, source, source_d, comments)
VALUES ('".date("y-m-d h:m:s")."', '".$sended_url."', '".$apiResponse."','".$message."','".$ready_data['NAME']."','".$ready_data['LAST_NAME']."','".$ready_data['EMAIL']."','".$ready_data['PHONE']."','".$ready_data['LANGUAGE']."','".$ready_data['SOURCE']."','".$ready_data['SOURCE_D']."','".str_replace("%20", " ", $ready_data['COMMENT'])."')";
		//$result = $conn->query($sql);
		if ($conn->query($sql) === TRUE) {
  			$message = $message."||New record created successfully";
		} else {
  			$message = $message."||Error: " . $sql . "<br>" . $conn->error;
		}
	
	}
	//################################################
	
	curl_close($cURLConnection);
	
	//log information:
	if($message == "") $content = date("F j, Y, g:i a")."||".$sended_url."||".$apiResponse."||".$_SERVER['REMOTE_ADDR']."\n";
	else $content = date("F j, Y, g:i a")."||".$sended_url."||".$apiResponse."||".$_SERVER['REMOTE_ADDR']."\n".$message."\n";
	file_put_contents($env['location']."/log_send_from_queue.txt", print_r($content, true), FILE_APPEND);
	
	//$finalapiResponse = $finalapiResponse."||".$apiResponse;
	echo $apiResponse."\n";
}

if($apiResponse == "Nothing to be send!") echo $apiResponse;
//var_dump(isset($_SERVER['REMOTE_ADDR']));
	
} else echo "Access Denied!";

?>