<?php

//	---------------------------------------------------------------------------------
//	Open Data query utility for public data from data.gov.au Magda API and CKAN APIs
//	author:		C Bahlo
//	notes: 		Users set query options in form
//				form self-submits and displays a table of results, duplicates are noted		
//				query phrase is used to run a full text search on repos
//				keyword phrase is used to filter full text search results by checking if keyword exists
//	
//	---------------------------------------------------------------------------------

$rows = 1000;
$formatFilter = false;	// could add this as a form option
// need to decide whether to keep this in, which means I need to run it on CKAN APIs as well, also consider getting full list of formats
//$resource_options = array("","wms","wfs","csv","json","tiff","xml","geojson","html","arcgis","esri","kml","pdf");

// get CKAN API details from file
$ckan_json_file = "ckan_apis_local.json"; // use "local" version which may contain api keys (not in repo)

// use general json file (contains no api keys)
if(!file_exists($ckan_json_file)) {
	$ckan_json_file = "ckan_apis.json";
}

$json = file_get_contents($ckan_json_file);
$api_data = json_decode($json, true);
$CKAN_apis = $api_data['APIs'];

// construct header
?>
	<html lang="en">
	<head>
    <meta charset="utf-8">
    <meta name="author" content="Chris Bahlo">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Magda+CKAN API search tool</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
	<style>
		.badge-success, .badge-info {color: black;}
	</style>
	</head>


<?php
if (!isset($_POST['submit'])) { // if page is not submitted, show the form
?>
	<body>
	<div class="container">	
		<h2 class="my-4">Magda & CKAN API Search Tool</h2>
		<p>This search is run on the Magda API of data.gov.au and all CKAN API instances specified in the file <em><?= $ckan_json_file ?></em></em>.</p>
		<p>Two search methods can be used: Full text search and full text with subsequent keyword filter.</p> 
		<p>Full text search will yield the largest result set, as it returns all records where the search word found in any field.
			There will probably be a number of false positives.
			Adding a keyword filter to the full text search perform a case-insensitive search including partial matches in the keyword field.
			This is suggested to reduce the number of false positives. This must be a single word.</p>
		<p>&nbsp;</p>
		<hr/>

		<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
			<div class="row">
				<div class="col-md-6 mb-3">
		            <label for="search_string">1.a. Search word (full text search)</label>
					<input type="text" class="form-control" id="search_string" name="search_string"><br />
	            </div>

				<div class="col-md-6 mb-3">
		            <label for="search_tag">1.b. Search keyword in full text search results</label>
					<input type="text" class="form-control" id="search_tag" name="search_tag"><br />
	            </div>
          	</div>
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="spreadsheet_format" name="spreadsheet_format"><br />
	            <label class="form-check-label" for="spreadsheet_format">Format abbreviated output for spreadsheet</label>
	        </div>

          	<hr/>
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="Submit" name="submit">
			<p>&nbsp;</p>
			<p>Note: processing may take a little time depending on the size number of datasets found.</p>
		</form>
	</div>	

<?php
} else {	//run queries and display in a web page

	$spreadsheet_format = ($_POST["spreadsheet_format"] ?? "off");

	$filter = "?limit=" . $rows;	// construct filter (already urlencoded)

	if (isset($_POST["search_string"])) {
		$search_string = $_POST["search_string"];
		
	} else {
		echo "<p class='danger'>You must specify a search string!</p>";
		exit;
	}

	$filter .= "&query=" . rawurlencode($search_string);

	if (isset($_POST["search_resource_type"])) {
		$filter .= "&format=" . $search_resource_type;
	} 

	if (isset($_POST["search_tag"])) {
		$use_tag_filter = true;
		$search_tag = $_POST["search_tag"];
	} else {
		$use_tag_filter = false;
	}

	echo '<body>';
	echo "<h3>Query of data.gov.au and various CKAN instances</h3>";
	echo "<h4>Query: " . $search_string. ($_POST["search_tag"] ?? " | filtered for tag: " . $search_tag) . "</h4>";
	echo '<h4 id="count">Working on it ....</h4>';
	
	// First run query on Magda API
	
	$url = "https://data.gov.au/api/v0/search/datasets" . $filter;
	$curl = curl_init();

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
	  CURLOPT_SSL_VERIFYHOST => false
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  // echo $response;	// for testing
	}

	$data = json_decode($response, true);
	$datasets = $data['dataSets'];
	$count = 0;
	$result_datasets = [];

	foreach ($datasets as $ds) {	// go through Magda datasets and filter for keywords (if specified)

		$has_tag = false;

		if ($use_tag_filter == true) {
			foreach ($ds['keywords'] as $tag) {
				if (strpos(strtolower($tag), strtolower($search_tag)) !== false) {
				    $has_tag = true;	// have found case-insensitive match (including part of word)
				    break;
				}
			}
		}
		// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
		if ($use_tag_filter == false || $has_tag == true) {
			$ds['api'] = '<span class="badge badge-success">Magda</span>';
			$result_datasets[] = $ds;	// append this to results array so we know origin is data.gov.au when we query CKAN APIs later
			$count+=1;
		}
	}


