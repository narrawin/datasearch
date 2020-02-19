# datasearch
Scripts for querying public data APIs

### CKAN_search.php
User can select one or more of the CKAN APIs and specify search terms and max number of results to return. Returns a html table of results and total number of datasets found, highlighting any duplicates found. 

This table can be copied and pasted into a spreadsheet for further processing. 

### magda_search.php
Search facility for data.gov.au. Uses Magda API. User can specify search term and optional search tags(keywords). Very similar in output to CKAN search results.

### CKAN_search_all.php (outdated, no longer used)
User can specify search terms. Returns table of results and number of finds. Identifies duplicates (by dataset name). 

#### magda_packages.php (outdated, no longer used)
Very basic script. User needs to specify search terms in script. All functionality now in magda_search.php. Only keeping it as it has the older code that lists rganisations and facets at start of output.

CKAN APIs as per ckan_apis.json. Name has to be unique, and needs a url and API key (so far only needed for VIC CKAN API).

Note: if publishing this file, remove vic api key as it is a Cerdi one, and add a note for user to update file with their key.

