<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward

    // get user reward
    method: GET
    params: [
        "user_id" => 1,     mandatory
        "debug" => 1,       optional
    ]
*/

$http_method = $_SERVER["REQUEST_METHOD"];
if ( $http_method !== "GET" ) {
    $response = [
        "errorCode" => 405,
        "message" => "Method Not Allowed",
    ];
    http_response_code(405);
    exit(json_encode($response));
}

extract($_GET);

if ( isset($debug) && $debug !== "1" ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

// check paramter
if ( ! isset($user_id) || ! preg_match("/^\d+$/", $user_id ) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid user_id",
    ];
    http_response_code(400);
    exit(json_encode($response));
} else {
    $user_id = intval($user_id);
}

// main -------------------------------------------------------------------------------------------

// set cache key
$key_prefix = "api-v3-user-reward-get";
$target_date = date("Y-m-d", strtotime("-7 Day"));
$cache_key = "{$key_prefix}-{$user_id}-{$target_date}";
$m_redis = new dw_redis();

// checck if cache data exist
if ( ! $ignore_cache ) {
    $cache = $m_redis->get($cache_key);
    if ($cache) {
        http_response_code(200);
        exit(json_encode($cache));
    }
}

// check history
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM reward_queue WHERE user_id = {$user_id} AND created_at > '$target_date' ORDER BY created_at DESC";
$history = $m_mysql->query($sql);

// init response
$response = [
    "from" => $target_date,
    "to" => date("Y-m-d"),
    "result" => [],
];

if ($history) {
    // set response
    foreach ($history as $_reward_info) {
        $response["result"] []= [
            "type" => $_reward_info["type"],
            "reward" => $_reward_info["reward"],
            "status" => $_reward_info["approved_at"] ? "approved" : "pending",
        ];
    }

    // set cache
    $m_redis->set($cache_key, $response);
}

http_response_code(200);
exit(json_encode($response));

?>