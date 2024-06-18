<?php

//	---------------------------------------------------------------------------------
//	Open Data query utility for public data from:
//				- data.gov.au Magda API
//				- CSIRO Magda API
//				- CKAN APIs as per ckan_apis.json/ckan_apis_local.json
//	author:		C Bahlo
//	notes: 		User sets query options in form
//				form self-submits and displays a table of results, duplicates are noted
//				query phrase is used to run a full text search on repos
//				keyword phrase is used to filter full text search results by checking if keyword exists
//
//	---------------------------------------------------------------------------------

$rows = 1000; 	// consider paging?

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
	<title>Data catalogues search tool</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
	<style>
		.bg-success, .bg-info, .bg-primary {color: black;}*/
		.w-10 {width:10px;}
		.w-30 {width:20px;}
		.w-30 {width:30px;}
		.w-40 {width:40px;}
		.w-50 {width:50px;}
		.w-60 {width:60px;}
		.w-70 {width:70px;}
		.w-80 {width:80px;}
		.w-90 {width:90px;}
		.w-100 {width:100px;}
		.w-150 {width:150px;}
		.w-200 {width:200px;}
		.w-250 {width:250px;}
		td {margin: 2px;}
	</style>
	</head>


<?php
if (!isset($_POST['submit'])) { // if page is not submitted, show the form
?>
	<body>
	<div class="container">
		<h2 class="my-4">Data Catalogue Search Tool</h2>
		<p>This search is run on the Magda API of data.gov.au, the CSIRO Knowledge Network and the CKAN API instances specified in the file <em><?= $ckan_json_file ?></em></em>.</p>
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
				<input type="checkbox" class="form-check-input" id="spreadsheet_format" name="spreadsheet_format" checked><br />
	            <label class="form-check-label" for="spreadsheet_format">Format abbreviated output for spreadsheet</label>
	        </div>

          	<hr/>
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="Submit" name="submit">
			<p>&nbsp;</p>
			<p>Note: processing may take a little time depending on the size number of datasets found. Page will refresh when completed.</p>
		</form>
	</div>

<?php
} else {	//run queries and display in a web page

	$spreadsheet_format = ($_POST["spreadsheet_format"] ?? "off");

	$filter = "?limit=" . $rows;	// construct filter

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
	echo '<h4 class="text-3xl font-bold">Query: ' . $search_string. ($_POST["search_tag"] ?? " | filtered for tag: " . $search_tag) . "</h4>";
	echo '<h4 class="text-3xl font-bold" id="count">Working on it ....</h4>';

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

	echo sizeof($datasets) . " results from data.gov.au" . "<br>";

	// variables for overall result set from all queries
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
			$ds['api'] = '<span class="badge bg-success">Magda</span>';
			$result_datasets[] = $ds;	// append this to results array so we know origin is data.gov.au when we query CKAN APIs later
			$count+=1;
		}
	}

