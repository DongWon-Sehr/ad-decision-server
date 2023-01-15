<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "library/redis.php";
require_once "library/mysql.php";

/*
	Caller Example
	http://localhost:8080/api/v3/user-reward

    // get user reward
	http://localhost:8080/api/v3/user-reward\
		?user_id=1\         mandatory
        &ignore_cache=0     optional
        &debug=0            optional

    // set user reward
    method: PUT
    params: [
        "user_id" => 1,     mandatory
        "type" => use|earn  mandatory
        "ad_id" => 33,      mandatory
        "issue_id" => 33,   mandatory
        "debug" => 1,       optional
    ]
*/

$http_method = $_SERVER["REQUEST_METHOD"];
if ( ! in_array($http_method, ["GET", "PUT"]) ) {
    $response = [
        "errorCode" => 405,
        "message" => "Method Not Allowed",
    ];
    http_response_code(405);
    exit(json_encode($response));
} else if ( $http_method === "GET" ) {
    require_once "user-reward-get.php";
} else if ( $http_method === "PUT" ) {
    require_once "user-reward-put.php";
}

?>