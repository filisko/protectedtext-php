<?php
namespace Filisko\ProtectedText;

use GuzzleHttp\Client;
use Filisko\ProtectedText\ApiClient;

class ApiClientFactory
{
    const BASE_URL = 'https://www.protectedtext.com/';
    
    public static function create()
    {
        $client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout'  => 5,
            'allow_redirects' => true,
            'verify' => false
        ]);

        return new ApiClient($client);
    }
}
