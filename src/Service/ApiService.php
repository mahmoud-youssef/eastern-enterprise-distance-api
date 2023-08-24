<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function sendRequest(string $method, string $url, array $headers = [], $body = null): ResponseInterface
    {
        try {
            $options = [
                'headers' => $headers,
            ];

            if ($body !== null) {
                $options['body'] = $body;
            }

            return $this->httpClient->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            // Handle any transport exception
            // For example, log the error or throw a custom exception
            // ...
        }
        return $this->httpClient->request($method, $url, $options);
    }
}