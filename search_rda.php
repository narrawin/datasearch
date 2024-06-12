<?php
//	---------------------------------------------------------------------------------
//	Json extraction utility for extracting dataset listing from RDA getRIFCS API  (returns XML)
//  refer to: https://documentation.ardc.edu.au/rda/getrifcs

//	author:		C Bahlo
//	notes: 		- see test scripts in postman 
//				- search result is filtered for datasets (otherwise search will also giv persons, orgs etc.)
//				- there are lots of results, so need batches set start and startRow and rows.
//	
//	
//	---------------------------------------------------------------------------------

// variables to set:

$query = "title:(*canopy*)";	
$rows_to_fetch = 10;
$offset = 0;
$sort = ""; // not used, but is possibl, refer doco

$params = urlencode("q=". $query . "&start=" . $offset . "&rows=" . $rows_to_fetch);

// if available, use "local" file which contains private api keys (file not in repo)
$rda_json_file = "rda_api_local.json"; 

// use general json file (contains no api keys)
if(!file_exists($rda_json_file)) {
	$rda_json_file = "rda_api.json";
} 

$json = file_get_contents($rda_json_file);
$api_data = json_decode($json, true);
$rda_api = $api_data['APIs'][0];	// array only has one el
//var_dump($rda_api);


if(isset($rda_api['api_key'])) {
	$apikey = $rda_api['api_key'];
} else {
	echo "You need to add an RDA api key to rda_api.json!";
	exit();
}

$url = $rda_api['url'] . $apikey . "/getRIFCS?" . $params;

echo $url;

$curl = curl_init();
curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_HTTPHEADER => array(
			"Accept: */*",
			"Accept-Encoding: gzip, deflate, br",
			"Connection: keep-alive",
			"Host: http://researchdata.edu.au",
			),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "<br>cURL Error #:" . $err . "<br>";
} else {
  echo $response;
}

// **** NOTE: THIS IS STILL RETURNING NOTHING ALTHOUGH THIS WORKS IN POSTMAN

$xml = simplexml_load_string($response);
print_r($xml);
$json = json_encode($xml);

$json = $response;


$registryObjects = json_decode($json,TRUE);
$items = ($registryObjects['collection']);
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
