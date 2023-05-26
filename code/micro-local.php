<?php

// Match citatuons to DOIs

error_reporting(E_ALL);

$pdo = new PDO('sqlite:../ipni.db');

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


$sql = 'SELECT * FROM names WHERE id="106680-1"';

$sql = 'SELECT * FROM names WHERE issn="2346-9641"';

$sql = 'SELECT * FROM names WHERE issn="2346-9641" and publishingauthor ="O\'Donell"';

$sql = 'SELECT * FROM names WHERE id="107954-1"';

$sql = 'SELECT * FROM names WHERE id="102409-3"';

$sql = 'SELECT * FROM names WHERE issn="0085-4417"';

// Wege & K.R.Thiele
//$sql = 'SELECT * FROM names WHERE id="77104089-1"';

$sql = 'SELECT * FROM names WHERE issn="0254-6299"';

$sql = 'SELECT * FROM names WHERE issn="0075-5974" and doi is null';
$sql = 'SELECT * FROM names WHERE issn="0211-1322" and publicationyearfull LIKE "2%"';

$sql = 'SELECT * FROM names WHERE issn="0524-0476" and wikidata is null';
//$sql = 'SELECT * FROM names WHERE issn="0210-9506" and doi is null';

//$sql = 'SELECT * FROM names WHERE id="77119449-1"';

//$sql .= ' AND doi is NULL';
//$sql .= ' AND wikidata is NULL';
//$sql .= ' AND jstor is NULL';

$debug = true;
$debug = false;

$include_authors = true; // more accuracy
$include_authors = false;

$query_result = do_query($sql);

$rows = array();

foreach ($query_result as $data)
{

	//print_r($data);

	$terms = array();
	
	if (isset($data->publication))
	{
		$terms[] = $data->publication;
	}

	if (isset($data->collation))
	{
		$terms[] = $data->collation;
	}
	
	$string = join(' ', $terms);
		
	$url = 'http://localhost/citation-matching/api/parser.php?q=' . urlencode($string);

	$json = get($url);

	if ($debug)
	{
		echo $json;
	}
	
	$doc = json_decode($json);
	
	if ($doc && $doc->score > 80)
	{
		// go hunting
		if (isset($data->publicationyearfull))
		{
			$doc->issued = new stdclass;
			$doc->issued->{'date-parts'} = array();	
			$doc->issued->{'date-parts'}[0][0] = (Integer)substr($data->publicationyearfull, 0, 4);
		}

		if (isset($data->issn) && !isset($doc->ISSN))
		{
			$doc->ISSN[0] = $data->issn;
			
			// Anales del Jardín Botánico de Madrid
			if ($data->issn == '0211-1322')
			{
				$doc->ISSN[0] = '1988-3196';
			}

			if ($data->issn == '0210-9506')
			{
				$doc->ISSN[0] = '2340-5074';
			}

			if ($data->issn == '0524-0476')
			{
				//$doc->ISSN[0] = '1853-8460';
			}
			
			
			
		}
		
		// add author
		if ($include_authors)
		{
			if (isset($data->publishingauthor))
			{
				$literal = $data->publishingauthor;
	
				$literal = preg_replace('/.*\)\s+/', '', $literal);
		
				//echo $literal . "\n";
		
				// multiple authors, split on "&"
				if (preg_match('/^([^\&]+)\&/', $literal, $m))
				{
					$literal = trim($m[1]);
				}
		
				//echo $literal . "\n";
		
				// split on ","
				if (preg_match('/^([^,]+),/', $literal, $m))
				{
					$literal = trim($m[1]);
				}	
				
				if (preg_match('/^([A-Z][a-z]*\.)+\s*(.*)/', $literal, $m))
				{
					$literal = trim($m[2]);
				}	
				
				$literal = preg_replace('/\.$/', '', $literal);
		
				//echo $literal . "\n";
		
				$literal = trim($literal);
		
				$author = new stdclass;
				$author->literal = $literal;
				$doc->author = array($author);
			
			}
		}
		
		
		$url = 'http://localhost/microcitation-lite/api/micro.php';
	
		$json = post($url, json_encode($doc));
	
		if ($debug)
		{
			// echo $url . "\n";
			// echo json_encode($doc) . "\n";
			echo $json . "\n";
		}
	
		$doc = json_decode($json);
	
		if ($doc && isset($doc->DOI) && count($doc->DOI) == 1)
		{
			echo 'UPDATE names SET doi = "' . $doc->DOI[0] . '" WHERE id="' . $data->id . '";' . "\n";
		}
	
		if ($doc && isset($doc->WIKIDATA) && count($doc->WIKIDATA) == 1)
		{
			echo 'UPDATE names SET wikidata = "' . $doc->WIKIDATA[0] . '" WHERE id="' . $data->id . '";' . "\n";
		}

		if ($doc && isset($doc->URL) && count($doc->URL) == 1)
		{
			// echo 'UPDATE names SET url = "' . $doc->URL[0] . '" WHERE id="' . $data->id . '";' . "\n";
			
			if (preg_match('/https?:\/\/www.jstor.org\/stable\/(?<id>\d+)/', $doc->URL[0], $m))
			{
				echo 'UPDATE names SET jstor = "' . $m['id']. '" WHERE id="' . $data->id . '";' . "\n";					
			}
		}
		
		
		
	}


	
}

?>
