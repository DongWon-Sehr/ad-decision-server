<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward-earn

    // set user reward
    method: PUT
    params: [
        "user_id" => 1,             mandatory
        "ad_issue_id" => c9010f4v,  mandatory
        "ad_id" => 33,              mandatory
        "reward" => 10,             mandatory
        "ignore_cache" => 0,        optional
        "debug" => 1,               optional
    ]
*/

$http_method = $_SERVER["REQUEST_METHOD"];
if ( $http_method !== "PUT" ) {
    $httpCode = 405;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Method Not Allowed",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

parse_str(file_get_contents("php://input"), $_post_data);
extract($_post_data);

// HTTP Redirect was prohibiting the data from being redirected along.
// So use GET request method
extract($_GET);

// check parameter
if ( isset($debug) && in_array($debug, ["1", "true"]) ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

if ( ! isset($user_id) || ! preg_match("/^\d+$/", $user_id ) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid user_id",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else {
    $user_id = intval($user_id);
}

if ( ! isset($ad_issue_id) || ! strlen($ad_issue_id) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid ad_issue_id",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

if ( ! isset($ad_id) || ! preg_match("/^\d+$/", $ad_id ) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid ad_id",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else {
    $ad_id = intval($ad_id);
}

if ( 
    ! isset($reward) || 
    ! preg_match("/^(\d+)$/", $reward, $matches) || 
    intval($matches[1]) < 0 // $reward always positive
) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid reward",
    ];
    http_response_code($httpCode0);
    exit(json_encode($reward));
} else {
    $reward = intval($reward);
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

// check user info
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM user WHERE id = {$user_id}";
$user_info = $m_mysql->query($sql, $debug);
if ( ! $user_info ) {
    $httpCode = 404;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Not Found user_id",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}
$user = $user_info[0];

// check ad_issue exist & user_reward_queue already pending
$sql = "SELECT *
          FROM ad_issue 
         WHERE id = '{$ad_issue_id}' 
           AND user_id = {$user_id} 
           AND ad_id = {$ad_id}";
$ad_issue_info = $m_mysql->query($sql, $debug);

if ( ! $ad_issue_info ) {
    $httpCode = 404;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Not Found ad_issue",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else if ( $ad_issue_info[0]["user_reward_queue_id"] ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "The Reward is already paid or on pending",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else if ( $ad_issue_info[0]["reward"] !==  $reward ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid reward request",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// insert user_reward_queue
$type = "earn";
$created_at = date("Y-m-d H:i:s");
$sql = "INSERT INTO user_reward_queue 
        (type, user_id, reward, created_at) 
        VALUES 
        ('{$type}', {$user_id}, {$reward}, '{$created_at}')";
$exec_result = $m_mysql->exec_sql($sql, $debug);

if ( ! $exec_result ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(1)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// get user_reward_queue_id just created
$sql = "SELECT * 
          FROM user_reward_queue
         WHERE type = '{$type}'
           AND user_id = {$user_id}
           AND reward = {$reward}
           AND created_at = '{$created_at}'
           AND approved_at IS NULL";
$user_reward_queue_info = $m_mysql->query($sql, $debug);

if ( ! $user_reward_queue_info ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(2)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}
$user_reward_queue = $user_reward_queue_info[0];

// update ad_issue.user_reward_queue_id
$sql = "UPDATE ad_issue 
           SET user_reward_queue_id = {$user_reward_queue["id"]}
         WHERE id = '{$ad_issue_id}' 
           AND user_id = {$user_id} 
           AND ad_id = {$ad_id}";
$exec_result = $m_mysql->exec_sql($sql, $debug);

if ( $exec_result === NULL ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(3)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// update user.reward
$updated_reward = $user["reward"] + $reward;
$sql = "UPDATE user SET reward = {$updated_reward} WHERE id = {$user_id}";
$exec_result = $m_mysql->exec_sql($sql, $debug);
if ( $exec_result === NULL ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(4)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// update user_reward_queue.approved_at
$approved_at = date("Y-m-d H:i:s");
$sql = "UPDATE user_reward_queue SET approved_at = '{$approved_at}' WHERE id = {$user_reward_queue["id"]}";
$exec_result = $m_mysql->exec_sql($sql, $debug);
if ( $exec_result === NULL ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(5)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// get user reward again
$sql = "SELECT * FROM user WHERE id = {$user_id}";
$user_info = $m_mysql->query($sql, $debug);
if ( ! $user_info ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(6)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}
$user = $user_info[0];

// response
$response = [
    "response_at" => date("Y-m-d H:i:s"),
    "result" => [
        "user_id" => $user["id"],
        "reward" => $user["reward"],
    ],
];

// update user-reward cache
$m_redis->set_cache("api-v3-user-reward", $cache_key, $user);

http_response_code(200);
exit(json_encode($response));

?>