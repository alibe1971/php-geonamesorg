<?php

namespace Alibe\PhpGeonamesorg\Lib;

class Exec {
    protected $conn;

    public function __construct($conn) {
        $this->conn=$conn;
        $this->conn['baseHost']=rtrim($this->conn['baseHost'],'/');
    }

    public function get(array $par, $fCall='JSON') {
        $lang=$this->conn['settings']['lang'];
        if(isSet($par['lang']) && $par['lang']) {
            $lang=$par['lang'];
            unset($par['lang']);
        }
        unset($par['clID']);

        $url=$this->conn['baseHost'].'/'.
            $par['cmd'].$fCall.
            '?username='.$this->conn['settings']['clID'].
            '&lang='.$lang;

        if(isSet($par['query'])) {
            foreach ($par['query'] as $k => $v) {
                if(null==$v || false==$v) { continue; }
                $url.='&'.$k.'='.$v;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        return $this->output($response, $this->conn['settings']['format']);
    }

    protected function output($res,$format) {
        $format=mb_strtolower($format);
        switch($format) {
            case 'array':
                return (array) json_decode($res, true);
            break;

            case 'object':
                return (object) json_decode($res);
            break;

            default:
                return $res;
        }
    }


}
