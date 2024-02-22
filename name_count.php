<?php

$headings = array();

$row_count = 0;

$filename = "other-data/powoNames/taxon.txt";


$headings = array(

'taxonID',
'modified',
'verbatimTaxonRank',
'scientificName',
'family',
'genus',
'specificEpithet',
'infraspecificEpithet',
'scientificNameAuthorship',
'nomenclaturalStatus',
'rightsHolder',
'namePublishedInYear',
'nomenclaturalCode',
'taxonRemarks',
'bibliographicCitation',
'language',
'class',
'references',
'license',
'rights',
'namePublishedIn',
'taxonRank',
'kingdom',
'phylum',
'parentNameUsageID',
'acceptedNameUsageID',
'originalNameUsageID',
'taxonomicStatus',
'source',
'order',
'dynamicProperties',
);

$counts = array(
'name' => 0,
'species' => 0,
'homotypic' => 0,
'heterotypic' => 0,
'accepted' => 0,
);

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$counts['name']++;

	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		$obj = new stdclass;
	
		foreach ($row as $k => $v)
		{
			if ($v != '')
			{
				$obj->{$headings[$k]} = $v;
			}
		}
		
		//print_r($obj);	
		
		if (isset($obj->taxonRank) && ($obj->taxonRank == 'SPECIES'))
		{
			$counts['species']++;	

			if (isset($obj->taxonomicStatus))
			{
				switch ($obj->taxonomicStatus)
				{
					case 'Homotypic_Synonym':
						$counts['homotypic']++;
						break;
					
					case 'Heterotypic_Synonym':
						$counts['heterotypic']++;
						break;

					case 'Accepted':
						$counts['accepted']++;
						break;
					
					default:
						break;
					
				
				}
			
			}
		}	
		
		
	}	
	$row_count++;	
	
	//if ($row_count==10000) break;
	
}	


print_r($counts);




