<?php

namespace App\Services;

class MoreThanPanelService
{
    public $api_url = 'https://morethanpanel.com/api/v2';
    public $api_key = '';

    public function order($data) {
        return json_decode((string) $this->connect(array_merge(['key'=>$this->api_key, 'action'=>'add'], $data)));
    }

    public function status($order_id) {
        return json_decode($this->connect(['key'=>$this->api_key, 'action'=>'status', 'order'=>$order_id]));
    }

    public function services() {
        return json_decode($this->connect(['key'=>$this->api_key, 'action'=>'services']));
    }

    public function balance() {
        return json_decode($this->connect(['key'=>$this->api_key, 'action'=>'balance']));
    }

    private function connect($post)
    {
        $fields = [];
        foreach ($post as $k => $v) {
            $fields[] = $k.'='.urlencode($v);
        }

        $ch = curl_init($this->api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => join('&', $fields),
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $result = curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);

        return $error === 0 && is_string($result) ? $result : null;
    }
}
