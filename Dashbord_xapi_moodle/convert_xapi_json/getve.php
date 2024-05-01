<?php

include('config.php');

if (isset($_GET['hedares'])) {
    processData('hedares', $_GET['hedares']);
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        processData('last', $days);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}

function processData($param_name, $param_value) {
    global $url, $ws_key, $ws_secret;

    $ch = curl_init();

    // Build the URL
    $url .= '?' . $param_name . '=' . urlencode($param_value);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$ws_key}:{$ws_secret}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
        $data = json_decode($response, true);

        // Process data
        $results = [];
        foreach ($data as $item) {
            // Check for key existence to avoid errors
            $actor_name = isset($item['actor']['name']) ? $item['actor']['name'] : '';
            $verb_id = isset($item['verb']['id']) ? $item['verb']['id'] : '';
            $verb_parts = explode('/', $verb_id);
            $verb = end($verb_parts);

            $object_name = isset($item['object']['definition']['name']['en']) ? $item['object']['definition']['name']['en'] : '';

            $timestamp = isset($item['timestamp']) ? $item['timestamp'] : '';

            $full_json = json_encode($item, JSON_PRETTY_PRINT);
            $results[] = [
                'activity' => "{$actor_name} {$verb} {$object_name}",
                'actor' => $actor_name,
                'verb' => $verb,
                'object' => $object_name,
                'timestamp' => $timestamp,
                'json_data' => json_decode($full_json)
            ];
        }

        // Convert the array to JSON format
        $json_results = json_encode($results, JSON_PRETTY_PRINT);
        echo $json_results;
    } else {
        echo "Error fetching data from Watershed LRS.";
    }

    curl_close($ch);
}

?>
