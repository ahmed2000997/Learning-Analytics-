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

function filterDataByLastDays($data, $days) {
    $timestamp = strtotime("-{$days} days");
    return array_filter($data, function ($entry) use ($timestamp) {
        $entry_timestamp = strtotime($entry['timestamp']);
        return $entry_timestamp >= $timestamp;
    });
}

function countCourses($data) {
    $courses = [];
    foreach ($data as $item) {
        if (isset($item['object']['definition']['type']) && $item['object']['definition']['type'] == 'http://vocab.xapi.fr/activities/course') {
            $course_name = $item['object']['definition']['name']['en'];
            $courses[$course_name] = isset($courses[$course_name]) ? $courses[$course_name] + 1 : 1;
        }
    }
    arsort($courses);
    return $courses;
}

if (isset($_GET['hedares'])) {
    $param_value = $_GET['hedares'];
    $response = fetchData($url, $ws_key, $ws_secret);
    $data = json_decode($response, true);
    $filtered_data = filterDataByTimestamp($data, $param_value);
    $courses = countCourses($filtered_data);
    $output = array_map(null, array_keys($courses), array_values($courses));
    echo json_encode($output, JSON_PRETTY_PRINT);
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        $response = fetchData($url, $ws_key, $ws_secret);
        $data = json_decode($response, true);
        $filtered_data = filterDataByLastDays($data, $days);
        $courses = countCourses($filtered_data);
        $output = array_map(null, array_keys($courses), array_values($courses));
        echo json_encode($output, JSON_PRETTY_PRINT);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}
?>
