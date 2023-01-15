<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward

    // set user reward
    method: PUT
    params: [
        "type" => "use"|"earn"  mandatory
        "reward" => 10,         mandatory
        "user_id" => 1,         mandatory
        "ad_issue_id" => 33,    mandatory
        "ad_id" => 33,          mandatory
        "ignore_cache" => 0,    optional
        "debug" => 1,           optional
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
if ( ! isset($type) || ! in_array($type, ["use", "earn"] ) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid type",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

if ( ! isset($reward) || ! preg_match("/^\d+$/", $reward ) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid reward",
    ];
    http_response_code($httpCode0);
    exit(json_encode($reward));
} else {
    $reward = abs(intval($reward)); // $reward always positive
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

if ( isset($debug) && in_array($debug, ["1", "true"]) ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

if ( isset($ignore_cache) && in_array($ignore_cache, ["1", "true"]) ) {
    $ignore_cache = 1;
    header("Content-Type: text/plain");
} else {
    $ignore_cache = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

// main -------------------------------------------------------------------------------------------

// set cache key
$key_prefix = "api-v3-user-reward-put";
$target_date = date("Y-m-d", strtotime("-7 Day"));
$cache_key = "{$key_prefix}-{$user_id}-{$target_date}";
$m_redis = new dw_redis();

// checck if cache data exist
if ( ! $ignore_cache ) {
    $cache = $m_redis->get($cache_key);
    if ($cache) {
        $httpCode = 200;
        http_response_code($httpCode);
        exit(json_encode($cache));
    }
}

// check user info
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM user WHERE id = {$user_id}";
$user_info = $m_mysql->query($sql);
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

// check condition via param $type
$created_at = date("Y-m-d H:i:s");
if ( $type === "use") {
    // check if user.reward update is available
    if ( $user["reward"] - $reward < 0 ) {
        $httpCode = 400;
        $response = [
            "errorCode" => $httpCode,
            "message" => "Request reward is bigger than user reward",
        ];
        http_response_code($httpCode);
        exit(json_encode($response));
    }

    // insert reward_queue
    $sql = "INSERT INTO reward_queue (type, user_id, reward, created_at) VALUES ('{$type}', {$user_id}, {$reward}, '{$created_at}')";
    $exec_result = $m_mysql->exec_sql($sql);

} else if ( $type === "earn") {
    // check ad_issue & reward_queue info
    $sql = "SELECT ad_issue.(*), reward_queue.approved_at
              FROM ad_issue 
              LEFT JOIN reward_queue 
                ON ad_issue.reward_queue_id = reward_queue.id
             WHERE ad_issue.id = '{$ad_issue_id}' 
               AND ad_issue.user_id = {$user_id} 
               AND ad_issue.ad_id = {$ad_id}";
    $ad_issue_info = $m_mysql->query($sql);
    
    if ( ! $ad_issue_info ) {
        $httpCode = 404;
        $response = [
            "errorCode" => $httpCode,
            "message" => "Not Found ad_issue",
        ];
        http_response_code($httpCode);
        exit(json_encode($response));
    } else if ( $ad_issue_info[0]["reward_queue_id"] ) {
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
    
    // insert reward_queue
    $sql = "INSERT INTO reward_queue (type, user_id, reward, created_at) VALUES ('{$type}', {$user_id}, {$reward}, '{$created_at}')";
    $exec_result = $m_mysql->exec_sql($sql);

    if ( ! $exec_result ) {
        $httpCode = 500;
        $response = [
            "errorCode" => $httpCode,
            "message" => "Internal Server Error(1)",
        ];
        http_response_code($httpCode);
        exit(json_encode($response));
    }
}

// get reward_queue_id just created
$sql = "SELECT * 
          FROM reward_queue
         WHERE type = '{$type}'
           AND user_id = {$user_id}
           AND reward = {$reward}
           AND created_at = {$created_at}
           AND approved_ad IS NULL";
$reward_qeue_info = $m_mysql->query($sql);

if ( ! $reward_qeue_info ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(2)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

if ( $type === "earn") {
    // update ad_issue
    $sql = "UPDATE ad_issue 
               SET (reward_queue_id = {$reward_qeue_info[0]["id"]}) 
             WHERE id = '{$ad_issue_id}' 
               AND user_id = {$user_id} 
               AND ad_id = {$ad_id}";
    $exec_result = $m_mysql->exec_sql($sql);

    if ( ! $exec_result ) {
        $httpCode = 500;
        $response = [
            "errorCode" => $httpCode,
            "message" => "Internal Server Error(3)",
        ];
        http_response_code($httpCode);
        exit(json_encode($response));
    }
}

// update user.reward
if ($type === "use") $reward = (-1) * $reward;
$updated_reward = $user["reward"] + $reward;
$sql = "UPDATE user SET (reward = {$updated_reward}) WHERE id = {$user_id}";
$exec_result = $m_mysql->exec_sql($sql);
if ( ! $exec_result ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(4)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// update reward_queue.approved_at
$approved_at = date("Y-m-d H:i:s");
$sql = "UPDATE reward_queue SET (approved_at = '{$approved_at}') WHERE id = {$reward_qeue_info["id"]}";
$exec_result = $m_mysql->exec_sql($sql);
if ( ! $exec_result ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error(5)",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

// response
http_response_code(201);
exit(json_encode($response));

?>