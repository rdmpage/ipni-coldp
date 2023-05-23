<?php

// Match IPNI record to BioStor using local copy of BioStor

require_once(dirname(__FILE__) . '/sqlite.php');

$pdo 		= new PDO('sqlite:../ipni.db');    // name of SQLite database (a file on disk)

//----------------------------------------------------------------------------------------
function get($url)
{
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	
	return $data;
			
}	

//----------------------------------------------------------------------------------------
$sql = 'SELECT * FROM names WHERE biostor IS NULL AND issn="1626-3596" LIMIT 10';
$sql = 'SELECT * FROM names WHERE biostor IS NULL AND issn="1626-3596"'; // Richardiana

//$sql = 'SELECT * FROM names WHERE id ="77118301-1"';

//$sql = 'SELECT * FROM names WHERE id = "323667-2"';


$data = do_query($pdo, $sql);

$include_year = true;
$include_year = false;


$debug = true;
//$debug = false;

foreach ($data as $obj)
{
	//print_r($obj);
	
	$terms = array();
	
	if (isset($obj->publication))
	{
		$terms[] = $obj->publication;
	}

	if (isset($obj->collation))
	{
		$terms[] = $obj->collation;
	}

	/*
	if (isset($obj->publicationyearfull))
	{
		$terms[] = $obj->publicationyearfull;
	}
	*/
	
	$string = join(' ', $terms);
		
	$url = 'http://localhost/citation-matching/api/parser.php?q=' . urlencode($string);

	$json = get($url);

	if ($debug)
	{
		 echo $json;
		 echo "\n";
	}
	
	$doc = json_decode($json);
	
	if ($doc && $doc->score > 80)
	{
		$year = $obj->publicationyearfull;
		$year = substr($year, 0, 4);
	
		$parameters = array(
			'journal' => $doc->{'container-title'},
			'volume' => $doc->volume,
			'page' => $doc->page
		);
		
		if ($include_year)
		{
			$parameters['year'] = $year;
		}
		
		$url = 'http://localhost/biostor-classic/www/micro.php?' . http_build_query($parameters);
		
		if ($debug)
		{
			echo $url . "\n";
		}
		
		$json = get($url);
		
		$response = json_decode($json);
		
		if ($debug)
		{
			print_r($response);
		}
		
		if ($response->count == 1)
		{
			$biostor = 0;
			$bhl = 0;
			
			foreach ($response->results[0]->identifier as $identifier)
			{
				if ($identifier->type == 'biostor')
				{
					$biostor = $identifier->id;
				}
			}
			
			$page_key = 'Page ' . $doc->page;
			if (isset($response->results[0]->bhl_pages->{$page_key}))
			{
				$bhl = $response->results[0]->bhl_pages->{$page_key};
			}
			
			if ($biostor != 0)
			{			
				echo 'UPDATE names SET biostor="' . $biostor . '" WHERE id="' . $obj->id . '";' . "\n";
			}
			if ($bhl != 0)
			{			
				echo 'UPDATE names SET bhl="' . $bhl . '" WHERE id="' . $obj->id . '";' . "\n";
			}
		
		}
	
	}
	
}

?>

