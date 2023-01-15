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
        "id" => 1,      mandatory
        "reward" => 33, mandatory
        "debug" => 1,   optional
    ]
*/

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

if ( ! isset($id) || ! preg_match("/^\d+$/", $id ) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid id",
    ];
    http_response_code(400);
    exit(json_encode($response));
} else {
    $id = intval($id);
}

if ( ! isset($reward) ) {
    $response = [
        "errorCode" => 400,
        "message" => "Not Found reward",
    ];
    http_response_code(400);
    exit(json_encode($response));
} else if ( ! preg_match("/^(\d+)$/", $reward, $matches ) 
            || $matches && intval($matches[1]) < 0) {
    $response = [
        "errorCode" => 400,
        "message" => "Invalid reward",
    ];
    http_response_code(400);
    exit(json_encode($response));
} else {
    $reward = intval($matches[1]);
}

if ( isset($debug) && $debug !== "1" ) {
    $debug = 1;
    header("Content-Type: text/plain");
} else {
    $debug = 0;
    header("Content-Type: application/json; charset=UTF-8");
}

// main -------------------------------------------------------------------------------------------

// check target ad exist
$m_mysql = new dw_mysql();
$sql = "SELECT * FROM ad_campaigns WHERE id = {$id}";
$ads_list = $m_mysql->query($sql, $debug);

if ( ! $ads_list ) {
    // return bad request
    exit;
}

$sql = "UPDATE ad_campaigns SET reward = {$reward} WHERE id = {$id}";
$query_result = $m_mysql->exec_sql($sql);

if ($query_result === false) {
    $response = [
        "errorCode" => 500,
        "message" => "Internal Server Error",
    ];
    http_response_code(500);
    exit(json_encode($response));
} else if ($query_result === 0) {
    $response = [
        "errorCode" => 400,
        "message" => "Given id not exist",
    ];
    http_response_code(500);
    exit(json_encode($response));
}

$response = [
    "result" => [
        "id" => $id,
        "reward" => $reward,
    ]
];

// response
http_response_code(201);
exit(json_encode($response));

?>