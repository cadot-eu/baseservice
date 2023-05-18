<?php

namespace App\Service\base;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpHelper
{
    private $httpClient;
    /**
     * The constructor function is used to create a new instance of the class
     *
     * @param HttpClientInterface httpClient This is the HttpClientInterface that we will use to make
     * requests to the API.
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /* Getting the user IP address. */
    public static function getUserIP()
    {

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
        }
    }
    /**
     * It returns the IP address of the server
     *
     * @return The IP address of the server.
     */
    public function getIp()
    {
        //get real ip
        return $this->httpClient->request('GET', 'https://ipecho.net/plain')->getContent();
    }
    /**
     * It makes a GET request to the ip-api.com API and returns the response as an array
     *
     * @return An array of information about the user's IP address.
     */
    public function getInformations()
    {
        $ip = $this->getIp();
        $response = $this->httpClient->request('GET', 'http://ip-api.com/json/' . $ip)->toArray();
        return $response;
    }
}
