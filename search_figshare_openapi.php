<?php

//	---------------------------------------------------------------------------------
//	Open Data query utility for public data from Figshare
//	author:		C Bahlo
//	notes: 		Users set query options in form
//				form self-submits and displays a table of results
//				tried to use searchbyindex endpoint, but could only get one result at a time
//				using searchbylocation with empty location instead
//
//			full doco at: 	https://docs.figshare.com/
//	---------------------------------------------------------------------------------


// construct header
?>
	<html lang="en">
	<head>
    <meta charset="utf-8">
    <meta name="author" content="Chris Bahlo">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Figshare OpenAPI search tool</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>'
	<style>
		.badge-success, .badge-info {color: black;}
	</style>
	</head>

<?php
	if (!isset($_POST['submit'])) { // if page is not submitted, show the form
?>
	<body>
	<div class="container">	
		<h2>Figshare OpenAPI search tool</h2>
		<p>This search is run on the CSIRO OpenAPI.</p>
		<p>Two search methods can be used: Full text search and keyword search.</p> 
		<p>Full text search will yield the largest result set, as it returns all records where the search word found in any field.
			There will probably be a number of false positives.
			The keyword search is more specific.</p>
		<p>&nbsp;</p>
		<hr/>

		<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
			<div class="row">
				<div class="col-md-6 mb-3">
		            <label for="search_string">Search word (full text search)</label>
					<input type="text" class="form-control" id="search_string" name="search_string"><br />
	            </div>

<!-- 				<div class="col-md-6 mb-3">
		            <label for="search_tag">1.b. Search keyword in full text search results</label>
					<input type="text" class="form-control" id="search_tag" name="search_tag"><br />
	            </div> -->
          	</div>
          	<hr/>
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="submit" name="submit">
			<p>&nbsp;</p>
			<p>Note: processing may take a little time depending on the size number of datasets found.</p>
		</form>
	</div>	

<?php
} else {	//run queries and display in a web page

	$filter = "";
	$search_string = rawurlencode($_POST["search_string"]);
	//$search_tag = $_POST["search_tag"];	//	not implemented
	$start_page = 1;

// Form was completed, run query on OpenAPI

	if ($search_string == "") {
		echo "No search term was entered, please return to form.";
		exit;
	}

	// construct results table from $result_datasets array
	echo '<body>';
	echo "<h3>Query run on Figshare API</h3>";
	echo "<h4>Query: " . $search_string. "</h4>";
	echo "<h4>Query: " . $search_string. "</h4>";
	//echo "<h4>Results requested: " . $rows . "</h4>"; // rows are per api, so not really relevant in this context
	echo '<h4 id="count">Working on it ....</h4>';
	echo '<div class=""><table class="table table-condensed table-bordered">';
	echo '<tr><thead class="thead-dark">';
	echo '<th>#</th>';
	echo '<th>API</th>';
	echo '<th>ID</th>';
	echo '<th>Title</th>';
	echo '<th>Description</th>';
	// echo '<th>Org ID</th>';
	//echo '<th>DOI</th>';
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
	
	$count = 0;	// for number of datasets found
	
	do {
		
		// kw search doesn't work with searchbylocation endpoint
		//$filter = "?q=" . $search_string; // if we use the searchbyindex endpoint, but this only returns one result at a time.
		$filter = "?q=" . $search_string . '&searchType=INTERSECTION&p=' . $start_page . '&rpp=100&soud=off&sb=TITLE&showFacets=false&atom=false';
		$url = 'https://data.csiro.au/dap/ws/v2/collections/searchbylocation' . $filter;
		echo ("<p>url queried: " . $url . "</p>");

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json'
			),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  //echo $response;	// for testing
		}

		$data = json_decode($response, true);
		//var_dump($data);

		if ($data['totalResults'] == 0) {
			$start_page = 0;
			echo ("no results");
			break;
		}

		$datasets = $data['dataCollections'];

		foreach ($datasets as $ds) {
			$count +=1;
			echo "<tr>";
			echo "<td>" . $count . "</td>";
			echo "<td>CSIRO OpenAPI</td>";
			if ($ds['doi'] <> ""){
				echo "<td>https://doi.org/" . $ds['doi'] . "</td>";
			} else {
				echo "<td>" . $ds['id']['identifier'] . "</td>";
			}
			echo "<td>" . $ds['title'] . '</td>';
			echo "<td>" . strip_tags($ds['description']) . "</td>";
			echo "<td><a href='" . $ds['landingPage']['href'] . "'>" . $ds['landingPage']['href'] . "</a></td>";
			//echo "<td>" . $ds['published'] . date_format(date($ds['published'],"d/m/y")) . "</td>";
			echo "<td>" . $ds['published'] . $ds['published'] . "</td>";
			echo "<td>CSIRO</td>";		
			echo "<td></td>";	// catalog
			echo "<td>" . ($ds['keywords'] ?? "---") . "</td>";
			echo "<td>";	
			$coords = array();
			foreach ($ds['spatialParameters'] as $sParm) {
				$coords[] = $sParm;
			}
			echo implode(", ", $coords) . "</td>";
			
			//echo "<td>" . $ds['spatialParameters'][2] . ", " . $ds['spatialParameters'][3] . ", " . $ds['spatialParameters'][4] . ", " . $ds['spatialParameters'][5]. ", " . $ds['spatialParameters'][1] . '</td>';
			echo "<td>" . ($ds['dataStartDate'] ?? "---") . "</td>";
			echo "<td>" . ($ds['dataEndDate'] ?? "---") . "</td>";
			echo "<td></td>"; //updates
			
			echo "<td>" . $ds['collectionType'] . "</td>";

			echo "<td>" . $ds['licence'] . "</td>";
			echo "<td></td>";
			echo "</tr>";
		}

		// the APi allows a max of 100 datasets to be returned at at time (rpp=100 - see request). If we find 100, chances are there are more.
		// API has pagination facility, use by setting p= to fetch the subsequent pages. This is why the entire request operation is in a loop.
		// Every 100 records found, increment the page, which means the loop will run one more time. if we get to the end of the fetched set 
		// and there are less than 100 results, we have the all, so reset start_page to zero to exit the do loop.
		if (($count % 100) == 0 ){
			$start_page+=1;
			//error_log("count = " . $count . " *** mod 100 = 0, increment page");
		} else {
			$start_page = 0;
			//error_log("mod 100 <> 0, last page");
		}

	} while ($start_page > 0);

	echo '</tbody></table></div></body>';
	echo '<script type="text/javascript">
		$(document).ready(function(){
			$("#count").html("Total results found: '. $count .'");	// updates results found in heading
		});
		</script>';

}	// finished fetching results and displaying

?>

</body>
</html>