<?php

//----------------------------------------------------------------------------------------
function get($url, $format = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
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

$basedir = 'rdf';

// 77334341-1


$count = 1;

for ($id = 77255000; $id < 77336128; $id++)
{
	echo $id;
	
	$rdf_id = $id . '-1';

	$dir = floor($id / 1000);

	$dir = $basedir . "/" . $dir;
	if (!file_exists($dir))
	{
		$oldumask = umask(0); 
		mkdir($dir, 0777);
		umask($oldumask);
	}
	
	// Previous version of this file used just inetger paer of id for name, 
	// which is OK for recent names which are Kew-only
	$filename = $dir . '/' . $rdf_id . '.xml';
	
	if (file_exists($filename))
	{
		echo " - have it already\n";
	}
	else
	{
		echo " - fetching\n";
		
		$url = 'http://lsid.io/urn:lsid:ipni.org:names:' . $rdf_id . '&format=xml';
	
		$xml = get($url);
		
		file_put_contents($filename, $xml);
		
		// Give server a break every 10 items
		if (($count++ % 10) == 0)
		{
			$rand = rand(1000000, 3000000);
			echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
			usleep($rand);
		}		
		
	}
}

?>