// start processing of CKAN APIs as per json file ------------------------------------------------

	// construct filter to pass to API
	$filter = "?rows=" . $rows;

	if ($search_string <> "") {
		$filter .= "&q=" . rawurlencode($search_string);
	} 

	foreach($CKAN_apis as $api) {
	    $api_url = $api['url'];

		// call API
		$url = $api_url . $filter;
		$curl = curl_init();

		// if vic ckan api, need a key, and pass in header
		if (strpos($api['name'], 'vic.gov.au') !== false) {
			$api_key = "apikey: " . $api['api_key'];

			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
				"Accept: */*",
				"Accept-Encoding: gzip, deflate",
				"Cache-Control: no-cache",
				"Connection: keep-alive",
				"Host: https://wovg-community.gateway.prod.api.vic.gov.au",
				$api_key,
				"cache-control: no-cache"
				),
				CURLOPT_SSL_VERIFYPEER => false,
	  			CURLOPT_SSL_VERIFYHOST => false
			));
		} else {	// all other CKAN apis don't need a key
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false
			));
		}

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		  echo "\n encountered when processing: " . $api_url; 
		} else {
		  // echo $response;	// for testing
		}

		$data = json_decode($response, true);
		//$datasets = ($data['result']['results']);
		$datasets = isset($data['result']['results']) ? $data['result']['results'] : [];

		// if (strlen($search_tag)) {
		// 	$use_tag_filter = true;
		// } else {
		// 	$use_tag_filter = false;
		// }

		foreach ($datasets as $ds) {

			$has_tag = false;

			if ($use_tag_filter == true) {
				foreach ($ds['tags'] as $tag) {
					if (strpos(strtolower($tag['name']), strtolower($search_tag)) !== false) {
					    $has_tag = true;	// have found case-insensitive match (including part of word)
					    break;
					}
				}
			}

			// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
			if ($use_tag_filter == false || $has_tag == true) {
				// if this dataset is a duplicate of one already found (by id partial match on id field), then update row, otherwise add this ds to the list 
				$add_current_ds = true;
				$rds_idx = 0;
				
				foreach ($result_datasets as $already_found) {

					//echo strpos($already_found['identifier'], $ds['id']) . " -- <br>";

					if (strpos($already_found['identifier'], $ds['id']) !== false) { // if the id of the curr CKAN ds is the same as one already in the list, don't add again.
						//echo $result_datasets[$rds_idx]['api'] . " .. + " . '<span class="badge badge-warning">' . $api["name"] .'</span>' . "<br>";
						$result_datasets[$rds_idx]['api'] = $result_datasets[$rds_idx]['api'] . ' <span class="badge badge-warning">' . $api["name"] .'</span>';
						$add_current_ds = false;
						break;
					}

					$rds_idx +=1;
				}

				if ($add_current_ds == true) {	// there is no entry for this dataset in magda (by id), so add it to the results array

					$CKAN_ds = [];	// mt array to assemble dataset detail for current CKAN dataset, so we can append that to total results array

					$CKAN_ds['api'] = '<span class="badge badge-warning">' . $api["name"] .'</span>';	// mark ckan api name
					$CKAN_ds['identifier'] = $ds['id'];
					$CKAN_ds['title'] = $ds['title'];
					$CKAN_ds['description'] = $ds['notes'];

					// $CKAN_ds['distributions'] = $ds['resources'];
					$CKAN_ds['publisher']['name'] = $ds['organization']['title'];

					$CKAN_ds['spatial']['text'] = ($ds['spatial_coverage'] ?? "---");
					$CKAN_ds['temporal']['start']['text'] = ($ds['temporal_coverage_from'] ?? "---");
					$CKAN_ds['temporal']['end']['text'] = ($ds['temporal_coverage_to'] ?? "---");
					$CKAN_ds['accrualPeriodicity']['text'] = ($ds['update_freq'] ?? "---");
					$CKAN_ds['license'] = ($ds['license_title'] ?? "---");

					foreach ($ds['tags'] as $tag) {	
						$CKAN_ds['keywords'][] = $tag['name'];
					}					

					$tmp_resource = [];

					foreach ($ds['resources'] as $resource) {	

						$tmp_resource['downloadURL'] = $resource['url'];
						//$tmp_resource['title'] = ($resource['name'] ?? "---");
						$tmp_resource['format'] = ($resource['format'] ?? "---");

						$CKAN_ds['distributions'][] = $tmp_resource;					
					}
					
					// not reliable, extras element sometimes have harvest_url key, but not always. To see what is included, run and dump json
					// if(isset($ds['extras'])) {
					// 	//$CKAN_ds['landingPage'] = json_encode($ds['extras']);
					// 	foreach ($ds['extras'] as $extra) {
					// 		if($extra['key'] == "harvest_url") {
					// 			$CKAN_ds['landingPage'] = $extra['value'];
					// 		}
					// 	}
					// }

					$CKAN_ds['landingPage'] = $api['portal'] . $ds['name'];

					$result_datasets[] = $CKAN_ds;	// append this CKAN entry to overall results array 
					$count+=1;

				} 
			}
		}

	}	// end processing of CKAN APIs ---------------------------------------------------


	// need to convert all array elements to strings to be able to export to csv
	// $output_file = "all_results.csv";
	// $handle = fopen("$output_file", "w");
	// foreach ($result_datasets as $line) {
	// 	fputcsv($handle, $line);
	// }
	// fclose($handle);

	// construct results table from $result_datasets array


	echo '<div class=""><table class="table table-condensed table-bordered">';
	echo '<tr><thead class="thead-dark">';
	echo '<th>API</th>';
	echo '<th>ID</th>';
	echo '<th>Title</th>';
	echo '<th>Description</th>';
	// echo '<th>Org ID</th>';
	echo '<th>Landing page</th>';
	echo '<th>Date issued</th>';
	echo '<th>Publisher</th>';
	echo '<th>Catalog</th>';
	echo '<th>Keywords</th>';
	echo '<th>Spatial</th>';
	echo '<th>Start</th>';
	echo '<th>End</th>';
	echo '<th>Updates</th>';
	echo '<th>Distributions</th>';
	echo '<th>License/s</th>';
	echo '<th>Quality</th>';
	echo '</tr></thead><tbody>';

	foreach ($result_datasets as $ds) {
		echo "<tr>";
		echo "<td>" . $ds['api'] . "</td>";
		echo "<td>" . $ds['identifier'] . "</td>";
		echo "<td>" . $ds['title'] . '</td>';
		echo "<td>" . strip_tags($ds['description']) . '</td>';
		// echo "<td>" . $ds['publisher']['identifier'] . "</td>";
		echo "<td>";
		if (isset($ds['landingPage'])) {
			echo "<a href='" . $ds['landingPage'] . "'>" . $ds['landingPage'] . "</a>";
		}
		echo "</td>";
		
		//echo "<td>" . $ds['issued'] . isset($ds['issued']) ? date_format(date($ds['issued'],"d/m/y")) : "*" . "</td>";
		echo "<td>" . ($ds['issued'] ?? "---") . "</td>";
		echo "<td>" . ($ds['publisher']['name'] ?? "---") . '</td>';		
		echo "<td>" . ($ds['catalog'] ?? "---") . "</td>";

		// echo "<td><ul>"; // show keywords as list
		// foreach ($ds['keywords'] as $keyword) {
		// 	echo "<li>" . $keyword . "</li>";
		// }
		// echo "</ul></td>";

		echo "<td>"; // show keywords with comma separators
		$kw = array();

		if(isset($ds['keywords'])){
			
			$kw = array();

			foreach ($ds['keywords'] as $keyword) {
				$kw[] = ' <span class="badge badge-info">' . $keyword . "</span>";
			}

			echo implode(", ", $kw);
		}

		echo "</td>";

		echo "<td>" . ($ds['spatial']['text'] ?? "---") . '</td>';
		echo "<td>" . ($ds['temporal']['start']['text'] ?? "---") . '</td>';
		echo "<td>" . ($ds['temporal']['end']['text'] ?? "---") . '</td>';
		echo "<td>" . ($ds['accrualPeriodicity']['text'] ?? "---") . '</td>';
		
		$licences = array();
		$dists = array();
		
		if(isset($ds['distributions'])) {
			if ($spreadsheet_format == "on") {	//	show list of formats only (no subtable so it works in a spreadsheet)

					
				foreach ($ds['distributions'] as $resource) {
					$listResource = true;		// check if non-matching resources are to be filtered out
					if ($formatFilter == true ) {
						if (strtoupper($resource['format']) <> strtoupper($resourceType)){
							$listResource = false;
						}
					} 

					if ($listResource == true) {

						if(isset($resource['downloadURL'])) {
							$resourceTitle = "<a href='" . $resource['downloadURL'] . "'>" . $resource['format'] . "</a>";
						} else {
							$resourceTitle = $resource['format'];
						}

						if (!in_array($resourceTitle, $dists)) {  // add distribution already in the list
							$dists[] = $resourceTitle;
						}

						if(isset($resource['license']['name'])) {
							if (!in_array($resource['license']['name'], $licences)) {  // add resource licence unless already included
								$licences[] = $resource['license']['name'];
							}
						}

					}
				}

				echo "<td>" . implode(", ", $dists) . "</td>";
				echo "<td>";
				if ($licences) {
					echo implode("<br>", $licences) . "<br>";	// list array if licences were attached to individual distributions
				}
				echo ($ds['license'] ?? "");					// licence attached to dataset
				echo "</td>";

			} else {	//show resources as nested table with full details

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
							// if (!array_key_exists('downloadURL', $resource) {
							// 	$resource['downloadURL'] = '';
							// }

							if(isset($resource['downloadURL'])) {
								$resourceTitle = "<a href='" . $resource['downloadURL'] . "'>" . $resource['title'] . "</a>";
							} else {
								$resourceTitle = $resource['title'];
							}
							
							if (!in_array($resource['license']['name'], $licences)) {  // add resource licence unless already included
								$licences[] = $resource['license']['name'];
							}
							echo "<tr><td>" . $resource['format'] . "</td><td>" . $resourceTitle . "</td><td>" . $resource['license']['name'] . "</td></tr>";
						}
					}
				echo "</table></td>";
			}
		} else { 	// distributions was mt

			echo "<td>" . "***" . '</td>';
			echo "<td>" . ($ds['license'] ?? "---") . '</td>';

		}

		echo "<td>" . ($ds['quality'] ?? "---") . '</td>';
		echo "</tr>";

	} 

	echo '</tbody></table></div></body>';
	echo '<script type="text/javascript">
		$(document).ready(function(){
			$("#count").html("Total results found: '. $count .'");
		});
		</script>';


}	// finished fetching results and displaying

?>

</body>
</html>