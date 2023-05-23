<?php

// Match citatuons to BHL

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


$sql = 'SELECT * FROM names WHERE id="318499-1"';

$sql = 'SELECT * FROM names WHERE publication="Icon. Pl. Formosan."';

$sql = 'SELECT * FROM names WHERE publication="Mem. New York Bot. Gard."';

$sql .= ' AND Collation LIKE "10%"';


$sql .= ' AND publication IS NOT NULL';
$sql .= ' AND collation IS NOT NULL ';
$sql .= ' AND bhl is NULL';


$query_result = do_query($sql);

$rows = array();

foreach ($query_result as $data)
{
	// we will need to parse IPNI
	
	$terms = array();
	$terms[] = $data->publication;
	$terms[] = $data->collation;
	
	$q = join(", ", $terms);
	
	echo "-- $q\n";
	
	
	$url = 'http://localhost/citation-matching/api/parser.php?q=' . urlencode($q);
	
	$json = get($url);
	
	//echo $json . "\n";
	
	$doc = json_decode($json);
	
	if (!$doc)
	{
		continue;
	}
	
	
	if (isset($data->issn))
	{
		$doc->ISSN[] = $data->issn;
	}

	if (isset($data->bhltitle))
	{
		$doc->BHLTITLEID[] = $data->bhltitle;
	}	
	
	if (isset($data->publicationyearfull))
	{
		$year = substr($data->publicationyearfull, 0, 4);
	
		$doc->issued = new stdclass;
		$doc->issued->{'date-parts'} = array();
		$doc->issued->{'date-parts'}[0][0] = (Int)$year;
	}
	
	/*
	// add author
	if (isset($data->author))
	{
		$literal = preg_replace('/\s+\d+$/', '', $data->author);
		$author = new stdclass;
		$author->literal = $literal;
		$doc->author = array($author);
	}
	*/
	
	if (1)
	{
		// BHL API
		$url = 'http://localhost/citation-matching/api/bhl.php';
	}
	else
	{
		// local database
		$url = 'http://localhost/citation-matching/api/bhl_db.php';
	}
	
	$json = post($url, json_encode($doc));
	
	//echo $json;
		
	$doc = json_decode($json);
	$doc->name = $data->fullnamewithoutfamilyandauthors;
	
	//print_r($doc);
	
	//echo json_encode($doc) . ;
	
	if (isset($doc->BHLPAGEID))
	{
		$url = 'http://localhost/citation-matching/api/text_doc.php';
	
		$json = post($url, json_encode($doc));
		
		//echo $json;
		
		$doc = json_decode($json);
		
		//print_r($doc);
		
		$page_ids = array();
		
		if (isset($doc->hits))
		{
			if (count((array)$doc->hits) > 0)
			{
				foreach ($doc->hits as $pageid => $hit)
				{
					foreach ($hit->selector as $selector)
					{
					
						$page_ids[] = $pageid;
						
						//echo $selector->exact;
					
						$keys = array();
						$values = array();
					
						$keys[] = 'pageid';
						$values[] = $pageid;
					
						$keys[] = 'string_id';
						$values[] = '"' . $data->id . '"';

						$keys[] = 'string';
						$values[] = '"' . str_replace('"', '""', $data->fullnamewithoutfamilyandauthors) . '"';
					
						$annotation_id = $pageid;
					
						foreach ($selector as $k => $v)
						{
							switch ($k)
							{
								case 'score':
									$keys[] = $k;
									$values[] = $v;
									break;
							
								case 'range':
									$keys[] = 'start';
									$values[] = $v[0];
									$keys[] = 'end';
									$values[] = $v[1];
								
									$annotation_id .= '-' . join('-', $v);
									$keys[] = 'id';
									$values[] = '"' . $annotation_id . '"';
									break;
													
								case 'prefix':
								case 'exact':
								case 'suffix':
									$keys[] = $k;
									$v = str_replace("\n", '\n', $v);					
									$values[] = '"' . str_replace('"', '""', $v) . '"';
									break;
								
								default:
									break;
						
							}
					
						}
					
						echo 'REPLACE INTO annotation('
						. join(',', $keys) . ') VALUES ('
						. join(',', $values) . ');' . "\n";
					
					
					}
				}
			
				$page_ids = array_unique($page_ids);
				
				if (count($page_ids) == 1)
				{
					// we have an unambiguous match
					echo 'UPDATE names SET bhl=' . $pageid . ' WHERE id="' . $data->id . '";' . "\n";
				}
			}
		}
	
	}
	
	/*
	if ($doc && isset($doc->DOI) && count($doc->DOI) == 1)
	{
		echo 'UPDATE names SET doi = "' . $doc->DOI[0] . '" WHERE id="' . $data->id . '";' . "\n";
	}
	*/
}

?>
