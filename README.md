# datasearch
Scripts for querying Australian public data APIs. These scripts were part of my PhD project, but I have used them for several other projects since then and have made them open source as they may be helpful to others. 

Using PHP over Javascript (with fetch or axios) avoids CORS errors, as several of the APIs don't allow cross origin requests. Note that some of the scripts on the bottom of the page are a few years old now and probably need some work. There is also a new directory with jupyter notebooks with examples.

In future, I hope to update all the scripts and consolidate them. I may add the option to dump output as CSV. For the time being, I copy the html and paste it into a spreadsheet when I need to analyse the results further.

-------------------------
## Dataset search scripts and supporting files

### search_all_V3.php
This is the latest and most complete search script. It run queries on data.gov.au, then the CSIRO Knowledgebase, and finally on all the CKAN catalogues listed in ckan_apis.json. It puts out the results in a table with improved (over the older scripts) formatting and layout. It checks for duplicate datasets based on ID and title (older scripts were by id alone). It shows the finds per repository and lists for each dataset found the repositories it was found in. 

### ckan_apis.json
JSON specification for the list of CKAN APIs to query by search_all.php - used by scripts querying all CKAN instances. 
Name of API has to be unique, and needs a url and API key (note: key needed for VIC CKAN API). Will run without specifying API key, but will return result set without results for that API and will give no error. Note: search-all.php allows the use of a ckan_apis_local.json file which may contain private api keys. If this file is present, it will be used by the script, otherwise ckan_apis.json will be used. The local version of that file is excluded from the repo.

## jupyter notebooks (directory)
I have started adding notebooks to this directory as examples of using some of the APIs within python. Notebooks are a great way to convert json responses to dataframes and then create output in various formats such as csv in a few lines of code, and very useful to prototyping. 

--------------------------
## Older search scripts (some are in the progress of being updated)

### search_all_V2.php
Now superceded by v3.

### search_all.php
Searches magda catalog first and then goes through CKAN instances. Marks madgda results and other sources(work in progress). Output is a html table of results. The form allows ticking a box that will format the output to produce a better spreadsheet without nested cells, and filters out duplicate distributions (and licence info).

### search_ckan.php
Several data catalogues are based on the [CKAN API](https://github.com/ckan/ckan). With this script, a user can select one or more of the available Australian CKAN APIs (refer to ckan_apis.json/ckan_apis_local.json) and specify search terms and max number of results to return. Returns a html table of results and total number of datasets found, highlighting any duplicates found. This table can be copied and pasted into a spreadsheet for further processing. 

### search_magda.php
Data.gov.au is built on the [Magda API](https://magda.io/docs/). This script queries the magda API, a user can specify search term and optional search tags(keywords). Very similar in output to CKAN search results.

### mla_reports_list.html
[Meat & Livestock Australia](https://www.mla.com.au/) publishes market livestock reports. These are available via a public API. This script obtains a list of available reports from that API, and provides a URL to query each report. Details of API can be found [here](http://statistics.mla.com.au/Assets/MLA%20Statistics%20Database%20API%20Methodology.pdf).

### search_rda.php
The [Australioan National Data Service](https://www.ands.org.au/) has several API endpoints for data searches. This is a script using the getRIFCS API or the getExtRif API, and the output is a html table. Details via the [Widgets & APIs](https://documentation.ardc.edu.au/pages/viewpage.action?pageId=81988031) page at ARDC. This script is very basic, API endpoint and query parameters need to be set in the source. Not also that at times the script returns no results from the API endpoint. However, it is possible to obtain an API response (XML) via Postman, save as XML file and then use that saved file as script input to produce formatted output. Refer to comments in script.### ands-search.html
Script that fetches dataset listing from ANDS via registry widget. See [RDA Registry Search Widget](https://documentation.ardc.edu.au/display/DOC/RDA+Registry+Search+Widget) for details. Has a modifiable results template. Very limited in returned fields. The search_RDA script produces more detailed output.

### search_ands.html
Script that fetches dataset listing from ANDS via registry widget. See [RDA Registry Search Widget](https://documentation.ardc.edu.au/display/DOC/RDA+Registry+Search+Widget) for details. Has a modifiable results template. Very limited in returned fields. The search_RDA script produces more detailed output.


### search_csiro_api_v2.php
[CSIRO data](https://data.csiro.au/collections/) is available via DAP Web Services and OpenAPI, details as per the [Developer Tools](https://confluence.csiro.au/display/dap/Developer+Tools) page. This script uses the OpenAPI endpoint, documentation can be found on the [CSIRO Data Access Portal Web Services](https://data.csiro.au/dap/swagger-ui.html#/) page. 

### search_figshare_openapi.php
[Figshare](https://figshare.com/) uses [OpenAPI](https://github.com/OAI/OpenAPI-Specification), and this script searches that catalogue, creating a html table as output.

### search_magda_csiro.php
Essentially the same as magda_search.php, but will query the Magda interface provided by CSIRO. Not used as the other script returns more results, and according to CSIRO documentation, the V2 OpenAPI used by the above script is the more relevant one.

-------------------------
## FAIR-metrics-eval-script.php
The [FAIR Evaluation Services](https://fairsharing.github.io/FAIR-Evaluator-FrontEnd/#/!) allow the testing of datasets against a selected set of FAIR metrics. A collection called [Maturity Indicator collection for Australian Agricutural data](https://fairsharing.github.io/FAIR-Evaluator-FrontEnd/#!collections/15) was created and is the default used by this script. This collection (id 15) consists of 18 individual tests from the FAIR Evaluation Services.
The PHP script runs a FAIR evaluation using the [FAIRMetrics API](https://github.com/FAIRMetrics/Metrics/tree/master/MetricsEvaluatorCode/Ruby/fairmetrics). It defaults to the metrics collection 15, but can be changed in the form shown. 
Note that datasets to be evaluated need to be specified in datasets-to-check.csv. It is recommended to run less than 10 at a time, as the tests are very slow, often taking in excess of 5 minutes per dataset. A user has to specify an ORCID id and provide a title for the test.

-------------------------
## Resultset visualisation (sankey graphs)
Can be found in the /visualise directory. The live version of [Graphs](https://narrawin.github.io/datasearch/visualise) allows the viewing of data in different ways. Data is based on a spreadsheet that summarises results of all the data searches using the above scripts as well as manual searches.

This application shows the output from several data summaries based on that spreadsheet and uses the [D3.js](https://d3js.org/) library, and is based on a sample application by [subrata20011997](https://blockbuilder.org/subrata20011997/e943f89f678eb77d0c9a5c6bbc64986f). Screenshot below:

![Screenshot](visualise.jpg)

-------------------------

### other files see superceded directory
##### CKAN_search_all.php 
User can specify search terms. Returns table of results and number of finds. Identifies duplicates (by dataset name). 

##### magda_packages.php 
Very basic script. User needs to specify search terms in script. All functionality now in magda_search.php. Only keeping it as it has the older code that lists rganisations and facets at start of output.


-------------------------

