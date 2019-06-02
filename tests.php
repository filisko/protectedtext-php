<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Filisko\ProtectedText\ApiClient;
use Filisko\ProtectedText\Repository;


$client = new Client([
    'base_uri' => 'https://www.protectedtext.com/',
    'timeout'  => 3,
    'allow_redirects' => true,
    'verify' => false
]);

$apiClient = new ApiClient($client);

$site = $apiClient->get('phptest');

// $site->decrypt(123123);

dd($site->getCurrentHashContent());

// $site->setPassword(1);
// $tabs = $site->getTabs();
// $tabs[0] = 'asdaassd3';
// $site->updateTabs($tabs);

// dd($site->getMetadata());

// // $apiClient->create($site);

// // sleep(1);
// dd($apiClient->update($site));
// dd($apiClient->delete($site));


// dd($site);

// // if (!$site->exists()) {
//     // $site->setPassword(123123);
    



    
//     // dd($site);
//     dump($apiClient->create($site));

// // }


// $site->decrypt(123123);
// dd($site);

// dd($site);
// $metadata = $site->getMetadata();
// dd($metadata);
// $metadata['title'] = 'holaasd?';
// $metadata['hola'] = 'loCl';
// $site->setMetadata($metadata);

;




// use Filisko\ProtectedText\ProtectedText;
// use Filisko\ProtectedText\Repository;

// $pad = ProtectedText::open('filistest2', 123123);
// $pad->updateTab(0, 'xd');


// var_dump($pad->save());

