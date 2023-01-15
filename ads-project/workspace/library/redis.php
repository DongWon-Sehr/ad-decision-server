<?php
class dw_redis
{
    public $redis = NULL;
    public $host = 'hostname-redis';
    public $port = 6379;
    public $key_prefix = "DEV_";
    public $_stack = array();
    public $handle = false;

    function __construct()
    {
    }

    function __destruct()
    {
    }

    function connect()
    {
        if ($this->handle == true) {
            return true;
        }

        $this->redis = new Redis();

        for ($i = 0; $i < 5; $i++) {
            if ($i) {
                sleep($i);
            }

            if ($this->handle == false) try {
                $this->redis->connect($this->host, $this->port);
                $this->handle = true;
            } catch (Exception $e) {
                $this->handle = false;
            }

            if ($this->handle == true) {
                break;
            }
        }

        if ($this->handle == true) {
            return true;
        } else {
            return false;
        }
    }

    function get(string $key)
    {
        if ($this->connect() == true) {
            // return NULL if key is empty
            if (!strlen($key)) {
                return NULL;
            }

            $resp = $this->redis->get($this->key_prefix . $key);

            // return NULL if response is empty
            if (!strlen($resp)) {
                return NULL;
            } else {
                return json_decode($resp, true, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }
        } else {
            return NULL;
        }
    }

    function set(string $key, $val, int $timeout = 259200) // 86400 * 3 days
    {
        if ($this->connect() == true) {
            // return NULL if key is empty
            if (!strlen($key)) {
                return NULL;
            }

            $json_encoded_val = json_encode($val, JSON_UNESCAPED_UNICODE);

            $result = $this->redis->set($this->key_prefix . $key, $json_encoded_val);

            $this->expire($key, $timeout);

            return $result;
        } else {
            return NULL;
        }
    }

    function expire(string $key, int $timeout = 259200) // 86400 * 3 days
    {
        if ($this->connect() == true) {
            // return NULL if key is empty
            if (!strlen($key)) {
                return NULL;
            }

            return $this->redis->expire($this->key_prefix . $key, $timeout);
        } else {
            return NULL;
        }
    }

    function delete(string $key)
    {
        if ($this->connect() == true) {

            // return NULL if key is empty
            if (!strlen($key)) {
                return;
            }

            /*
                Usage Example:
                    $m_redis->delete('hello'); // delete hello
                    $m_redis->delete('*'); // delete all keys
                    $m_redis->delete('hell*'); // delete hell*
            */
            if ((strpos($key, "*") == true) || ($key == "*")) {
                $key_list = $this->redis->keys($this->key_prefix . $key);
                if (@count($key_list)) {
                    $this->redis->delete($key_list);
                }
            } else {
                $this->redis->delete($this->key_prefix . $key);
            }
        }
    }

    function get_cache(string $id, string $key, int $timeout = 300)
    {
        if ($this->connect() == true) {
            $cache_key = $id . "-" . $key;

            $resp = $this->get($cache_key);

            if ($resp) {
                $curr_time = time();
                $elapsed_time = $curr_time - $resp['tickcount'];

                if (($curr_time <= $resp['expire']) || ($elapsed_time <= $timeout)) {
                    return $resp['src'];
                }
            } 
        }

        return NULL;
    }

    function set_cache(string $id, string $key, $val, int $timeout = 300, int $expire_timeout = 604800)
    {
        if ($this->connect() == true) {
            if ( ! strlen($key) ) {
                return false;
            }

            $resp = [
                "src" => $val,
                "expire" => time() + $timeout,
                "tickcount" => time()
            ];

            /*
                we won't use user timeout, but use 604800 secs for extended access
                604800 = 86400*7
            */
            if ($timeout < $expire_timeout) $timeout = $expire_timeout;
            $cache = $this->set($id . "-" . $key, $resp, $timeout);
            return true;
        }
        
        return false;
    }

    function delete_cache(string $id, $key = NULL)
    {
        if ($this->connect() == true) {
            if (!strlen($key)) return false;
            $this->delete($id . "-" . $key);
            return true;
        } else return false;
    }
}
