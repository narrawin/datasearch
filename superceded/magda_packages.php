<?php
//	---------------------------------------------------------------------------------
//	Json extraction utility for agricultural data from MAGDA API at data.gov.au 
//	author:		C Bahlo
//	notes: 		see test scripts in postman 
//				set filters as required			
//	
//	
//	
//	---------------------------------------------------------------------------------


$query = "soil";

$filter_for_keyword = "";	// must be lower case; set to empty string to return all results
$filter_for_keyword = $query;

$resourceType = "";	// specify wms for example
$datasets = 500;		// set max datasets - adjust as needed (default 10)
$facets = 400;		// set max facets high to get table with all formats

$formatFilter = false;	// only show resources that have the specified format. Set to false to show all resource formats per dataset

$filter = "?limit=" . $datasets . "&facetSize=" . $facets . "&query=" . $query . "&format=" . $resourceType;

$url = "https://data.gov.au/api/v0/search/datasets" . $filter;

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

$data = json_decode($response, true);

echo "<h2>data.gov.au MAGDA search results (keyword agnostic): " . $data['hitCount'] . "</h2>";
echo "<h2>API call: " . $url . "</h2>";
echo "<p>Keyword filter is set to: " . $filter_for_keyword . "</p>";
echo "<p>For results after filtering for keyword, please refer to number of datasets in table (ensure that $datasets > no of search results.</p>";

//  --------- make publisher table from returned facet information  ---------
$facets = $data['facets'];
$publishers = $facets[0]['options'];
$totalHits = 0;

echo "<h3>Publishers represented in search results</h3>";
echo '<style> table {border-collapse: collapse;} table, th, td {border: 1px solid black; vertical-align: text-top;}</style>';
echo '<table><tr>';
echo '<th>#</th>';
echo '<th>ID</th>';
echo '<th>Name</th>';
echo '<th>Hits</th>';
echo '</tr>';

foreach ($publishers as $publisher) {
	$count+=1;
	echo "<tr>";
	echo "<td>" . $count . '</td>';
	echo "<td>" . $publisher['identifier'] . "</td>";
	echo "<td>" . $publisher['value'] . '</td>';
	echo "<td>" . $publisher['hitCount'] . '</td>';
	$totalHits = $totalHits + $publisher['hitCount'];
	echo "</tr>";
}

echo '</table>';
echo "<p><b>total hits for listed publishers: " . $totalHits . "</b></p>";
// --------------------------------------------------------------------------------------
// make formats table from returned facet information  ---------
$facets = $data['facets'];
$formats = $facets[1]['options'];
$totalHits = 0;
$count = 0;

echo "<h3>Data formats in database. If search filters for format, this is shown first in results</h3>";
echo '<style> table {border-collapse: collapse;} table, th, td {border: 1px solid black; vertical-align: text-top;}</style>';
echo '<table><tr>';
echo '<th>#</th>';
echo '<th>Name</th>';
echo '<th>Hits</th>';
echo '</tr>';

foreach ($formats as $format) {
	$count+=1;
	echo "<tr>";
	echo "<td>" . $count . '</td>';
	echo "<td>" . $format['value'] . '</td>';
	echo "<td>" . $format['hitCount'] . '</td>';
	$totalHits = $totalHits + $format['hitCount'];
	echo "</tr>";
}

echo '</table>';
echo "<p><b>total hits for listed formats: " . $totalHits . "</b></p>";


// --------------- make results table with fields as required ----------------------------------------

$datasets = $data['dataSets'];
$count = 0;

if (strlen($filter_for_keyword)) {
	$use_keyword_filter = true;
} else {
	$use_keyword_filter = false;
}


echo "<h3>Datasets found</h3>";
echo '<style> table {border-collapse: collapse;} table, th, td {border: 1px solid black; vertical-align: text-top;}</style>';
echo '<table><tr>';
echo '<th>#</th>';
echo '<th>ID</th>';
echo '<th>Org ID</th>';
echo '<th>Landing page</th>';
echo '<th>Date issued</th>';
echo '<th>Publisher</th>';
echo '<th>Catalog</th>';
echo '<th>Title</th>';
echo '<th>Distributions</th>';
echo '<th>Description</th>';
echo '<th>Keywords</th>';
echo '<th>Spatial</th>';
echo '<th>Start</th>';
echo '<th>End</th>';
echo '<th>Updates</th>';
echo '</tr>';



foreach ($datasets as $ds) {

	$has_keyword = false;

	if ($filter_for_keyword == true) {
		foreach ($ds['keywords'] as $keyword) {
			if (strpos(strtolower($keyword), $filter_for_keyword) !== false) {
			    $has_keyword = true;
			}
		}
	}

	// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
	if ($use_keyword_filter == false || $has_keyword == true) {

		$count+=1;
		echo "<tr>";
		echo "<td>" . $count . '</td>';
		echo "<td>" . $ds['identifier'] . "</td>";
		echo "<td>" . $ds['publisher']['identifier'] . "</td>";
		echo "<td><a href='" . $ds['landingPage'] . "'>" . $ds['landingPage'] . "</a></td>";
		echo "<td>" . $ds['issued'] . date_format(date($ds['issued'],"d/m/y")) . "</td>";
		echo "<td>" . $ds['publisher']['name'] . '</td>';		
		echo "<td>" . $ds['catalog'] . "</td>";
		echo "<td>" . $ds['title'] . '</td>';
		echo "<td><table>";
		
		foreach ($ds['distributions'] as $resource) {
			$listResource = true;		// check if non-matching resources are to be filtered out
			if ($formatFilter == true ) {
				if (strtoupper($resource['format']) <> strtoupper($resourceType)){
					$listResource = false;
				}
			} 

			if ($listResource == true) {
				// some resources have accessURL, but all have downloadURLs, so use dowloadURLS and link the title
				if($resource['downloadURL'] <> '') {
					$resourceTitle = "<a href='" . $resource['downloadURL'] . "'>" . $resource['title'] . "</a>";
				} else {
					$resourceTitle = $resource['title'];
				}
				echo "<tr>";
				echo "<td>" . $resource['format'] . "</td><td>" . $resourceTitle . "</td><td>" . $resource['license']['name'] . "</td>";
				echo "</tr>";	
			}

		}
		echo "</table></td>";
		echo "<td>" . $ds['description'] . '</td>';
		echo "<td><ul>"; 
		foreach ($ds['keywords'] as $keyword) {
			echo "<li>" . $keyword . "</li>";
		}
		echo "</ul></td>";
		echo "<td>" . $ds['spatial']['text'] . '</td>';
		echo "<td>" . $ds['temporal']['start']['text'] . '</td>';
		echo "<td>" . $ds['temporal']['end']['text'] . '</td>';
		echo "<td>" . $ds['accrualPeriodicity']['text'] . '</td>';
		echo "</tr>";
	}

}

echo '</table>';


