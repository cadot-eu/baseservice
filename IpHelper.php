<?php

namespace App\Service\base;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpHelper
{
    private $httpClient;
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    static public function getUserIP()
    {

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
        }
    }
    public function getIp()
    {

        return $this->httpClient->request('GET', 'https://ipecho.net/plain')->getContent();
    }
    public function getInformations()
    {
        $response = $this->httpClient->request('GET', 'http://ip-api.com/json/')->getContent();
        return json_decode($response, true);
    }
}
