<?php

// Match citatuons to old MySQL database

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

//$sql = 'SELECT * FROM names WHERE id=17814';

$sql = 'SELECT * FROM names WHERE id="907425-1"';

$sql = 'SELECT * FROM names WHERE issn="0256-257X" and wikidata is null';

$sql = 'SELECT * FROM names WHERE issn="0075-5974" and doi is null';

$sql = 'SELECT * FROM names WHERE issn="0368-8895"';
$sql = 'SELECT * FROM names WHERE issn="0258-1485" and jstor IS NULL and doi is null';

//$sql = 'SELECT * FROM names WHERE id="873015-1"';


$debug = true;
$debug = false;

$include_authors = true; // more accuracy
//$include_authors = false;

$include_issue = true; // more accuracy
$include_issue = false;


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
		$collation = $data->collation;
		
		// clean
		$collation = preg_replace('/,\s+nom. nov..*$/', '', $collation);
		$collation = preg_replace('/,?\s+without.*$/', '', $collation);
		$collation = preg_replace('/,?\s+basio.*$/', '', $collation);
		$collation = preg_replace('/\s+nym.*$/', '', $collation);
		$collation = preg_replace('/;\s+et.*$/', '', $collation);
		$collation = preg_replace('/,\s+as.*$/', '', $collation);
		$collation = preg_replace('/[,|\.]\s+in\s*.*$/', '', $collation);
		
		
		$terms[] = $collation;
	}
	
	$string = join(' ', $terms);
		
	$url = 'http://localhost/citation-matching/api/parser.php?q=' . urlencode($string);

	$json = get($url);

	//echo $json;
	if ($debug)
	{
		echo $json;
	}
	
	$doc = json_decode($json);
	
	if ($doc && $doc->score > 80)
	{
		$doc->id = $data->id;
			
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
					$literal = trim($m[1]);
				}	
				
				$literal = preg_replace('/\.$/', '', $literal);
		
				echo "--literal=$literal\n";
		
				$literal = trim($literal);
		
				$author = new stdclass;
				$author->literal = $literal;
				$doc->author = array($author);
				
				//exit();
			
			}
		}
	

	
		// print_r($doc);
	
		$parameters = array();
	
		$keys = array('id', 'container-title', 'ISSN', 'collection-title', 'volume', 'issue', 'page', 'author');
		foreach ($keys as $k)
		{
			if (isset($doc->{$k}))
			{
				switch ($k)
				{
					case 'container-title':
						break;
			
					case 'ISSN':
						
						// hacks
						if ($doc->ISSN[0] == '0529-1526')
						{
							$parameters['issn'] = '1674-4918';
						}						
						else
						{
							$parameters['issn'] = $doc->ISSN[0];
						}
						break;

					case 'collection-title':
						$parameters['series'] = $doc->{$k};
						break;

					case 'volume':
						$parameters['volume'] = $doc->{$k};
						break;
						
					case 'issue':
						if ($include_issue)
						{
							$parameters['issue'] = $doc->{$k};
						}
						break;						

					case 'page':
						$parameters['page'] = $doc->{$k};
						break;
					
					case 'author':
						if (isset($doc->{$k}[0]->literal))
						{
							$parameters['authors'] = $doc->{$k}[0]->literal;
						}
						break;				
						
					default:
						break;
				}
			}
		}
	
		//print_r($parameters);
		
		echo "-- " . join(" ", $parameters) ."\n";

		$url = 'http://localhost/old/microcitation/www/index.php?' . http_build_query($parameters);
	
		$json = get($url);
	
		//echo $json . "\n";
		
		$obj = json_decode($json);
	

		//print_r($obj);

		if (isset($obj->found) && $obj->found)
		{
			if (count($obj->results) == 1)
			{	
				if (isset($obj->results[0]->doi))
				{
					$sql = 'UPDATE names SET doi="' . $obj->results[0]->doi . '" WHERE id="' . $doc->id . '";';
			
					echo $sql . "\n";			
				}
				
				if (isset($obj->results[0]->handle))
				{
					$sql = 'UPDATE names SET handle="' . $obj->results[0]->handle . '" WHERE id="' . $doc->id . '";';
			
					echo $sql . "\n";			
				}
				
			
				if (isset($obj->results[0]->jstor))
				{
					$sql = 'UPDATE names SET jstor=' . $obj->results[0]->jstor . ' WHERE id="' . $doc->id . '";';
			
					echo $sql . "\n";			
				}
				
				if (isset($obj->results[0]->wikidata))
				{
					$sql = 'UPDATE names SET wikidata="' . $obj->results[0]->wikidata . '" WHERE id="' . $doc->id . '";';
			
					echo $sql . "\n";			
				}
				
				if (isset($obj->results[0]->url))
				{
					$sql = 'UPDATE names SET url="' . $obj->results[0]->url . '" WHERE id="' . $doc->id . '";';
			
					echo $sql . "\n";			
				}

				if (isset($obj->results[0]->pdf))
				{
					$sql = 'UPDATE names SET pdf="' . $obj->results[0]->pdf . '" WHERE id="' . $doc->id . '";';
			
					//echo $sql . "\n";			
				}
				

			}
		}
	}
}

?>
