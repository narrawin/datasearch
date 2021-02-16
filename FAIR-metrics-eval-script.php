<?php

//	---------------------------------------------------------------------------------
//	FAIR evaluation script using the FAIRMetrics API
//	author:		C Bahlo
//	inputs:		- csv file which contains a list of urls pointing to public datasets
//				- valid ORCID ID 
//				- test name
//				- FAIRMetrics collection id - defaults to 15
//				  Details here: https://fairsharing.github.io/FAIR-Evaluator-FrontEnd/#!/collections/15
//	
//	notes: 		form self-submits and displays a table of inputs and outputs, inputs are not checked!	
//	
//	NOTE:		Test inputs and outputs are shown publically at 
//				https://fairsharing.github.io/FAIR-Evaluator-FrontEnd/#!evaluations
//	To Do:		remove defaults from input fields
//				input checking
//				make result table indiv.test headings read from file
//	---------------------------------------------------------------------------------


//ini_set('max_execution_time', '600'); //300 seconds = 5 minutes
ini_set('max_execution_time', '0'); //infinite

// construct header
?>
	<html lang="en">
	<head>
    <meta charset="utf-8">
    <meta name="author" content="Chris Bahlo">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>FAIR evaluation script</title>
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
		<h2>FAIR evaluation script</h2>
		<p></p>
		<p>Based on the FAIRMetrics evaluator API described at 
			<a href="https://github.com/FAIRMetrics/Metrics/tree/master/MetricsEvaluatorCode/Ruby/fairmetrics#createnewevaluation">The FAIR Evaluator</a>
		</p>
		<p>  
			<a href="https://fairsharing.github.io/FAIR-Evaluator-FrontEnd/#!/collections">Available FAIRMetrics collections</a></br>
			Note that this tool defaults to 15, which is a set of 18 tests set up for Ag datasets.
		</p>		
		
		<p>&nbsp;</p>
		<hr/>

		<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
			<div class="row">
				<div class="col-md-6 mb-3">
		            <label for="csv">csv file to use</label>
					<input type="text" class="form-control" id="csv" name="csv" value="datasets-to-check.csv"><br />
	            </div>
				<div class="col-md-6 mb-3">
		            <label for="orcid">ORCID ID</label>
					<input type="text" class="form-control" id="orcid" name="orcid" value="0000-0003-4185-5542"><br />
	            </div>
	        </div>   
			<div class="row">
				<div class="col-md-6 mb-3">
		            <label for="collection">FAIR Indicators collection Number</label>
					<input type="text" class="form-control" id="collection" name="collection" value="15"><br />
	            </div>
				<div class="col-md-6 mb-3">
		            <label for="test">Test name</label>
					<input type="text" class="form-control" id="test" name="test" value="Ag data FAIR test"><br />
	            </div>
	        </div> 	         
			<input class="btn btn-warning btn-lg btn-bloc" type="submit" value="submit" name="submit">
			<p>&nbsp;</p>
			<p>Note: processing will take a time due to the speed of the FAIRMetrics API.</p>
		</form>
	</div>	

<?php
} else {	//form was submitted - run evaluations and display on page

	$collection = $_POST["collection"];
	$csv = $_POST["csv"];
	$test = $_POST["test"];
	$orcid = $_POST["orcid"];

	$datasets = [];

	if (($handle = fopen($csv, "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	        $datasets[] = $data;
	    }
	    fclose($handle);
	}

	//var_dump($datasets);

	echo '<body>';
	echo "<h2>Evaluation results</h2>";
	echo "<h4>Checking datasets against FAIRMetrics collection ".$collection." for ORCID ID ".$orcid."</h4>";
	echo "<h4>Runnning ". count($datasets) ." evaluation as per source file: ". $csv ."</h4>";
	echo '<div class=""><table class="table table-condensed table-bordered">';
	echo '<th>ID</th>';
	echo '<th>Dataset tested</th>';
	echo '<th>Input</th>';
	echo '<th>Metrics 1</th>';
	echo '<th>Metrics 2</th>';
	echo '<th>Metrics 3</th>';
	echo '<th>Metrics 4</th>';
	echo '<th>Metrics 5</th>';
	echo '<th>Metrics 6</th>';
	echo '<th>Metrics 7</th>';
	echo '<th>Metrics 8</th>';
	echo '<th>Metrics 9</th>';
	echo '<th>Metrics 10</th>';
	echo '<th>Metrics 11</th>';
	echo '<th>Metrics 12</th>';
	echo '<th>Metrics 13</th>';
	echo '<th>Metrics 14</th>';
	echo '<th>Metrics 16</th>';
	echo '<th>Metrics 18</th>';
	echo '<th>Metrics 20</th>';
	echo '<th>Metrics 22</th>';
	echo '</tr></thead><tbody>';


	foreach ($datasets as $ds) {	// for each row in the source csv file, run an evaluation

		$url = "https://w3id.org/FAIR_Evaluator/collections/" . $collection . "/evaluate.json";
		$post_fields = "{\r\n    \"resource\": \"".$ds[0]."\",\r\n    \"executor\": \"".$orcid."\",\r\n    \"title\": \"".$test."\"\r\n}";

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $post_fields,
		  CURLOPT_HTTPHEADER => array(
		    "Content-Type: application/json"
		  ),
		));

		$response = curl_exec($curl);

		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  //echo $response;	// for testing
		}

		$evaluation = json_decode($response, true);
		$evaluation_result = json_decode($evaluation['evaluationResult'], true);	// the result is a json string, so need to decode again
		
		//var_dump($evaluation_result);		
		
		$numtests = count($evaluation_result); 	// this depends on how many tests in the selected collection
		//echo "number of tests: ".$numtests;
		
		$keys = array_keys($evaluation_result);	// need this to read they keys in the arrays in the loop below, because the test names vary

		echo "<tr>";
		echo "<td>" . $evaluation['@id'] . "</td>";
		echo "<td>" . $evaluation['primaryTopic'] . "</td>";
		echo "<td>" . $evaluation['evaluationInput'] . '</td>';
		//echo "<td>" . $evaluation_result['evaluationResult'] . '</td>';

		for ($i=0; $i < $numtests; $i++) {

			// for each of the tests within the collection, get the result value
			$result = "failure";
			if ($evaluation_result[$keys[$i]][0]['http://semanticscience.org/resource/SIO_000300'][0]['@value'] == 1){
				$result = "success";
			}
			
			$result = $result;	// show as success/failure rather than  1 or 0
			//$result = $evaluation_result[$keys[$i]][0]['http://semanticscience.org/resource/SIO_000300'][0]['@value'];

			echo "<td>" . $result . "</td>";
		}
		echo "</tr>";

	}

	echo '</tbody></table></div></body>';
	
}	// finished fetching results and displaying

	$time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    echo "<h4>Process Time: {$time}</h4>";

?>

</body>
</html>