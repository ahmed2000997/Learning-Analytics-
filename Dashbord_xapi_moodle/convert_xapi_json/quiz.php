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

function filterDataByGradedQuiz($data, $timestamp = null) {
    $filteredData = array_filter($data, function ($entry) use ($timestamp) {
        $validType = isset($entry['object']['definition']['type']) && $entry['object']['definition']['type'] == 'http://vocab.xapi.fr/activities/quiz';
        $validScore = isset($entry['result']['score']['scaled']);
        $validTimestamp = !$timestamp || strtotime($entry['timestamp']) >= $timestamp;
        return $validType && $validScore && $validTimestamp;
    });
    return $filteredData;
}

function calculateAverageScore($data) {
    $totalScore = 0;
    $totalQuizzes = count($data);
    foreach ($data as $entry) {
        $totalScore += $entry['result']['score']['scaled'];
    }
    return $totalQuizzes > 0 ? $totalScore / $totalQuizzes : 0;
}

$response = fetchData($url, $ws_key, $ws_secret);
$data = json_decode($response, true);

if (isset($_GET['headers'])) {
    $headers = $_GET['headers'];
    $filteredData = array_filter($data, function ($entry) use ($headers) {
        return isset($entry['headers']) && $entry['headers'] == $headers;
    });
} elseif (isset($_GET['last'])) {
    $days = intval($_GET['last']);
    if ($days > 0) {
        $timestamp = strtotime("-{$days} days");
        $filteredData = filterDataByGradedQuiz($data, $timestamp);
    } else {
        echo "Parameter 'last' must be a positive integer.";
        exit;
    }
} else {
    $filteredData = filterDataByGradedQuiz($data);
}

$averageScores = [];

foreach ($filteredData as $entry) {
    $userId = $entry['actor']['account']['name'];
    if (!isset($averageScores[$userId])) {
        $averageScores[$userId] = [];
    }
    $averageScores[$userId][] = $entry['result']['score']['scaled'];
}

$output = [];
foreach ($averageScores as $userId => $scores) {
    $averageScore = array_sum($scores) / count($scores);
    $output[] = [
        $userId,
        round($averageScore * 100, 2) // تقريب النسبة المئوية دون إضافة الرمز %
    ];
}

// ترتيب المصفوفة من أعلى قيمة إلى أقل قيمة
usort($output, function($a, $b) {
    return $b[1] - $a[1];
});

echo json_encode($output, JSON_PRETTY_PRINT);
?>
