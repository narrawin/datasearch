<?php

//	---------------------------------------------------------------------------------
//	Json extraction utility for soil and ag data from Magda API
//	author:		C Bahlo
//	notes: 		calls dat.gov.au magda API
//				set options in form		
//				form self-submits and displays a table of results
//	
//	
//	---------------------------------------------------------------------------------

$rows = $_POST["rows"];
$search_string = rawurlencode($_POST["search_string"]);
$search_resource_type = $_POST["search_resource_type"];
$search_tag = $_POST["search_tag"];

$resource_options = array("","wms","wfs","csv","json","tiff","xml","geojson","html","arcgis","esri","kml","pdf");

if (!isset($_POST['submit'])) { // if page is not submitted to itself echo the form

?>
	<html lang="en">
	<head>
    <meta charset="utf-8">
    <meta name="author" content="Chris Bahlo">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Magda API search</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>'
	</head>
	<body>
	<div class="container">	
		<h2>Magda API Search Tool</h2>
		<p>Two search methods can be used: Full text search and full text with subsequent keyword filter.</p> 
		<p>Full text search will yield the largest result set, as it returns all records where the search word found in any field.
			There will probably be a number of false positives.
			Adding a keyword filter to the full text search perform a case-insensitive search including partial matches in the keyword field.
			This is suggested to reduce the number of false positives. This must be a single word.</p>
		<p>&nbsp;</p>

		<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="search_resource_type">Select a resource format</label>
					<select name="search_resource_type" id="search_resource_type" class="custom-select d-block w-100">
					
					<?php	// construct dropdown with options for resource names
						foreach($resource_options as $ro) {
						    echo '<option value="'. $ro .'">'. $ro .'</option>';
						}
					?>
					</select>
				</div>
				<div class="col-md-6 mb-3">
					<label for="rows">Rows to get (max 1000)</label>
					<input type="text" class="form-control" id="rows" name="rows" value="300"><br />
	            </div>
			</div>
			<hr/>
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
          	<hr/>
			<hr class="mb-4">
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="submit" name="submit">

		</form>
	</div>	

	<?php
} else {	//run script for selected API

	// construct filter to pass to API
	$filter = "?limit=" . $rows;

	if ($search_string <> "") {
		$filter .= "&query=" . $search_string;
	} 
	if ($search_resource_type <> "") {
		$filter .= "&format=" . $search_resource_type;
	} 

	// call API
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
	  CURLOPT_CUSTOMREQUEST => "GET"
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

	if (strlen($search_tag)) {
		$use_results_tag_filter = true;
	} else {
		$use_results_tag_filter = false;
	}

	echo '<html lang="en">';
	echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="author" content="Chris Bahlo">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
	echo '<title>data.gov.au Magda API query</title>';
	echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
	echo '<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>';
	echo '</head>';
	echo '<body>';


	echo "<h3>API call: " . $url . "</h3>";
	echo "<h4>Filtered for tag: " . $search_tag . "</h4>";
	echo "<h4>Results requested: " . $rows . "</h4>";
	echo '<h4 id="count">Working on it ....</h4>';
	echo '<div class=""><table class="table table-condensed table-bordered">';
	echo '<tr><thead class="thead-dark">';
	// echo '<th>#</th>';
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
	echo '</tr></thead><tbody>';

	foreach ($datasets as $ds) {

		$has_tag = false;

		if ($use_results_tag_filter == true) {
			foreach ($ds['keywords'] as $tag) {
				if (strpos(strtolower($tag), strtolower($search_tag)) !== false) {
				    $has_tag = true;	// have found case-insensitive match (including part of word)
				    break;
				}
			}
		}

		// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
		if ($use_results_tag_filter == false || $has_tag == true) {

			$count+=1;
			echo "<tr>";
			// echo "<td>" . $count . '</td>';
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

	echo '</tbody></table></div></body>';
	echo '<script type="text/javascript">
		$(document).ready(function(){
			$("#count").html("Total results found: '. $count .'");
		});
		</script>';
	echo '</html>';
	}
?>