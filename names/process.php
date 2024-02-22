<?php

// Import RDF as SQL

$basedir = '/rdf';

$files1 = scandir(dirname(__FILE__) . '/' . $basedir);
//$files1 = array('77255');

foreach ($files1 as $directory)
{
	//echo $directory . "\n";
	if (preg_match('/^\d+$/', $directory))
	{	
		//echo $directory . "\n";
		
		$files2 = scandir(dirname(__FILE__) . '/' . $basedir . '/' . $directory);
		
		//$files2 = array('77255112.xml');
		
		foreach ($files2 as $filename)
		{
			//echo $filename . "\n";
			if (preg_match('/\.xml$/', $filename))
			{	
				$xml = file_get_contents(dirname(__FILE__) . '/' . $basedir . '/' . $directory . '/' . $filename);

				$dom= new DOMDocument;
				$dom->loadXML($xml);
				$xpath = new DOMXPath($dom);
	
				$xpath->registerNamespace('dc',      'http://purl.org/dc/elements/1.1/');
				$xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
				$xpath->registerNamespace('tm',      'http://rs.tdwg.org/ontology/voc/Team#');
				$xpath->registerNamespace('tcom',    'http://rs.tdwg.org/ontology/voc/Common#');
				$xpath->registerNamespace('tn',      'http://rs.tdwg.org/ontology/voc/TaxonName#');
				$xpath->registerNamespace('p', 		 'http://rs.tdwg.org/ontology/voc/Person#');
				$xpath->registerNamespace('rdfs',    'http://www.w3.org/2000/01/rdf-schema#');
				$xpath->registerNamespace('rdf',     'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
				$xpath->registerNamespace('owl',     'http://www.w3.org/2002/07/owl#');

/*
  <tn:TaxonName rdf:about="urn:lsid:ipni.org:names:77255112-1">
    
    <tcom:versionedAs rdf:resource="urn:lsid:ipni.org:names:77255112-1:1.1" />
    
    <tn:nomenclaturalCode rdf:resource="http://rs.tdwg.org/ontology/voc/TaxonName#botanical" />
    <owl:versionInfo>1.1</owl:versionInfo>
    <dcterms:title>Sideritis scordioides subsp. cavanillesii (Lag.) Nyman</dcterms:title>
    <tn:rankString>subsp.</tn:rankString>
    <tn:nameComplete>Sideritis scordioides subsp. cavanillesii</tn:nameComplete>
    <tn:uninomial>Sideritis scordioides subsp. cavanillesii</tn:uninomial>
    <tn:genusPart>Sideritis</tn:genusPart>
    <tn:specificEpithet>scordioides</tn:specificEpithet>
    <tn:authorship>(Lag.) Nyman</tn:authorship>
    <tn:basionymAuthorship>(Lag.)</tn:basionymAuthorship>
    <tn:combinationAuthorship>Nyman
    </tn:combinationAuthorship>
    <tn:authorteam>
      <tm:Team>
        
        <tm:name>Lag.</tm:name>
        <tm:hasMember rdf:resource="urn:lsid:ipni.org:authors:5205-1" tm:index="2" tm:role="Basionym Author" />
        
        <tm:name>Nyman</tm:name>
        <tm:hasMember rdf:resource="" tm:index="1" tm:role="Combination Author" />
        
      </tm:Team>
    </tn:authorteam>
    <tn:hasBasionym rdf:resource="urn:lsid:ipni.org:names:458853-1"></tn:hasBasionym>
    
    <tcom:publishedIn>Consp. Fl. Eur.</tcom:publishedIn>
    <tcom:publishedInCitation>http://www.biodiversitylibrary.org/openurl?ctx_ver&#x3D;Z39.88-2004&amp;rft.date&#x3D;1881&amp;rft.spage&#x3D;583&amp;rft_id&#x3D;http://www.biodiversitylibrary.org/bibliography/10533&amp;rft_val_fmt&#x3D;info:ofi/fmt:kev:mtx:book&amp;url_ver&#x3D;z39.88-2004
    </tcom:publishedInCitation>
    <tcom:microReference>583</tcom:microReference>
    <tn:year>1881</tn:year>
    
    
    
    <dcterms:bibliographicCitation>http://www.biodiversitylibrary.org/openurl?ctx_ver&#x3D;Z39.88-2004&amp;rft.date&#x3D;1881&amp;rft.spage&#x3D;583&amp;rft_id&#x3D;http://www.biodiversitylibrary.org/bibliography/10533&amp;rft_val_fmt&#x3D;info:ofi/fmt:kev:mtx:book&amp;url_ver&#x3D;z39.88-2004
    </dcterms:bibliographicCitation>
    
  </tn:TaxonName>
</rdf:RDF>		

*/		
	
				$obj = new stdclass;
	
				// Identifier (must be position 1 because if basionymn present IF adds it as well
				$nodeCollection = $xpath->query ('//tn:TaxonName/@rdf:about');
				foreach ($nodeCollection as $node)
				{
					$obj->id = str_replace('urn:lsid:ipni.org:names:', '', $node->firstChild->nodeValue);
				}
				
				// version
				$nodeCollection = $xpath->query ('//owl:versionInfo');
				foreach ($nodeCollection as $node)
				{
					$obj->version = $node->firstChild->nodeValue;
				}				
				
				// basionym
				$nodeCollection = $xpath->query ('//tn:hasBasionym/@rdf:resource');
				foreach ($nodeCollection as $node)
				{
					$obj->basionymid = str_replace('urn:lsid:ipni.org:names:', '', $node->firstChild->nodeValue);
				}

				// Name
				$nodeCollection = $xpath->query ('//tn:nameComplete');
				foreach ($nodeCollection as $node)
				{
					$obj->fullnamewithoutfamilyandauthors = $node->firstChild->nodeValue;
				}

				$nodeCollection = $xpath->query ('//tn:genusPart');
				foreach ($nodeCollection as $node)
				{
					$obj->genus = $node->firstChild->nodeValue;
				}
				$nodeCollection = $xpath->query ('//tn:specificEpithet');
				foreach ($nodeCollection as $node)
				{
					$obj->species = $node->firstChild->nodeValue;
				}
				$nodeCollection = $xpath->query ('//tn:infraspecificEpithet');
				foreach ($nodeCollection as $node)
				{
					$obj->infraspecifies = $node->firstChild->nodeValue;
				}
				
				$nodeCollection = $xpath->query ('//tn:authorship');
				foreach ($nodeCollection as $node)
				{
					$obj->authors = trim($node->firstChild->nodeValue);
				}
				$nodeCollection = $xpath->query ('//tn:basionymAuthorship');
				foreach ($nodeCollection as $node)
				{
					$obj->basionymauthor = trim($node->firstChild->nodeValue);
				}
				
				$nodeCollection = $xpath->query ('//tn:combinationAuthorship');
				foreach ($nodeCollection as $node)
				{
					$obj->publishingauthor = trim($node->firstChild->nodeValue);
				}

				$nodeCollection = $xpath->query ('//tn:rankString');
				foreach ($nodeCollection as $node)
				{
					$obj->rank = $node->firstChild->nodeValue;
				}
	
				// publication
				$nodeCollection = $xpath->query ('//tcom:publishedIn');
				foreach ($nodeCollection as $node)
				{
					$obj->publication = $node->firstChild->nodeValue;
				}

				$nodeCollection = $xpath->query ('//tcom:microReference');
				foreach ($nodeCollection as $node)
				{
					$obj->collation = $node->firstChild->nodeValue;
				}
	
				$nodeCollection = $xpath->query ('//tn:year');
				foreach ($nodeCollection as $node)
				{
					$obj->publicationyearfull = $node->firstChild->nodeValue;
				}

				$nodeCollection = $xpath->query ('//dcterms:bibliographicCitation');
				foreach ($nodeCollection as $node)
				{
					if (preg_match('/https?:\/\/(www.)?biodiversitylibrary.org/', $node->firstChild->nodeValue, $m))
					{
						$obj->bhl_openurl_ipni = trim($node->firstChild->nodeValue);
					}

					if (preg_match('/https?:\/\/(dx\.)?doi.org\/(?<doi>.*)/', $node->firstChild->nodeValue, $m))
					{
						$obj->remarks = trim($node->firstChild->nodeValue);
						$obj->doi = strtolower(trim($m['doi']));
					}
				}

				//print_r($obj);
				
				if (isset($obj->id))
				{
	
					$keys = array();
					$values = array();
	
					foreach ($obj as $k => $v)
					{
						$keys[] = strtolower($k);
						$values[] = "'" . str_replace("'", "''", $v) . "'";
					}
				
					$keys[] = 'updated';
					$values[] = 'CURRENT_TIMESTAMP';
	
					$sql = 'INSERT OR IGNORE INTO names ('
						. join(",", $keys) . ') VALUES ('
						. join(",", $values) . ');';
		
					echo $sql . "\n";
				}
			}
		}
	}
}	

?>
