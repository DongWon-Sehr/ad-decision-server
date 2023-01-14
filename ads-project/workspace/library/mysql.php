<?php

class dw_mysql {

    public $connection = NULL;
    public $host = 'hostname-mysql-master'; // service name from docker-compose.yml
    public $port = "3306";
    public $user = 'buzzvil';
    public $password = 'buzzvil';
    public $database = "buzzvil";
    public $options = NULL;
    public $master = [
        "host" => 'hostname-mysql-master',
        "port" => "3306",
    ];
    public $slave = [
        [
            "host" => 'hostname-mysql-slave',
            "port" => "3306",
        ],
    ];
    public $previous_connection = [
        "host" => NULL,
        "port" => NULL,
        "database" => NULL
    ];

    function __construct()
    {
        $this->options = [
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => FALSE
        ];
    }

    function __destruct()
    {
        $this->close();   
    }

    function open()
    {
        // close the connection if the previous connection is not planned
        if (
            ($this->previous_connection["host"] !== $this->host) &&
            ($this->previous_connection["port"] !== $this->port) &&
            ($this->previous_connection["database"] !== $this->database)
        ) {
            $this->close();
        }

        // establish connection
        for ($i = 0; $i < 5; $i++) {
            if ($i) {
                sleep($i);
            }

            if ($this->connection == NULL) try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8";
                
                $this->connection = new PDO($dsn, $this->user, $this->password, $this->options);
                $this->connection->query("SET wait_timeout=86400;");
                $this->connection->query("SET CHARACTER SET utf8");
            } catch (PDOException $exc) {
                $this->connection = NULL;
            }

            if ($this->connection) {
                break;
            }
        }

        // save the connection info
        if ($this->connection) {
            $this->previous_connection = [
                "host" => $this->host,
                "port" => $this->port,
                "database" => $this->database
            ];
        } else {
            echo "DB open failure !!";
            exit;
        }

        return $this->connection;
    }

    function close()
    {
        if (is_object($this->connection)) {
            $this->connection->query('KILL CONNECTION_ID()');
        }
        $this->connection = NULL;
    }

    function route_query(string $sql, $debug = 0)
    {
        if (isset($this->master) && isset($this->slave)) {
            $sql = trim($sql);

            if (preg_match('/(^SELECT)/i', $sql)) {
                // set slave
                $slave_max_cnt = count($this->slave) - 1;
                $slave_idx = rand(0, $slave_max_cnt);
                $this->host = $this->slave[$slave_idx]['host'];
                $this->port = $this->slave[$slave_idx]['port'];

                if ($debug) echo "[{$this->host}:{$this->port}] connect sql = $sql";
                return true;
            } else {
                // set master
                $this->host = $this->master['host'];
                $this->port = $this->master['port'];

                if ($debug) echo "[{$this->host}:{$this->port}] connect sql = $sql";
                return true;
            }
        } else {
            // non master slave data
            return false;
        }
    }

    function query(string $sql, $debug = 0)
    {
        if (strlen($sql) == 0) {
            echo "Fatal error - query is empty !!!";
            exit;
        }

        $this->route_query($sql, $debug);

        if ($this->open()) {

            // run original query planned
            if ($debug) echo "db open - ok<br>\n";
            try {
                $select = $this->connection->query($sql);
            } catch (Exception $e) {
                echo "DB:\n{$sql}\nException -> <pre>\n";
                var_dump($e->getMessage());
                echo "</pre>";
            }

            $ret = null;
            if ($select) {
                if ($debug) echo "db query - ok<br>\n";

                // get query result
                while ($row = $select->fetch()) $ret[] = $row;
            }

            $this->close();

            if ($debug) {
                echo "Query result:<br>\n";
                echo "<pre>";
                var_dump($ret);
                echo "</pre>";
            }

            if ($debug && is_array($ret)) echo "result count  = " . count($ret) . "<br>\n";

            return $ret;
        } else {
            if ($debug) echo "return value for query execution is null - original query: {$sql}";
            return null;
        }
    }
    
}

?>