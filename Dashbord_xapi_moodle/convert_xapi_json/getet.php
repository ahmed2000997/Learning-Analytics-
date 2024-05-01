<?php
include('config.php');

function fetchData($url, $ws_key, $ws_secret) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$ws_key}:{$ws_secret}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function filterDataByTimestamp($data, $param_value) {
    $param_timestamp = strtotime($param_value);
    return array_filter($data, function ($entry) use ($param_timestamp) {
        $timestamp = strtotime($entry['timestamp']);
        return $timestamp >= $param_timestamp;
    });
}

function countInteractionsByStudent($filtered_data) {
    $student_names = [];
    foreach ($filtered_data as $item) {
        $roles = isset($item['context']['extensions']['http://vocab.xapi.fr/extensions/user-role']) ? $item['context']['extensions']['http://vocab.xapi.fr/extensions/user-role'] : '';
        $name = isset($item['actor']['name']) ? $item['actor']['name'] : (isset($item['actor']['account']['name']) ? $item['actor']['account']['name'] : '');
        if (strpos($roles, 'student') !== false && !in_array($name, $student_names)) {
            $student_names[] = $name;
        }
    }

    $interactions_count = [];
    foreach ($student_names as $name) {
        $count = 0;
        foreach ($filtered_data as $item) {
            $actor_name = isset($item['actor']['name']) ? $item['actor']['name'] : (isset($item['actor']['account']['name']) ? $item['actor']['account']['name'] : '');
            if ($actor_name === $name) {
                $count++;
            }
        }
        $interactions_count[$name] = $count;
    }

    arsort($interactions_count);
    return $interactions_count;
}



if (isset($_GET['hedares'])) {
    $param_value = $_GET['hedares'];
    $response = fetchData($url, $ws_key, $ws_secret);
    $data = json_decode($response, true);
    $filtered_data = filterDataByTimestamp($data, $param_value);
    $interactions_count = countInteractionsByStudent($filtered_data);
    $formatted_results = [];
    foreach ($interactions_count as $name => $count) {
        $formatted_results[] = [$name, $count];
    }
    echo json_encode($formatted_results, JSON_PRETTY_PRINT);
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        $response = fetchData($url, $ws_key, $ws_secret);
        $data = json_decode($response, true);
        $filtered_data = filterDataByTimestamp($data, "-{$days} days");
        $interactions_count = countInteractionsByStudent($filtered_data);
        $formatted_results = [];
        foreach ($interactions_count as $name => $count) {
            $formatted_results[] = [$name, $count];
        }
        echo json_encode($formatted_results, JSON_PRETTY_PRINT);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}
?>
