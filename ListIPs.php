<?php

/**
 * Description of list_ips
 *
 * @author robert beran
 */

class ListIPs {

    public $domain = "";
    public $regex = "";
    public $urls = array();
    public $providers = array();
    public $result = "";
    public $list = "";
    public $res_proxies = array();
    public $res_time = array();
    
    public function __construct() {
        file_put_contents("ips", "");
    }

    public function crawl($url) {

        $curl = curl_init();

// Set the file URL to fetch through cURL
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

// Do not check the SSL certificates
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

// Fetch the URL and save the content in $html variable
        $html = curl_exec($curl);

        return $html;
        
    }
    
    public function set_proxylists() {
        
        $status = 0;
        
        $this->domain = 'freeproxylists.com';
        $this->regex = '/href=\'https\/(.*?)\.html\'>/is';
        
        $res = $this->crawl('http://www.freeproxylists.com/https.html');
        
        preg_match_all($this->regex, $res, $matches);

        foreach ($matches[1] as $key => $value) {
            
            $status++;

            //href='https/d1575149421.html'>
            $ergebnis = $this->crawl('http://www.freeproxylists.com/load_https_' . $value . '.html');

            //&gt;37.52.11.12&lt;/td&gt;&lt;td&gt;41803&lt;

            $this->regex = '/&gt;(\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b)&lt;\/td&gt;&lt;td&gt;([0-9]{2,5})&lt;/is';
            //&gt;50.235.92.14&lt;
            preg_match_all($this->regex, $ergebnis, $matches1);

            for ($i = 0; $i < count($matches1[1]); $i++) {

                $ip = $matches1[1][$i] . ":" . $matches1[2][$i] . "\r\n";

                file_put_contents("ips", $ip, FILE_APPEND);
            }
            
            if($status >= 3) {
                
                break;
                
            }

        }
        
    }
    
}