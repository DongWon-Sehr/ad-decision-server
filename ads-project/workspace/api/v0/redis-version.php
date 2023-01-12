<?php
require_once("library/redis.php");
require_once("library/mysql.php");

extract($_GET);

$m_redis = new dw_redis();

if ($m_redis->connect() == true) {
    $redis_version = $m_redis->redis->info()["redis_version"];

    $m_mysql = new dw_mysql();
    $r = $m_mysql->query("SELECT COUNT(*) FROM ad_campaigns;");
    
    $cache_key = "redis_test";
    $m_redis->set($cache_key, $r);
    
    $cache = $m_redis->get($cache_key);
    $response = [
        "redis_version" => $redis_version,
        "ad_campaigns_records_count" => $cache[0][0],
    ];
} else {
    $response = [
        "errorCode" => 500,
        "message" => "Redis Failure!",
    ];
}

header("Content-Type: application/json");
echo json_encode($response);
?>