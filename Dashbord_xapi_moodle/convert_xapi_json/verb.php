<?php

include('config.php');

function filterData($data, $param_value, $key = 'timestamp')
{
    $param_timestamp = strtotime($param_value);
    return array_filter($data, function ($entry) use ($param_timestamp, $key) {
        $timestamp = strtotime($entry[$key]);
        return $timestamp >= $param_timestamp;
    });
}

function getActions($data)
{
    $actions = [];
    foreach ($data as $item) {
        if (isset($item['verb']['id'])) {
            $verb_id = $item['verb']['id'];
            $verb_parts = explode('/', $verb_id);
            $verb = end($verb_parts);
            if (!isset($actions[$verb])) {
                $actions[$verb] = 0;
            }
            $actions[$verb]++;
        }
    }
    arsort($actions);
    return $actions;
}

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

        $filtered_data = filterData($data, $param_value);

        $actions = getActions($filtered_data);

        $result = [];
        foreach ($actions as $verb => $count) {
            $result[] = [$verb, $count];
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo "error";
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

            $filtered_data = filterData($data, "-{$days} days");

            $actions = getActions($filtered_data);

            $result = [];
            foreach ($actions as $verb => $count) {
                $result[] = [$verb, $count];
            }

            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            echo "error";
        }

        curl_close($ch);
    } else {
        echo "Parameter 'last' must be a positive integer.";
    }
} else {
    echo "Parameter 'hedares' or 'last' is missing.";
}
?>
