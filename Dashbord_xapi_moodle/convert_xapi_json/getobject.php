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

function countActivities($data) {
    $activities = [];
    foreach ($data as $item) {
        if (isset($item['object']['id'])) {
            $activity_id_parts = explode('/', $item['object']['id']);
            $activity_type = $activity_id_parts[count($activity_id_parts) - 2];
            if (!empty($activity_type) && $activity_type !== 'course') { // Check if activity_type is not empty and not equal to 'cours'
                if (!isset($activities[$activity_type])) {
                    $activities[$activity_type] = 0;
                }
                $activities[$activity_type]++;
            }
        }
    }
    arsort($activities);
    return $activities;
}



if (isset($_GET['hedares'])) {
    $param_value = $_GET['hedares'];
    $response = fetchData($url, $ws_key, $ws_secret);
    $data = json_decode($response, true);
    $activities = countActivities($data);
    $output = array_map(null, array_keys($activities), array_values($activities));
    echo json_encode($output, JSON_PRETTY_PRINT);
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        $response = fetchData($url, $ws_key, $ws_secret);
        $data = json_decode($response, true);
        $activities = countActivities($data);
        $output = array_map(null, array_keys($activities), array_values($activities));
        echo json_encode($output, JSON_PRETTY_PRINT);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}
?>
