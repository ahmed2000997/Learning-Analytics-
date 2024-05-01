<?php

include('config.php');

if (isset($_GET['hedares'])) {
    $param_value = $_GET['hedares'];
    processRequest($param_value);
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        $param_value = "-{$days} days";
        processRequest($param_value);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}

function processRequest($param_value) {
    global $url, $ws_key, $ws_secret;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$ws_key}:{$ws_secret}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
        $data = json_decode($response, true);

        // Filter interactions based on the value of 'hedares' or 'last'
        $filtered_data = array_filter($data, function ($entry) use ($param_value) {
            $timestamp = strtotime($entry['timestamp']);
            $compare_timestamp = strtotime($param_value);
            return $timestamp >= $compare_timestamp;
        });

        // Initialize an array to store interactions count for each day
        $interactions_count_per_day = [];

        // Loop through the filtered data to count interactions per day
        foreach ($filtered_data as $item) {
            $timestamp = date('Y-m-d', strtotime($item['timestamp']));

            // Check if the timestamp already exists in the array, if not, initialize it to zero
            if (!isset($interactions_count_per_day[$timestamp])) {
                $interactions_count_per_day[$timestamp] = 0;
            }

            // Increment the interactions count for the corresponding day
            $interactions_count_per_day[$timestamp]++;
        }

        // Convert the interactions count per day array to the desired format
        $formatted_interactions_count_per_day = [];
        foreach ($interactions_count_per_day as $date => $count) {
            $formatted_interactions_count_per_day[] = ['date' => $date, 'interactions' => $count];
        }

        // Convert the formatted interactions count per day array to JSON format
        $json_formatted_interactions_count_per_day = json_encode($formatted_interactions_count_per_day, JSON_PRETTY_PRINT);

        echo $json_formatted_interactions_count_per_day;
    } else {
        echo "Error fetching data from Watershed LRS.";
    }

    curl_close($ch);
}

?>
