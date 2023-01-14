<?php

require_once "library/redis.php";
require_once "library/mysql.php";
require_once 'library/ad_policy/core/ad_policy_selector.php';

/*
	Caller Example
	http://localhost:8080/api/v2/target-ads\
		?user_id=1\         mandatory
        &gender=F\	        mandatory
        &country=KR         mandatory
        &ignore_cache=0     optional
*/

$http_method = $_SERVER["REQUEST_METHOD"];
if ( $http_method != "GET" ) {
    $response = [
        "errorCode" => 405,
        "message" => "Method Not Allowed",
    ];
    header("Content-Type: application/json");
    exit(json_encode($response));
}

// validate parameters
extract($_GET);

if ( ! isset($user_id) || ! preg_match("/^\d+$/", $user_id ) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid User ID",
    ];
    header("Content-Type: application/json");
    exit(json_encode($response));
} else {
    $user_id = intval($user_id);
}

if ( ! isset($gender) || ! in_array( $gender, ["M", "F"] ) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid Gender",
    ];
    header("Content-Type: application/json");
    exit(json_encode($response));
}

if ( ! isset($country) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid Country",
    ];
    header("Content-Type: application/json");
    exit(json_encode($response));
}

if ( ! isset($ignore_cache) || ($ignore_cache != "1" && $ignore_cache != "true") ) {
    $ignore_cache = false;
} else {
    $ignore_cache = true;
}

// main -------------------------------------------------------------------------------------------

// set cache key
$key_prefix = "api-v2-target-ads";
$cache_key = "{$key_prefix}-{$gender}-{$country}";
$m_redis = new dw_redis();

// get ads list from cache
if ( ! $ignore_cache ) {
    $ads_list = $m_redis->get($cache_key);
}

// get ads list from db
if ( ! $ads_list ) {
    $m_mysql = new dw_mysql();
    $sql = "SELECT * FROM ad_campaigns WHERE target_country = '{$country}' AND target_gender = '{$gender}'";
    $ads_list = $m_mysql->query($sql, $debug, 1);
}

$ads_count = @count($ads_list);
if ( ! $ads_count ) {
    $response = [
        "errorCode" => 400,
        "message" => "No Target Ads",
    ];
    header("Content-Type: application/json");
    exit(json_encode($response));
}

// get max 3 target ads via weight
$m_ad_policy_selector = new AdPolicySelector($user_id, $ads_list);
$target_ads = $m_ad_policy_selector->get_target_ads();

$response = [
    "policy" => $m_ad_policy_selector->get_policy_title(),
    "target_ads" => [],
];

foreach ($target_ads as $_ad_info) {
    $response["target_ads"] []= [
        "id" => $_ad_info["id"],
        "name" => $_ad_info["name"],
        "image_url" => $_ad_info["image_url"],
        "landing_url" => $_ad_info["landing_url"],
        "reward" => $_ad_info["reward"],
    ];
}

$m_redis->set($cache_key, $ads_list);

// response
header("Content-Type: application/json");
echo json_encode($response);

?>