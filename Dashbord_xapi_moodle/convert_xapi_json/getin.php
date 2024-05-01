<?php

include('config.php');

if (isset($_GET['hedares'])) {
    $param_value = $_GET['hedares'];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$ws_key}:{$ws_secret}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
        $data = json_decode($response, true);

        // Convert param_value to timestamp for comparison
        $param_timestamp = strtotime($param_value);

        // Filter interactions based on the value of 'hedares'
        $filtered_data = array_filter($data, function ($entry) use ($param_timestamp) {
            $timestamp = strtotime($entry['timestamp']);
            return $timestamp >= $param_timestamp;
        });

        // Encode the filtered data in a pretty format without numeric index
        $output = json_encode(array_values($filtered_data), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
    } else {
        echo "error";
        exit();
    }

    curl_close($ch);

} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);

    if ($days > 0) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$ws_key}:{$ws_secret}");
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            $data = json_decode($response, true);

            // Calculate the timestamp of N days ago
            $timestamp = strtotime("-{$days} days");

            // Filter interactions based on the last N days
            $filtered_data = array_filter($data, function ($entry) use ($timestamp) {
                $entry_timestamp = strtotime($entry['timestamp']);
                return $entry_timestamp >= $timestamp;
            });

            // Encode the filtered data in a pretty format without numeric index
            $output = json_encode(array_values($filtered_data), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
        } else {
            echo "error";
            exit();
        }

        curl_close($ch);
    } else {
        echo "Parameter 'last' must be a positive integer.";
        exit();
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
    exit();
}

// Process the filtered data to get the interaction counts per student
$data = json_decode($output, true);
$result = [];

foreach ($data as $item) {
    $name = isset($item['actor']['name']) ? $item['actor']['name'] : (isset($item['actor']['account']['name']) ? $item['actor']['account']['name'] : '');
    if (!isset($result[$name])) {
        $result[$name] = 0;
    }
    $result[$name]++;
}

// Sort the result array in descending order
arsort($result);

// Convert the result array to Grafana Table format
$grafana_table = [];
foreach ($result as $name => $count) {
    $grafana_table[] = [$name, $count];
}

// Convert the Grafana Table array to JSON format
$json_result = json_encode($grafana_table, JSON_PRETTY_PRINT);
echo $json_result;

?>
