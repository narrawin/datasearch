<?php

//	---------------------------------------------------------------------------------
//	Json extraction utility for soil and ag data from all CKAN APIs (refer json below) 
//	author:		C Bahlo
//	notes: 		most APIs don't have an api key (except VIC) 
//				set your VIC api key in curl header options below: "apikey: XXX",
//				set search options in form		
//				form self-submits and displays a table of results
//				requires ckan_apis.json file in current dir
//	
//	---------------------------------------------------------------------------------

$rows = 1000; // is max allowed by most APIs

// get user input
$search_string = $_POST["search_string"];
$search_api_tags = $_POST["search_api_tags"];
$search_result_tag = $_POST["search_result_tag"];

// get API details
$json = file_get_contents("ckan_apis.json");
$api_data = json_decode($json, true);
$apis = $api_data['APIs'];

if (!isset($_POST['submit'])) { // if page is not submitted to itself - echo the form

?>
	<html lang="en">
	<head>
    <meta charset="utf-8">
    <meta name="author" content="Chris Bahlo">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Select CKAN API to query</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	</head>
	<body>
	<div class="container">	
		<h2>CKAN API Search Tool - search all catalogues</h2>
		<p>Three search methods can be used: Full text search, full text with subsequent keyword filter and direct keyword search
			Use a full text search (option 1.a), with or without additional keyword filter on that result set (option 1.b.),
			OR use option 2. to run the direct keyword search.</p> 
		<p>Full text search will yield the largest result set, as it returns all records where the search word found in any field.
			There will probably be a number of false positives.
			Adding a keyword filter to the full text search perform a case-insensitive search including partial matches in the keyword field.
			This is suggested to reduce the number of false positives. This must be a single word.</p>
		<p>It is also possible to run a keyword search directly on the API. This keyword search is case sensitive and only returns literal matches.
			Partial matches are ignored. You may use multiple keywords or key phrases. This is the most restrictive of the three search methods.</p>
		<p>&nbsp;</p>

		<form method="post" action="<?php echo $PHP_SELF;?>">
			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="api_url">The following CKAN APIs will be searched:</label>
					<ul>
					<?php	// construct list with api names
						foreach($apis as $api) {
						    echo '<li>'. $api['url'].'</li>';
						}
					?>
					</ul>
				</div>
			</div>
			<hr/>
			<div class="row">
				<div class="col-md-6 mb-3">
		            <label for="search_string">1.a. Search word (full text search)</label>
					<input type="text" class="form-control" id="search_string" name="search_string"><br />
	            </div>
				<div class="col-md-6 mb-3">
		            <label for="search_result_tag">1.b. Search keyword in full text search results</label>
					<input type="text" class="form-control" id="search_result_tag" name="search_result_tag"><br />
	            </div>
          	</div>
          	<hr/>
			<div class="row">
				<div class="col-md-6 mb-3">          
					<label for="search_api_tags">2. Search keyword(s) - API</label>
					<input type="text" class="form-control" id="search_api_tags" name="search_api_tags"><br />
	            </div>				
	            <div class="col-md-6 mb-3">          
					<p>Instead of a full text search, specify a single keyword or key phrase (surrounded by double quotes). 
						It is also possible to use multiple keywords or key phrases in the form: keyword1 OR keyword2 OR "key phrase". 
						This keyword search is case sensitive and literal and queries the API directly.</p>
	            </div>
          	</div>
			<hr class="mb-4">
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="submit" name="submit">
		</form>
	</div>	

<?php
} else {	//run script for all APIs
	
	$count = 0;
	$dataset_names = [];
	$duplicates = 0;

	// construct page and table header	
	echo '<html lang="en">';
	echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="author" content="Chris Bahlo">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
	echo '<title>CKAN API query results</title>';
	echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
	echo '<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>';
	echo '</head>';
	echo '<body>';
	echo '<h3>Combined search results found in all CKAN APIs</h3>';
	echo '<h4 id="count">Working on it ....</h4>';
	// make table with fields as required
	echo '<div class=""><table class="table table-condensed table-bordered">';
	echo '<tr><thead class="thead-dark">';
	echo '<th>API</th>';
	echo '<th>ID</th>';
	echo '<th>Org ID</th>';
	echo '<th>Name</th>';
	//echo '<th>Landing page</th>';
	echo '<th>Title</th>';
	echo '<th>Resources:<br>type, link, created</th>';
	echo '<th>License</th>';
	echo '<th>Organisation</th>';
	echo '<th>Description</th>';
	echo '<th>tags</th>';
	echo '<th>Spatial</th>';
	echo '<th>Start</th>';
	echo '<th>End</th>';
	echo '<th>Updates</th>';
	echo '</tr></thead><tbody>';

	// construct filter to pass to API
	$filter = "?rows=" . $rows;

	if ($search_string <> "") {
		$filter .= "&q=" . $search_string;
	} 
	if ($search_api_tags <> "") {
		$filter .= "&q=tags:(" . rawurlencode($search_api_tags) . ")";
	} 

	foreach($apis as $api) {
	    $api_url = $api['url'];

		// call API
		$url = $api_url . $filter;
		$curl = curl_init();

		// if vic ckan api, need a key, and pass in header
		if (strpos($api_url, 'vic.gov.au') !== false) {
			$api_key = "apikey: " . $api['api_key'];

			curl_setopt_array($curl, array(
			  CURLOPT_PORT => "443",
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
			    "Host: api.vic.gov.au:443",
			    $api_key,
			    "cache-control: no-cache"
			  ),
			));
		} else {	// all othe CKAN apis don't need a key
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $url,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "GET"
			));
		}

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  // echo $response;	// for testing
		}

		$data = json_decode($response, true);
		$datasets = ($data['result']['results']);

		if (strlen($search_result_tag)) {
			$use_results_tag_filter = true;
		} else {
			$use_results_tag_filter = false;
		}

		foreach ($datasets as $ds) {

			$has_tag = false;

			if ($use_results_tag_filter == true) {
				foreach ($ds['tags'] as $tag) {
					if (strpos(strtolower($tag['name']), strtolower($search_result_tag)) !== false) {
					    $has_tag = true;	// have found case-insensitive match (including part of word)
					    break;
					}
				}
			}

			// if no keyword filter was specified OR if a kw filter was specified, and the keyword was present, then list current dataset
			if ($use_results_tag_filter == false || $has_tag == true) {
				
				// if this dataset is a duplicate of one already found (by name), then mark row and increase duplicate ctr. 
				if (in_array($ds['name'], $dataset_names)) {
 				   echo "<tr style='background-color:sandybrown;'>";
 				   $duplicates+=1;
				} else {
					echo "<tr>";
				}			
				echo "<td>" . $api['name'] . "</td>";
				echo "<td>" . $ds['id'] . "</td>";
				echo "<td>" . $ds['organization']['id'] . "</td>";
				echo "<td>" . $ds['name'] . "</td>";
				//echo "<td>" . $ds['extras'][1]['value'] . "</td>"; // landing page
				echo "<td>" . $ds['title'] . '</td>';
				
				echo "<td><table>"; // sub-table for resources
				foreach ($ds['resources'] as $resource) {
					// if resource has url, link the title
					if($resource['url'] <> '') {
						$resourceTitle = "<a href='" . $resource['url'] . "'>" . $resource['name'] . "</a>";
					} else {
						$resourceTitle = $resource['name'];
					}
					echo "<tr>";
					echo "<td>" . $resource['format'] . "</td><td>" . $resourceTitle . "</td><td>" . $resource['created']. "</td>";
					echo "</tr>";	
				}
				echo "</table></td>";
				
				echo "<td>" . $ds['license_url'] . '</td>';
				echo "<td>" . $ds['organization']['title'] . '</td>';
				echo "<td>" . $ds['notes'] . '</td>';
				echo "<td>"; 
				foreach ($ds['tags'] as $tag) {		// list all tags and apply formatting
					if (strpos(strtolower($tag['name']), strtolower($search_result_tag)) !== false) {
					    echo "<span class='badge badge-danger'>" . $tag['name'] . "</span><br>";	// highlight tag search
					} else {
						echo "<span class='badge badge-info'>" . $tag['name'] . "</span><br>";
					}
				}				
				// not: could do similar for API tag searches, but consider that several tags may be specified, so would need to parse the input
				// and then compare each tag name to each specified search tag. Considering the small number of hits from thes searchesn, not worth it.
				echo "</td>";
				echo "<td>" . $ds['spatial_coverage'] . '</td>';
				echo "<td>" . $ds['temporal_coverage_from'] . '</td>';
				echo "<td>" . $ds['temporal_coverage_to'] . '</td>';
				echo "<td>" . $ds['update_freq'] . '</td>';
				echo "</tr>";

				$count+=1;
				$dataset_names[] = $ds['name']; // add name to array for later comparison
			}
		}
	}
	echo '</tbody></table></div></body>';
	echo '<script type="text/javascript">
		$(document).ready(function(){
			$("#count").html("Total results found: '. $count .' (includes ' . $duplicates . ' highlighted duplicates)");
		});
		</script>';
	echo '</html>';
}	

?>