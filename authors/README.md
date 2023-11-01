# IPNI Authors

This par to the repo aims to have a complete list of authors with IPNI ids, to help this wanting to reconcile auto names. IPNI itself do not provide a bulk download of this data.

IPNI’s author data has been used recently to study the gender distribution of plant taxonomists (Lindon et al. 2015). Note that supplementary data and code for this paper seems to not be available from the TAXON web site, but is attached to the version on ResearchGate https://www.researchgate.net/publication/274249267. The data is also in Zenodo https://doi.org/10.5281/zenodo.3911077

More recent paper and dataset (https://doi.org/10.5281/zenodo.7746445) also available.

## LSIDs

Primary data source is RDF harvested via LSIDs.

## Wikidata

The query `SELECT * WHERE { ?item wdt:P586 ?id }` generates list of all Wikidata items with an IPNI author id. I’ve downloaded this list as a TSV file.

## Reading

Lindon, H. L., Gardiner, L. M., Brady, A., & Vorontsova, M. S. (2015). Fewer than three percent of land plant species named by women: Author gender over 260 years. TAXON, 64(2), 209–215. https://doi.org/10.12705/642.4

Lindon, H., Gardiner, L., Vorontsova, M., & Brady, A. (2020). Gendered Author List International Plant Names Index 2020 (Version 2) [Data set]. Zenodo. https://doi.org/10.5281/zenodo.3911077

Lindon, H. L., Gardiner, L. M., & Vorontsova, M. S. (2023). Making The Taxonomic Effort: Data on Index Fungorum and IPNI new species and new combinations author gender (1.0) [Data set]. Zenodo. https://doi.org/10.5281/zenodo.7746445

Lindon, H. L., Gardiner, L. M., & Vorontsova, M. S. (2023) Making the Taxonomic Effort: Acknowledging female role models in botanical and mycological taxonomy. The Linnean: Special Issue 10: 44-59, https://ca1-tls.edcdn.com/LinneanSpecialIssue_No10_TheDoorWasOpened_WomenInScience_Final.pdf