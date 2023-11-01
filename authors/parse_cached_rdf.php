<?php

// Parse cached RDF

$caches['ipni_authors'] ='/Volumes/Samsung_T5/rdf-archive/ipni/authors';

// Path to local storage of LSID is the reverse of the domain name
$domain_path['ipni_authors'] = array('org', 'ipni', 'authors');

$database = 'ipni_authors';

// Fetch XML files
$basedir = $caches[$database];

$files1 = scandir($basedir);

//$files1 = array('20002');

foreach ($files1 as $directory)
{
	// modulo 1000 directories
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($basedir . '/' . $directory);
		
		//print_r($files2);

		// individual XML files
		
		$contents = '';
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.xml$/', $filename))
			{	
				$full_filename = $basedir . '/' . $directory . '/' . $filename;
				
				$xml = file_get_contents($full_filename);
				
				// fix
				$xml = preg_replace('/"http:\/\/rs.tdwg.org\/ontology\/voc\/Person"/', '"http://rs.tdwg.org/ontology/voc/Person#"', $xml);
				
				//echo $xml "\n";
				
				$obj = new stdclass;
				
				// do stuff here
				$dom= new DOMDocument;
				$dom->loadXML($xml, LIBXML_NOCDATA); // So we get text wrapped in <![CDATA[ ... ]]>
				$xpath = new DOMXPath($dom);
				
				
				$xpath->registerNamespace('p', 'http://rs.tdwg.org/ontology/voc/Person#');
				$xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
				$xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
				$xpath->registerNamespace('owl', 'http://www.w3.org/2002/07/owl#');
				$xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
	
				$xpath_query = '//eSearchResult/Count';
				$nodeCollection = $xpath->query ($xpath_query);
				foreach($xpath->query('//p:Person') as $p)
				{				
					foreach($xpath->query('@rdf:about', $p) as $node)
					{
						$obj->id = str_replace('urn:lsid:ipni.org:authors:', '', $node->firstChild->nodeValue);
					}
				
					foreach($xpath->query('owl:versionInfo', $p) as $node)
					{
						$obj->version = $node->firstChild->nodeValue;
					}

					foreach($xpath->query('dcterms:created', $p) as $node)
					{
						$obj->created = $node->firstChild->nodeValue;
					}

					foreach($xpath->query('dcterms:modified', $p) as $node)
					{
						$obj->modified = $node->firstChild->nodeValue;
					}
					
					// name
					foreach($xpath->query('dc:title', $p) as $node)
					{
						$obj->name = $node->firstChild->nodeValue;
					}
					
					// alias
					$obj->alias = array();
					foreach($xpath->query('p:alias/p:PersonNameAlias', $p) as $node)
					{
						$alias = new stdclass;
						
						foreach($xpath->query('p:isPreferred', $node) as $a)
						{
							if (isset($a->firstChild))
							{
								$alias->preferred = ($a->firstChild->nodeValue == "true" ? true : false );
							}
						}

						foreach($xpath->query('p:standardForm', $node) as $a)
						{
							if (isset($a->firstChild))
							{
								$alias->standardForm = $a->firstChild->nodeValue;							
								$obj->standardForm = $a->firstChild->nodeValue;
							}
						}
						
						foreach($xpath->query('p:forenames', $node) as $a)
						{
							if (isset($a->firstChild))
							{
								$alias->forenames = $a->firstChild->nodeValue;
							}
						}

						foreach($xpath->query('p:surname', $node) as $a)
						{								
							if (isset($a->firstChild))
							{
								$alias->surname = $a->firstChild->nodeValue;
							}
						}						
					
						$obj->alias[] = $alias;
					}
					
					
					foreach($xpath->query('p:lifeSpan', $p) as $node)
					{
						if (isset($node->firstChild))
						{
							$obj->lifeSpan = $node->firstChild->nodeValue;
						}
					}
					
					foreach($xpath->query('p:subjectScope', $p) as $node)
					{
						if (isset($node->firstChild))
						{
							$obj->subjectScope[] = $node->firstChild->nodeValue;
						}
					}
					
					foreach($xpath->query('p:geographicScope', $p) as $node)
					{
						if (isset($node->firstChild))
						{
							$obj->geographicScope[] = $node->firstChild->nodeValue;
						}
					}
					
					
				}
				
				
				// print_r($obj);
				
				$keys = array();
				$values = array();

				foreach ($obj as $k => $v)
				{
					$keys[] = '"' . $k . '"'; // must be double quotes

					if (is_array($v))
					{
						$values[] = "'" . str_replace("'", "''", json_encode(array_values($v))) . "'";
					}
					elseif(is_object($v))
					{
						$values[] = "'" . str_replace("'", "''", json_encode($v)) . "'";
					}
					elseif (preg_match('/^POINT/', $v))
					{
						$values[] = "ST_GeomFromText('" . $v . "', 4326)";
					}
					else
					{               
						$values[] = "'" . str_replace("'", "''", $v) . "'";
					}                   
				}

				$sql = 'INSERT INTO authors (' . join(",", $keys) . ') VALUES (' . join(",", $values) . ') ON CONFLICT DO NOTHING;';                   
				$sql .= "\n";

				echo $sql;
				
			}
		}		
	}
}

?>
