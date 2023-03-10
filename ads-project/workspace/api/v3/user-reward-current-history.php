<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward-current-history\
		?user_id=1\         mandatory
        &ignore_cache=0     optional
        &debug=0            optional
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

// check paramter
if ( isset($debug) && in_array($debug, ["1", "true"]) ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

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

if ( isset($ignore_cache) && in_array($ignore_cache, ["1", "true"]) ) {
    $ignore_cache = 1;
} else {
    $ignore_cache = 0;
}

// main -------------------------------------------------------------------------------------------

// set cache key
$target_date = date("Y-m-d", strtotime("-7 Day"));
$cache_key = "{$user_id}-{$target_date}";
$m_redis = new dw_redis();

// check if cache data exist
if ( ! $ignore_cache ) {
    $cache = $m_redis->get_cache("api-v3-user-reward-current-history", $cache_key);
    if ($cache) {
        $response = [
            "response_at" => date("Y-m-d H:i:s"),
            "from" => $target_date,
            "to" => date("Y-m-d"),
            "result" => $cache,
        ];

        http_response_code(200);
        exit(json_encode($response));
    }
}

// check history in DB
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM user_reward_queue WHERE user_id = {$user_id} AND created_at > '$target_date' ORDER BY created_at DESC";
$user_reward_queue_info = $m_mysql->query($sql);

// init response
$response = [
    "response_at" => date("Y-m-d H:i:s"),
    "from" => $target_date,
    "to" => date("Y-m-d"),
    "result" => [],
];

$result = [];
if ($user_reward_queue_info) {
    // set response
    foreach ($user_reward_queue_info as $_user_reward_queue) {
        $result []= [
            "type" => $_user_reward_queue["type"],
            "reward" => $_user_reward_queue["reward"],
            "created_at" => $_user_reward_queue["created_at"],
            "approved_at" => $_user_reward_queue["approved_at"],
            "status" => $_user_reward_queue["approved_at"] ? "approved" : "pending",
        ];
    }

    // update cache
    $m_redis->set_cache("api-v3-user-reward-current-history", $cache_key, $result);
}

$response["result"] = $result;

http_response_code(200);
exit(json_encode($response));

?>