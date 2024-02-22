<?php

// Get DOI agency for a set of DOI prefixes

error_reporting(E_ALL);

$pdo = new PDO('sqlite:../ipni.db');


//----------------------------------------------------------------------------------------
function do_query($sql)
{
	global $pdo;
	
	$stmt = $pdo->query($sql);

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	
	
	}
	
	return $data;	
}

//----------------------------------------------------------------------------------------
function get($url, $format_type = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);	
	
	//curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$headers = array();
	
	if ($format_type != '')
	{
		$headers[] = "Accept: " . $format_type;
		
		if ($format_type == 'text/html')
		{
			// play nice
			$headers[] = "Accept-Language: en-gb";
			$headers[] = "User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405";
			
			// Cookies 
			curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');
			curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');	
		}
	}
	
	//print_r($headers);
	
	if (count($headers) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
	
	$header = substr($response, 0, $info['header_size']);
	//echo $header;
	
	$content = substr($response, $info['header_size']);
	
	curl_close($ch);
	
	
	
	return $content;
}

//----------------------------------------------------------------------------------------
function doi_to_agency($prefix, $doi)
{
	global $prefix_to_agency;
	
	$agency = '';
			
	if (isset($prefix_to_agency[$prefix]))
	{
		$agency = $prefix_to_agency[$prefix];
	}
	else
	{
		$url = 'https://doi.org/ra/' . $doi;
	
		$json = get($url);
	
		//echo $json;
	
		$obj = json_decode($json);
	
		if ($obj)
		{
			if (isset($obj[0]->RA))
			{
				$agency = $obj[0]->RA;
		
				$prefix_to_agency[$prefix] = $agency;
			}
			else
			{
				// Bad DOI
				if (isset($obj[0]->status))
				{
					$agency = $obj[0]->status;
				}
			}
	
		}
	}
	
	return $agency;
}


//----------------------------------------------------------------------------------------

echo "-- Getting prefixes...\n";


$prefix_filename = 'prefix.json';
$json = file_get_contents($prefix_filename);
$prefix_to_agency = json_decode($json, true);


$sql = 'SELECT DISTINCT doi FROM names WHERE doi IS NOT NULL AND doiagency IS NULL';

$query_result = do_query($sql);

$rows = array();

$count = 1;

foreach ($query_result as $data)
{
	echo "-- " . $data->doi . "\n";
	
	$parts = explode('/', $data->doi);
	$prefix = $parts[0];
		
	$agency = doi_to_agency($prefix, $data->doi);
	
	// echo "agency=$agency\n";
	
	if ($agency != '')
	{
		echo 'UPDATE names SET doiagency="' . $agency . '" WHERE doi LIKE "' . $prefix . '%" AND doiagency IS NULL;' . "\n";
	}
	

	if (($count++ % 10) == 0)
	{
		$rand = rand(1000000, 3000000);
		echo '-- sleeping for ' . round(($rand / 1000000),2) . ' seconds' . "\n";
		usleep($rand);
	}
	
}	

//print_r($prefix_to_agency);

file_put_contents($prefix_filename, json_encode($prefix_to_agency));


?>