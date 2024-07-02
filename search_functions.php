<?php

function send_csv($array): int {
    $csv_output = fopen('php://output', 'w');
    $status = fputcsv($csv_output, $array);
    fclose($csv_output);
    return $status;

}


function display_results($result_datasets, $results_found)
{
    echo '<div class="border-top border-red mt-10"><table id="search_results_table" class="table table-hover table-sm">';
    echo '<thead class="table-dark"><tr class="border-bottom border-info">';
    echo '<th class="w-20">API</th>';
    echo '<th class="w-100">Title</th>';
    echo '<th class="w-250">Description</th>';
    echo '<th class="w-100">Keywords</th>';
    echo '<th class="w-60">Date issued</th>';
    echo '<th class="w-60">Publisher</th>';
    echo '<th class="w-100">Landing page</th>';
    echo '<th class="w-100">License/s</th>';
    echo '<th class="w-100">Distributions</th>';
    echo '<th class="w-60">Catalog</th>';
    echo '<th class="w-50">Start/End</th>';
    echo '<th class="w-40">Updates</th>';
    echo '<th class="w-800">Spatial</th>';
    echo '<th class="w-40">ID</th>';
    // echo '<th>Quality</th>';
    echo '</tr></thead><tbody>';

    foreach ($result_datasets as $ds) {
        echo '<tr class="border-bottom border-info">';
        echo "<td>" . $ds['api'] . "</td>";
        echo "<td>" . $ds['title'] . '</td>';
        echo "<td>" . strip_tags($ds['description']) . '</td>';

        echo "<td>"; // show keywords one per line
        if (isset($ds['keywords'])) {
            echo implode("<br>", $ds['keywords']);
        }
        echo "</td>";

        //echo "<td>" . $ds['issued'] . isset($ds['issued']) ? date_format(date($ds['issued'],"d/m/y")) : "*" . "</td>";
        echo "<td>" . ($ds['issued'] ?? "---") . "</td>";
        echo "<td>" . ($ds['publisher']['name'] ?? "---") . '</td>';

        echo "<td>";
        if (isset($ds['landingPage'])) {
            echo "<a href='" . $ds['landingPage'] . "' class='btn btn-dark btn-sm' target='_blank'>Link</a>";
        }
        echo "</td>";

        $licences = array();
        $dists = array();

        if (isset($ds['distributions'])) {

            foreach ($ds['distributions'] as $resource) {

                if (isset($resource['downloadURL'])) {
                    $resourceTitle = "<a href='" . $resource['downloadURL'] . "'>" . $resource['format'] . "</a>";
                } else {
                    $resourceTitle = $resource['format'];
                }

                if (!in_array($resourceTitle, $dists)) {  // add distribution already in the list
                    $dists[] = $resourceTitle;
                }

                if (isset($resource['license']['name'])) {
                    if (!in_array(
                        $resource['license']['name'],
                        $licences
                    )) {  // add resource licence unless already included
                        $licences[] = $resource['license']['name'];
                    }
                }
            }

            echo "<td>";
            if ($licences) {
                echo implode(
                        "<br>",
                        $licences
                    ) . "<br>";    // list array if licences were attached to individual distributions
            }
            echo($ds['license'] ?? "");    // licence attached to dataset
            echo "</td>";

            echo "<td>" . implode("<br>", $dists) . "</td>";

        } else {    // distributions was mt

            echo "<td>" . "***" . '</td>';
            echo "<td>" . ($ds['license'] ?? "---") . '</td>';
        }

        echo "<td>" . ($ds['catalog'] ?? "---") . "</td>";
        echo "<td>Start: " . ($ds['temporal']['start']['text'] ?? "---") . "<br>End: " . ($ds['temporal']['end']['text'] ?? "---") . '</td>';
        echo "<td>" . ($ds['accrualPeriodicity']['text'] ?? "---") . '</td>';
        echo "<td>" . ($ds['spatial']['text'] ?? "---") . '</td>';
        echo "<td>" . $ds['identifier'] . "</td>";


        echo "</tr>";
    }

    echo '</tbody></table></div>';
    echo '<script type="text/javascript">
            $(document).ready(function(){
                $("#count").html("Total results found: ' . $results_found . '");
            });
            </script>';

    echo '</body></html>';

}

?>