// next query CSIRO Knowledge Network

	$url = "https://knowledgenet.co/api/v0/search/datasets" . $filter;
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
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_SSL_VERIFYHOST => false
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		 //echo response;	// for testing
	}

	$data = json_decode($response, true);
	$datasets_csiro = $data['dataSets'];

	echo sizeof($datasets_csiro) . " results from KN" . "<br>";

	foreach ($datasets_csiro as $ds) {
//		echo $ds['title'] . "<br>";
//		echo "<hr>";

		$has_tag = false;

		if ($use_tag_filter) {
			foreach ($ds['keywords'] as $tag) {
				if (strpos(strtolower($tag), strtolower($search_tag)) !== false) {
					$has_tag = true;	// have found case-insensitive match (including part of word)
					break;
				}
			}
		}

		// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
		if (!$use_tag_filter || $has_tag) {
			// if this dataset is a duplicate of one already found (by id partial match on id field), then update row, otherwise add this ds to the list
			$add_current_ds = true;
			$rds_idx = 0;

			foreach ($result_datasets as $already_found) {
				//echo strpos($already_found['identifier'], $ds['id']) . " -- <br>";

				if (str_contains($already_found['identifier'], $ds['identifier'])) { // if the id of the curr CKAN ds is the same as one already in the list, don't add again.
					$result_datasets[$rds_idx]['api'] = $result_datasets[$rds_idx]['api'] . ' <span class="badge bg-info">CSIRO KN</span>';
					$add_current_ds = false;
					break;
				}

				$rds_idx += 1;
			}

			if ($add_current_ds) {    // there is no entry for this dataset in magda (by id), so add it to the results array

				$ds['api'] = '<span class="badge bg-info">CSIRO KN</span>';    // set api name

				$result_datasets[] = $ds;    // append this KN entry to overall results array
				$count += 1;
			}
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

		echo sizeof($datasets) . " results from ". $api['name'] . "<br>";

		foreach ($datasets as $ds) {

			$has_tag = false;

			if ($use_tag_filter) {
				foreach ($ds['tags'] as $tag) {
					if (strpos(strtolower($tag['name']), strtolower($search_tag)) !== false) {
					    $has_tag = true;	// have found case-insensitive match (including part of word)
					    break;
					}
				}
			}

			// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
			if (!$use_tag_filter || $has_tag) {
				// if this dataset is a duplicate of one already found (by id partial match on id field), then update row, otherwise add this ds to the list
				$add_current_ds = true;
				$rds_idx = 0;

				foreach ($result_datasets as $already_found) {

					//echo strpos($already_found['identifier'], $ds['id']) . " -- <br>";

					if (str_contains($already_found['identifier'], $ds['id'])) { // if the id of the curr CKAN ds is the same as one already in the list, don't add again.
						//echo $result_datasets[$rds_idx]['api'] . " .. + " . '<span class="badge bg-warning">' . $api["name"] .'</span>' . "<br>";
						$result_datasets[$rds_idx]['api'] = $result_datasets[$rds_idx]['api'] . ' <span class="badge bg-primary">' . $api["name"] .'</span>';
						$add_current_ds = false;
						break;
					}

					$rds_idx +=1;
				}

				if ($add_current_ds) {	// there is no entry for this dataset in magda (by id), so add it to the results array

					$CKAN_ds = [];	// mt array to assemble dataset detail for current CKAN dataset, so we can append that to total results array

					$CKAN_ds['api'] = '<span class="badge bg-primary">' . $api["name"] .'</span>';	// mark ckan api name
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


	echo '<div class=""><table id="search_results_table" class="table-fixed align-text-top">';
	echo '<thead class="bg-sky-500"><tr class="border-bottom border-info">';
	echo '<th class="w-20">API</th>';
	echo '<th class="w-40">ID</th>';
	echo '<th class="w-100">Title</th>';
	echo '<th class="w-250">Description</th>';
	// echo '<th>Org ID</th>';
	echo '<th class="w-100">Landing page</th>';
	echo '<th class="w-60">Date issued</th>';
	echo '<th class="w-60">Publisher</th>';
	echo '<th class="w-60">Catalog</th>';
	echo '<th class="w-100">Keywords</th>';
	echo '<th class="w-800">Spatial</th>';
	echo '<th class="w-50">Start/End</th>';
	echo '<th class="w-40">Updates</th>';
	echo '<th class="w-100">Distributions</th>';
	echo '<th class="w-100">License/s</th>';
	// echo '<th>Quality</th>';
	echo '</tr></thead><tbody>';

	foreach ($result_datasets as $ds) {
		echo '<tr class="border-bottom border-info">';
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
			echo implode("<br>", $ds['keywords']);

//			$kw = array();
//
//			foreach ($ds['keywords'] as $keyword) {
//				$kw[] = ' <span class="badge bg-info">' . $keyword . "</span>";
//			}
//
//			echo implode("<br>", $kw);
		}


		echo "</td>";

		echo "<td>" . ($ds['spatial']['text'] ?? "---") . '</td>';
		echo "<td>Start: " . ($ds['temporal']['start']['text'] ?? "---") . "<br>End: " . ($ds['temporal']['end']['text'] ?? "---") . '</td>';
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

		//echo "<td>" . ($ds['quality'] ?? "---") . '</td>';
		echo "</tr>";

	}

	echo '</tbody></table></div></body>';
	echo '<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js" crossorigin="anonymous"></script>';
	echo '<script type="text/javascript">
		$(document).ready(function(){
			$("#count").html("Total results found: '. $count .'");
			let table = new DataTable("search_results_table");
		});
		</script>';


}	// finished fetching results and displaying

?>

</body>
</html>
