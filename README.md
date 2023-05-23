# International Plant Names Index (IPNI) as a Catalogue of Life Data Package (ColDP)

[![DOI](https://zenodo.org/badge/528876012.svg)](https://zenodo.org/badge/latestdoi/528876012)

A version of [International Plant Names Index (IPNI)](https://www.ipni.org) with the addition of persistent identifiers (e.g., DOIs). 

## Notes

### Exports and releases

The data to add to ChecklistBank are held in the views `names_with_references` and `references` in the SQLIte database. These views should be exported as `names.tsv` and `references.tsv` respectively (in tab-delimited format), and together with the `metadata.yml` file comprise a release.  Releases are versioned by date, and automatically get assigned a DOI via Zenodo. 

Note that the release should only include ColDP files so anything else should not be in the release. Add any unwanted files to a file called `.gitattributes`:

```
/code export-ignore
/junk export-ignore
*.db export-ignore
*.bbprojectd export-ignore
*.md export-ignore
*.rested export-ignore

*.gitattributes export-ignore
*.gitignore export-ignore
```

### Adding to ChecklistBank

For now this process is not automated, so we need to manually upload the three files (names.tsv`, `references.tsv`, and `metadata.yml`) to ChecklistBank.

### Triple store

Create triples using `export-triples.php` which generates triples creating taxon name and linking to publication. Can upload to local Oxigraph for testing and exploration. 

```
curl 'http://localhost:7878/store?graph=https://www.ipni.org' -H 'Content-Type:application/n-triples' --data-binary '@triples.nt'  --progress-bar
```


## Issues

### Triggers

Trigger to touch the `updated` value every time we edit a row.

```sql
CREATE TRIGGER names_updated AFTER UPDATE ON names FOR EACH ROW
BEGIN

UPDATE names
SET
    updated = CURRENT_TIMESTAMP
WHERE id = old.id;

END;
```
### Reference ids

The ColDP model assumes that we have reference-level identifiers, that is, `reference.ID` is a work-level reference. For datasets like IPNI where references are typically microcitations (i.e., pointers to a specific page) this model doesn’t work. The creators of Index Fungorum have adopted the approach of using the integer id for the name as the reference id as well, thus ensuring that it is unique within Index Fungorum. The creators of IPNI use DOIs as reference ids when available. However, the `/` does not play nice with the ColDP interface (even when URI encoded in the dataset).

One approach is to create a reference id based on the external identifiers for a reference, or which there may be more than one. The SQLite `COALESCE` function would be useful here.

```sql
-- create a referenceID for all names with some sort of identifier
UPDATE names SET referenceID =
         COALESCE(	
        	wikidata,
        	IIF(doi, ('doi:' || doi), NULL),
          IIF(handle, ('hdl:' || handle), NULL),
        	IIF(jstor, ('jstor:' || jstor), NULL),
          IIF(biostor, ('biostor:' || biostor), NULL),
          url,
          pdf,
          cinii,
          isbn
    	)
 WHERE wikidata IS NOT NULL OR doi IS NOT NULL OR jstor IS NOT NULL OR biostor IS NOT NULL OR url IS NOT NULL OR pdf IS NOT NULL OR cinii IS NOT NULL OR isbn IS NOT NULL;
```

By default the reference id is the Wikidata item id.

We can also add a trigger to update `referenceID` whenever a row is edited. 

```sql
CREATE TRIGGER referenceID_updated AFTER UPDATE ON names FOR EACH ROW
BEGIN
UPDATE names SET referenceID =
	COALESCE(	
		wikidata,
		IIF(doi, ('doi:' || doi), NULL),
		IIF(handle, ('hdl:' || handle), NULL),
		IIF(jstor, ('jstor:' || jstor), NULL),
		IIF(biostor, ('biostor:' || biostor), NULL),
		url,
		pdf,
    cinii,
    isbn
	)
WHERE id = old.id;
END;
```

### Missing names

- [Toxicodendron oligophyllum](https://www.checklistbank.org/dataset/20231/taxon/038FCC78D27DAE3EFF6BF8FCD4E9FC5E.taxon) is a treatment for a taxon not yet in local copy of IPNI.

