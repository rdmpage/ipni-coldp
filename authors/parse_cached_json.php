<?php

// Parse cached JSON

$database = 'ipni_authors';

// Fetch JSON files
$basedir = 'json';

$files1 = scandir($basedir);

//$files1 = array('0');

foreach ($files1 as $directory)
{
	// modulo 1000 directories
	if (preg_match('/^\d+$/', $directory))
	{	
		$files2 = scandir($basedir . '/' . $directory);

		// individual JSON files
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/\.json$/', $filename))
			{	
				$full_filename = $basedir . '/' . $directory . '/' . $filename;
				
				$json = file_get_contents($full_filename);
				
				// echo $json;
				
				$obj = json_decode($json);
				
				if (isset($obj->alternativeAbbreviations))
				{
					$abbreviations = explode(';', $obj->alternativeAbbreviations);
					foreach ($abbreviations as $abbreviation)
					{
						if (preg_match('/(?<abbreviation>.*)\s+From\s+TL2/', $abbreviation, $m))
						{
							echo "UPDATE authors SET tl2='" . str_replace("'", "''", $m['abbreviation']) . "' WHERE id='" . $obj->id . "';\n";
						}
					}
				}
				
				if (isset($obj->bhlPageLink))
				{
					if (preg_match('/(www.)?biodiversitylibrary.org\/page\/(?<bhl>\d+)/', $obj->bhlPageLink, $m))
					{
						echo "UPDATE authors SET bhl='" . $m['bhl'] . "' WHERE id='" . $obj->id . "';\n";
					}
				}			
				
			}
		}		
	}
}

?>
