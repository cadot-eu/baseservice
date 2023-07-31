<?php

namespace App\Service\base;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

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
    public function getIpAsynchrone()
    {
        // get real ip asynchronously
    $client = new Client();
    $url = 'https://ipecho.net/plain';

    $promise = $client->getAsync($url);
    $response = $promise->wait();

    if ($response->getStatusCode() !== 200) {
        // Gérer les erreurs de requête ici
        return new Response('Erreur de requête externe', 500);
    }

    return new Response($response->getBody()->getContents());
    }
//get ip synchrone
    public function getIp()
    {
        $ip = $this->httpClient->request('GET', 'https://ipecho.net/plain')->getContent();
        
        return $ip;
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
