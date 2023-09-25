<?php

// Fetch missing records using IPNI API

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
function process_xml($xml)
{	
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);

	$xpath->registerNamespace('dc',      'http://purl.org/dc/elements/1.1/');
	$xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
	$xpath->registerNamespace('tn', 'http://rs.tdwg.org/ontology/voc/TaxonName#');
	$xpath->registerNamespace('tcom', 'http://rs.tdwg.org/ontology/voc/Common#');
	$xpath->registerNamespace('rdfs',    'http://www.w3.org/2000/01/rdf-schema#');
	$xpath->registerNamespace('rdf',     'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

	$obj = new stdclass;

	// Identifier
	$nodeCollection = $xpath->query ('//tn:TaxonName/@rdf:about');
	foreach ($nodeCollection as $node)
	{
		$obj->id = str_replace('urn:lsid:ipni.org:names:', '', $node->firstChild->nodeValue);
	}

	// Name
	$nodeCollection = $xpath->query ('//tn:nameComplete');
	foreach ($nodeCollection as $node)
	{
		$obj->fullnamewithoutfamilyandauthors = $node->firstChild->nodeValue;
	}	
	
	foreach ($xpath->query ('//tn:genusPart') as $node)
	{
		$obj->genus = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:specificEpithet') as $node)
	{
		$obj->species = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:rankString') as $node)
	{
		$obj->rank = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:authorship') as $node)
	{
		$obj->authors = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:basionymAuthorship') as $node)
	{
		$obj->basionymauthor = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tcom:publishedIn') as $node)
	{
		$obj->publication = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tcom:microReference') as $node)
	{
		$obj->collation = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:year') as $node)
	{
		$obj->publicationyearfull = $node->firstChild->nodeValue;
	}

	foreach ($xpath->query ('//tn:versionInfo') as $node)
	{
		$obj->version = $node->firstChild->nodeValue;
	}


	return $obj;
}


//----------------------------------------------------------------------------------------
function data_to_sql($obj)
{
	$sql = '';
	

	// export			
	$keys = array();
	$values = array();

	foreach ($obj as $k => $v)
	{
		$keys[] = $k;

		if (is_array($v))
		{
			$values[] = '"' . str_replace('"', '""', join(",", $v)) . '"';
		}
		elseif(is_object($v))
		{
			$values[] = '"' . str_replace('"', '""', json_encode($v)) . '"';
		}
		else
		{				
			$values[] = '"' . str_replace('"', '""', $v) . '"';
		}
	}

	$sql = 'REPLACE INTO names(' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';
	
	return $sql;

}

$ids = array(
//'434654-1',
'77157024-1',
'77157025-1',
'77157026-1',
);

$count = 1;

foreach ($ids as $id)
{
	$url = 'http://lsid.io/urn:lsid:ipni.org:names:' . $id . '&format=xml';
	
	$xml = get($url);
	
	$obj = process_xml($xml);
	
	//print_r($obj);	
	
	$sql = data_to_sql($obj);
	
	echo $sql . "\n";
	
	if (($count++ % 5) == 0)
	{
		$rand = rand(1000000, 3000000);
		echo '...sleeping for ' . round(($rand / 1000000),2) . ' seconds' . "\n";
		usleep($rand);
	}

}

?>

