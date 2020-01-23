# datasearch
Scripts for querying public data APIs

### CKAN_search.php
User can select one of the CKAN APIs and specify search terms and max number of results to return. Returns table of results and number found for selected API.

### CKAN_search_all.php
User can specify search terms. Returns table of results and number of finds. Identifies duplicates (by dataset name). 

### magda_search.php
Search facility for data.gov.au. Uses Magda API. User can specify search term and optional search tags(keywords). Very similar in output to CKAN search results.

#### magda_packages.php (outdated, no longer used)
Very basic script. User needs to specify search terms in script. All functionality now in magda_search.php. Only keeping it as it has the older code that lists rganisations and facets at start of output.

For CKAN APIs to query, please see ckan_apis.json. This has a short name, url and API key (if any). 
Note: if publishing this file, remove vic api key as it is a Cerdi one, and add a note for user to update file with their key.

