<?php

/* ProxyChecker
 * by Robert Beran
 * robert (at) die-grafiken.de
 */

include 'ListIPs.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

set_time_limit(0);

class ProxyChecker extends ListIPs {

    public $results = "";
    public $proxy = array();
    public $check_url = "";
    public $time = array();
    public $working = "";
    public $resp_code = 0;
    public $auf_einmal = 23;
    public $check = array();

    public function __construct() {
        $this->check_url = "https://yourdomain/gate.php";
        file_put_contents("ergebnisse", "");
    }

    public function get_proxies() {

        $back = file("ips");

        return array_map('trim', $back);
    }

    public function auswerten($backs, $array, $time) {

        $backs = unserialize($backs);

        if (!empty($backs)) {

            // check transparnt proxy

            if (!empty($backs['HTTP_X_FORWARDED_FOR'])) {

                if (($backs['REMOTE_ADDR'] == $backs['HTTP_X_FORWARDED_FOR']) || strstr($backs['HTTP_X_FORWARDED_FOR'], $backs['REMOTE_ADDR'])) {

                    $ano = "transparent\r\n";
                }
            } else if (empty($backs['TYPE_NAME']) && !empty($backs['HTTP_VIA'])) {

                $ano = "anonymous\r\n";
            } else if (empty($backs['TYPE_NAME']) && empty($backs['HTTP_VIA'])) {

                $ano = "high anonymous\r\n";
            }

            $arrays = preg_grep('/(' . $backs['REMOTE_ADDR'] . ':[0-9]{2,5})/', $array);

            foreach ($arrays as $key => $value) {

                if($time < 0.1) {
                
                echo "IP: " . $value . " Time: " . $time . " Sichtbar: " . $ano;

                file_put_contents("ergebnisse", $value . "\r\n", FILE_APPEND);
                
                }
                
            }
        }
    }

    public function send_request() {

        $this->proxy = $this->get_proxies();

        $ch = array();
        $total = 100;
        $prox = array();                 

        $i = 0;

        $durchlauf = count($this->proxy) / $this->auf_einmal;
        
        for ($insel = 0; $insel <= floor($durchlauf); $insel++) {
            
            echo $insel;

            $mh = curl_multi_init();

            foreach ($this->proxy as $key_p => $value_p) {

                echo $value_p."\r\n";
                
                $prox[] = $value_p;
                
                $ch[$i] = curl_init();
                curl_setopt($ch[$i], CURLOPT_URL, $this->check_url);
                curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch[$i], CURLOPT_FAILONERROR, true);
                curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch[$i], CURLOPT_TIMEOUT, 3);
//curl_setopt($multiCurl[$i], CURLOPT_VERBOSE, 0);

                curl_setopt($ch[$i], CURLOPT_PROXY, trim($value_p));

// Do not check the SSL certificates
                curl_setopt($ch[$i], CURLOPT_SSL_VERIFYPEER, false);

                curl_multi_add_handle($mh, $ch[$i]);
                
                $i++;
                
                if ($i == $this->auf_einmal) {

                    $i = 1;
                    
                    $this->proxy = array_diff($this->proxy, $prox);
                    
                    break;
                    
                }
                
            }

            $active = null;
            do {
                $mrc = curl_multi_exec($mh, $active);
                usleep(100); // Maybe needed to limit CPU load (See P.S.)
            } while ($active);

            foreach ($ch as $c) {

                $r = curl_multi_getcontent($c);
                curl_multi_remove_handle($mh, $c);

                if (!empty($r) && !empty(curl_multi_info_read($mh))) {

                    $info = curl_multi_info_read($mh);

                    $cti = curl_getinfo($info['handle'], CURLINFO_CONNECT_TIME);

                    if (!empty($cti)) {

                        $this->auswerten($r, $prox, $cti);
                    }
                }
            }

            curl_multi_close($mh);

        }
        
    }

}

?>
