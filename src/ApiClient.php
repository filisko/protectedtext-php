<?php
namespace Filisko\ProtectedText;

use GuzzleHttp\ClientInterface;
use Filisko\ProtectedText\Site;

class ApiClient
{
    const BASE_URL = 'https://www.protectedtext.com/';
    
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Undocumented function
     *
     * @param string $site
     * @return Site
     */
    public function get($name)
    {
        $response = $this->client->get("{$name}?action=getJSON");
        $json = json_decode($response->getBody()->getContents());
        
        return new Site(
            $name,
            $json->eContent,
            $json->isNew,
            $json->currentDBVersion,
            $json->expectedDBVersion
        );
    }

    public function create(Site $site)
    {
        return $this->update($site);
    }

    public function update(Site $site)
    {
        if (!$site->getPassword()) throw new \Exception('Site must have a password');

        if (!$site->hasEncryptedContent()) {
            throw new \Exception('encrypted content can not be empty');
        }

        $response = $this->client->post($site->getName(), [
            'form_params' => [
                'action'             => 'save',
                'currentHashContent' => $site->getCurrentHashContent(),
                'encryptedContent'   => $site->getEncryptedContent(),
                'initHashContent'    => $site->getInitHashContent()
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function delete(Site $site)
    {
        $response = $this->client->post($site->getName(), [
            'form_params' => [
                'action'             => 'delete',
                'initHashContent'    => $site->getInitHashContent()
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }
}
