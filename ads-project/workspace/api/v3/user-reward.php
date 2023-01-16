<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward\
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
$cache_key = "{$user_id}";
$m_redis = new dw_redis();

// checck if cache data exist
if ( ! $ignore_cache ) {
    $cache = $m_redis->get_cache("api-v3-user-reward", $cache_key);
    if ($cache) {
        // set response
        $response = [
            "response_at" => date("Y-m-d H:i:s"),
            "result" => [
                "user_id" => $cache["id"],
                "reward" => $cache["reward"],
            ],
        ];
        http_response_code(200);
        exit(json_encode($response));
    }
}

// check history
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM user WHERE id = {$user_id}";
$user_info = $m_mysql->query($sql, $debug);

if ( ! $user_info ) {
    $response = [
        "errorCode" => 404,
        "message" => "Not Found user_id",
    ];
    http_response_code(404);
    exit(json_encode($response));
}
$user = $user_info[0];

// set response
$response = [
    "response_at" => date("Y-m-d H:i:s"),
    "result" => [
        "user_id" => $user["id"],
        "reward" => $user["reward"],
    ],
];

// update cache
$m_redis->set_cache("api-v3-user-reward", $cache_key, $user);

http_response_code(200);
exit(json_encode($response));

?>