<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

date_default_timezone_set("Asia/Seoul");

require_once "library/redis.php";
require_once "library/mysql.php";
require_once 'library/ad_policy/core/ad_policy_selector.php';

/*
	Caller Example
	http://localhost:8080/api/v3/target-ads\
		?user_id=1\         mandatory
        &gender=F\	        mandatory
        &country=KR         mandatory
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

if ( ! isset($gender) || ! in_array( $gender, ["M", "F"] ) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid gender",
    ];
    http_response_code(400);
    exit(json_encode($response));
}

if ( ! isset($country) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid country",
    ];
    http_response_code(400);
    exit(json_encode($response));
}

if ( isset($ignore_cache) && in_array($ignore_cache, ["1", "true"]) ) {
    $ignore_cache = 1;
} else {
    $ignore_cache = 0;
}

// main -------------------------------------------------------------------------------------------

// set cache key
$cache_key = "{$gender}-{$country}";
$m_redis = new dw_redis();

// get ads list from cache
if ( ! $ignore_cache ) {
    $ads_list = $m_redis->get_cache("api-v3-target-ads", $cache_key);
}

// get ads list from db
$m_mysql = new dw_mysql();
if ( ! $ads_list ) {
    $sql = "SELECT * FROM ad_campaigns WHERE target_country = '{$country}' AND target_gender = '{$gender}'";
    $ads_list = $m_mysql->query($sql, $debug);
}

// get max 3 target ads via weight
$m_ad_policy_selector = new AdPolicySelector($user_id, $ads_list ?? []);
$target_ads = $m_ad_policy_selector->get_target_ads();

// init response
$created_at = date("Y-m-d H:i:s");
$ad_issue_id = md5($user_id . $created_at);
$response = [
    "ad_issue_id" => $ad_issue_id,
    "response_at" => $created_at,
    "policy" => $m_ad_policy_selector->get_policy_title(),
    "target_ads" => [],
];

$sql = "INSERT INTO ad_issue (id, user_id, ad_id, reward, created_at) VALUES ";
$values = "";
if ($target_ads) {

    // set response
    foreach ($target_ads as $_ad_info) {
        $response["target_ads"] []= [
            "id" => $_ad_info["id"],
            "name" => $_ad_info["name"],
            "image_url" => $_ad_info["image_url"],
            "landing_url" => $_ad_info["landing_url"],
            "reward" => $_ad_info["reward"],
        ];

        // set query
        if ($values) $values .= ", ";
        $values .= "('{$ad_issue_id}', {$user_id}, {$_ad_info["id"]}, {$_ad_info["reward"]}, '{$created_at}')"; 
    }

    // insert ad issue data @table: ad_issue
    $sql = $sql . $values;
    $exec_result = $m_mysql->exec_sql($sql, $debug);
    if ( ! $exec_result ) {
        $httpCode = 500;
        $response = [
            "errorCode" => $httpCode,
            "message" => "Internal Server Error",
        ];
        http_response_code($httpCode);
        exit(json_encode($response));
    }
}

// update cache if db query exist
if ( $ads_list ) {
    $m_redis->set_cache("api-v3-target-ads", $cache_key, $ads_list);
}

// response
http_response_code(200);
exit(json_encode($response));

?>