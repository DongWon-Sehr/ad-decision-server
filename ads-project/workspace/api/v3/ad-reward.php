<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/ad-reward

    method: PUT
    params: [
        "ad_id" => 1,   mandatory
        "reward" => 33, mandatory
        "debug" => 1,   optional
    ]
*/
$MAX_REWARD_LIMIT = 100;

$http_method = $_SERVER["REQUEST_METHOD"];
if ( $http_method !== "PUT" ) {
    $response = [
        "errorCode" => 405,
        "message" => "Method Not Allowed",
    ];
    http_response_code(405);
    exit(json_encode($response));
}

parse_str(file_get_contents("php://input"), $_post_data);
extract($_post_data);

// HTTP Redirect was prohibiting the data from being redirected along.
// So use GET request method
extract($_GET);


if ( isset($debug) && in_array($debug, ["1", "true"]) ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
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

if ( ! isset($reward) ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Not Found reward",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else if ( ! preg_match("/^(\d+)$/", $reward, $matches ) 
            || $matches && intval($matches[1]) < 0
            || $matches && intval($matches[1]) > $MAX_REWARD_LIMIT ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Invalid reward",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
} else {
    $reward = intval($matches[1]);
}

// main -------------------------------------------------------------------------------------------

// check target ad exist
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM ad_campaigns WHERE id = {$ad_id}";
$ads_list = $m_mysql->query($sql, $debug);

if ( ! $ads_list ) {
    $httpCode = 400;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Given ad_id not exist",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

$sql = "UPDATE ad_campaigns SET reward = {$reward} WHERE id = {$ad_id}";
$query_result = $m_mysql->exec_sql($sql);

if ( ! $query_result ) {
    $httpCode = 500;
    $response = [
        "errorCode" => $httpCode,
        "message" => "Internal Server Error",
    ];
    http_response_code($httpCode);
    exit(json_encode($response));
}

$response = [
    "result" => [
        "ad_id" => $ad_id,
        "reward" => $reward,
    ]
];

// response
http_response_code(201);
exit(json_encode($response));

?>