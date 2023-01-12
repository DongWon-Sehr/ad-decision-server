<?php
require_once("library/mysql.php");

extract($_GET);

$m_mysql = new dw_mysql();

if ($m_mysql->open()) {
    $mysql_version = $m_mysql->connection->query('select version()')->fetchColumn();
    echo "MySQL Version: {$mysql_version}<br>";
    
    $sql = "SELECT COUNT(*) FROM ad_campaigns;";
    $r = $m_mysql->query($sql, 0);

    $response = [
        "mysql_version" => $mysql_version,
        "ad_campaigns_records_count" => $r[0][0],
    ];
} else {
    $response = [
        "errorCode" => 404,
        "message" => "MySQL Failure!",
    ];
}

header("Content-Type: application/json");
echo json_encode($response);
?>