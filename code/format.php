<?php

// Format citations

require_once(dirname(__FILE__) . '/sqlite.php');

$pdo 		= new PDO('sqlite:../ipni.db');    // name of SQLite database (a file on disk)

//----------------------------------------------------------------------------------------
function post($url, $data = '', $content_type = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	if ($content_type != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				"Content-type: " . $content_type
				)
			);
	}	
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
$sql = 'SELECT DISTINCT doi FROM `names` WHERE doi IS NOT NULL AND citation IS NULL AND issn="0960-4286"'; 
$sql = 'SELECT DISTINCT doi FROM `names` WHERE doi IS NOT NULL AND citation IS NULL AND publicationyearfull="2021"'; 

$sql = 'SELECT DISTINCT doi FROM `names` WHERE doi="10.1017/S0960428600220428"';

$sql = 'SELECT doi FROM `references` where citation like "%  %";';

$data = do_query($pdo, $sql);

foreach ($data as $obj)
{
	// print_r($obj);
		
	$doc = new stdclass;
	$doc->DOI = $obj->doi;
		
	$url = 'http://localhost/citation-matching/api/format.php';
	
	$json = post($url, json_encode($doc));
	
	//echo $json . "\n";
	
	$doc = json_decode($json);
	
	if ($doc && isset($doc->citation))
	{
		echo 'UPDATE names SET citation = "' . str_replace('"', '""', trim($doc->citation)) . '" WHERE doi="' . $doc->DOI . '";' . "\n";
	}
	
	
}

?>

