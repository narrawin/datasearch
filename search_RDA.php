<?php
//	---------------------------------------------------------------------------------
//	Json extraction utility for extracting dataset listing from RDA getRIFCS API  (returns XML)
//	author:		C Bahlo
//	notes: 		- see test scripts in postman 
//				- search result is filtered for datasets (otherwise search will also giv persons, orgs etc.)
//				- there are lots of results, so need batches set start and startRow and rows.
//	
//	
//	---------------------------------------------------------------------------------

// variables to set:

$query = "/getRIFCS?q=copper+AND+type%3A%28dataset%29";	//unrestricted search and type:(dataset)
//$query = "/getRIFCS?q=class%3A%28collection%29%20AND%20title_search%3A%28%2Acopper%2A%29";	//look for *beef" in title, collections only
//$query = "/getRIFCS?q=class%3A%28collection%29%20AND%20title_search%3A%28%22livestock%22%20OR%20%22cattle%22%20OR%20%22sheep%22%20OR%20%22grazing%22%20OR%20%22fodder%22%20OR%20%22wool%22%20OR%20%22meat%22%29";	
//collections only, title containing livestock, cattle, sheep etc.

//$query = "/getRIFCS?q=title_search%3A%28%2Alivestock%2A%29%20AND%20class%3A%28collection%29";

// refer to: https://documentation.ardc.edu.au/display/DOC/getRIFCS
$rowsToFetch = 10;
$startRow = 0;	// zero-based
$apikey = "5b4a0666b522";
// note: this is the one used for Agrefed, consider a new one




$start = "&start=" . $startRow;
$rows = "&rows=" . $rowsToFetch;

$url = "https://researchdata.ands.org.au/registry/services/" . $apikey . $query . $start . $rows;
//https://researchdata.ands.org.au/registry/services/5b4a0666b522/getRIFCS?q=livestock+AND+type%3A%28dataset%29&rows=10

// to use a file created from an API response run in Postman:
//$url = "http://localhost/cb-scripts/datasearch/RDA_getExtRif_livestock.xml";
//$url = "http://localhost/cb-scripts/datasearch/RDA_getExtRif_livestock_type=dataset.xml";
//$url = "http://localhost/cb-scripts/datasearch/RDA_getRIFCS_fodder_type=dataset.xml";


$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET"
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  //echo $response;
}

$xml = simplexml_load_string($response);
//print_r($xml);
$json = json_encode($xml);
$registryObjects = json_decode($json,TRUE);
$items = ($registryObjects['registryObject']);
var_dump($items);

echo "<h2>" . $url . "</h2>";
echo "<p>url: " . $url . "</p>";

// make table with fields as required
echo '<style> table {border-collapse: collapse;} table, th, td {border: 1px solid black; vertical-align: text-top;}</style>';
echo '<table><tr>';
	echo '<th>#</th>';
	echo '<th>API</th>';
	echo '<th>key</th>';
	echo '<th>Name</th>';
	// echo '<th>Identifiers</th>';
	echo '<th>Description</th>';
	echo '<th>location</th>';
	echo '<th>date issued</th>';
	echo '<th>publisher</th>';
	echo '<th>catalogue</th>';
	echo '<th>Keywords</th>';
	echo '<th>Coverage</th>';
	echo '<th>Start Date</th>';
	echo '<th>End Date</th>';
	echo '<th>Last Modified</th>';
	echo '<th>Distributions</th>';
	echo '<th>Licence Type</th>';
echo '</tr>';

$count = $startRow;	// incase we are paging results

foreach ($items as $item) {

	$count+=1;
	
	echo "<tr>";
	echo "<td>" . $count . "</td>";
	echo "<td>RDA</td>";
	//echo "<td>" . $item['key'] . "</td>";
	echo "<td>"; // fix: sometimes identifier is not an array
	if (is_array($item['collection']['identifier'])) {
		foreach ($item['collection']['identifier'] as $identifier) {
			echo $identifier['@attributes']['type'] . '; ';
		}
	} else {
		echo $item['collection']['identifier'];	
	}	
	echo "</td>";

	echo "<td>" . $item['collection']['name']['namePart'] . "</td>";
	if (is_array($item['collection']['description'])) {
		echo "<td>" . $item['collection']['description'][0] . "</td>";
	} else {
		echo "<td>" . $item['collection']['description'] . "</td>";
	}	
	echo "<td>" . $item['collection']['location']['address']['electronic']['value'] . "</td>";
	echo "<td>" . "</td>";	// date issued - not present, col for layout only
	echo "<td>" . $item['originatingSource'] . "</td>";
	echo "<td>" . "</td>"; // catalogue - not present, col for layout only
	echo "<td>";	// subject - use for keywords
		foreach ($item['collection']['subject'] as $subject) {
			echo $subject . ', ';
		}
	echo "</td>";
	echo "<td>"; // fix: sometimes coverage is array
	if (is_array($item['collection']['coverage']['spatial'])) {
		foreach ($item['collection']['coverage']['spatial'] as $spatialInfo) {
			echo $spatialInfo . '<br> ';
		}
	} else {
		echo $item['collection']['coverage']['spatial'];	
	}	
	echo "</td>";

	echo "<td>" . $item['collection']['coverage']['temporal']['date'][0] . "</td>";
	echo "<td>" . $item['collection']['coverage']['temporal']['date'][1] . "</td>";
	echo "<td>" . $item['collection']['@attributes']['dateModified'] . "</td>";
	echo "<td>" . "</td>"; // distributions - not present in result set

	echo "<td>";
	// rights info is complex because it varies where in the record the info is kept, the following has been tried 
	// and refined over several iterations and appears to return all licence info kept under the rights key.

	if (is_string($item['collection']['rights']['licence'])) {
		echo $item['collection']['rights']['licence'] . " ";
	} else {
		echo $item['collection']['rights']['licence']['@attributes']['type'];
	}

	if (is_string($item['collection']['rights']['accessRights'])) {
		echo $item['collection']['rights']['accessRights'] . " ";
	}
	if (is_string($item['collection']['rights']['rightsStatement'])) {
		echo $item['collection']['rights']['rightsStatement'] . " ";
	}

	// in case we couldn't find it in the above keys, look further down the array
	echo $item['collection']['rights'][0]['accessRights']['@attributes']['type'] . " ";
	echo $item['collection']['rights'][1]['licence'];

	echo "</td>";

	echo "</tr>";	
}

echo '</table>';
