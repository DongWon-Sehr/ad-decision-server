<?php

require_once("library/redis.php");
require_once("library/mysql.php");

/*
	Caller Example
	http://localhost:8080/api/v1/target-ads\
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

function get_weighted_random_index($weights) {
    $r = rand(1, array_sum($weights));
    for($i=0; $i<count($weights); $i++) {
      $r -= $weights[$i];
      if($r < 1) return $i;
    }
    return false;
}

// main -------------------------------------------------------------------------------------------

// set cache key
$cache_key = "api-v1-target-ads-" . $gender . $country;

// get ads list from cache
if ( ! $ignore_cache ) {
    $m_redis = new dw_redis();
    $ads_list = $m_redis->get($cache_key);
}

// get ads list from db
if ( ! $ads_list ) {
    $m_mysql = new dw_mysql();
    $sql = "SELECT * FROM ad_campaigns WHERE target_country = '{$country}' AND target_gender = '{$gender}'";
    $ads_list = $m_mysql->query($sql);
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

// get 3 target ads via weight
$response = [];
$weights = array_column($ads_list, "weight");
$ads_max_count = $ads_count;
if ($ads_max_count > 3) $ads_max_count = 3;
for ($i=0; $i<$ads_max_count; $i++) {
    $index = get_weighted_random_index($weights);
    $response []= [
        "id" => $ads_list[$index]["id"],
        "name" => $ads_list[$index]["name"],
        "image_url" => $ads_list[$index]["image_url"],
        "landing_url" => $ads_list[$index]["landing_url"],
        "reward" => $ads_list[$index]["reward"],
    ];
}

// set cache
$m_redis->set($cache_key, $ads_list);

// response
header("Content-Type: application/json");
echo json_encode($response);

?>