<?php
namespace Filisko\ProtectedText;

use GuzzleHttp\Client;
use Blocktrail\CryptoJSAES\CryptoJSAES;

class ProtectedText
{
    /**
     * Unique site ID.
     * 
     * @var string
     */
    private $id;

    /**
     * Initial hash of decrypted content, used for testing user's right to save changes and for overwrite protection.
     * 
     * @var string
     */
    private $initHashContent;

    /**
     * Encrypted content received from server.
     * 
     * @var string
     */
    private $encryptedContent;

    /**
     * Decrypted content.
     * 
     * @var string
     */
    private $content;

    /**
     * Password.
     * 
     * @var string
     */
    private $password;

    /**
     * Whether the site is new or not.
     * 
     * @var boolean
     */
    private $isNew;

    /**
     * Database versions are provided to client because: in case hashContent computation is changed.
     * 
     * @var integer
     */
    private $currentDBVersion;

    /**
     * Only client can decrypt the content and compute new hashContent that will be saved on server.
     * 
     * @var integer
     */
    private $expectedDBVersion;

    public function __construct(
        $id, $encryptedContent, $isNew, $currentDBVersion, $expectedDBVersion
    ) {
        $this->id = $id;
        $this->encryptedContent = $encryptedContent;
        $this->isNew = $isNew;
        $this->currentDBVersion = $currentDBVersion;
        $this->expectedDBVersion = $expectedDBVersion;
    }

    public static function getClient()
    {
        $client = new Client([
            'base_uri' => 'https://www.protectedtext.com/',
            'timeout'  => 3,
            'allow_redirects' => true,
            'verify' => false
        ]);

        return $client;
    }

    public static function get($id)
    {
        $client = self::getClient();

        $response = $client->get($id.'?action=getJSON');
        $json = json_decode($response->getBody()->getContents());

        return new self(
            $id,
            $json->eContent,
            $json->isNew,
            $json->currentDBVersion,
            $json->expectedDBVersion
        );
    }

    public static function open($id, $password)
    {
        $pad = self::get($id);

        if (!$pad->exists()) {
            throw new \Exception('Pad does not exist!');
        }

        $pad->decrypt($password);

        return $pad;
    }

    public static function create($id, $password, $content = null)
    {
        $pad = self::get($id);

        if ($pad->exists()) {
            throw new \Exception('Pad already exists!');
        }

        $pad->setPassword($password);

        // auto-save if any content
        if ($content) {
            $pad->addTab($content);
            $pad->save();
        }

        return $pad;
    }

    public static function destroy($id, $password)
    {
        $pad = self::open($id, $password);

        return $pad->delete();

        return $pad;
    }

    public static function hashContent($content, $password, $version)
    {
        if ($version === 1) {
            return hash('sha512', $content);
        } else {
            return hash('sha512', $content . hash('sha512', $password)) . $version;
        }
    }

    /**
     * Helper function to move elements from position to another.
     */
    public static function moveElement(&$array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
    }

    /**
     * Helper function to get content title
     */
    public static function getTitleByContent($content)
    {
        $skuList = preg_split('/\r\n|\r|\n/', $content);
        return $skuList[0];
    }

    public function encrypt()
    {
       $this->encryptedContent = CryptoJSAES::encrypt($this->content . $this->getSiteHash(), $this->password);
    }

    public function decrypt($password)
    {
        $content = CryptoJSAES::decrypt($this->encryptedContent, $password);

        if (!$content) {
            throw new \Exception('Content could not be decrypted');
        }

        $needle = $this->getSiteHash();
        $siteHashLen = strlen($needle);
        if ($siteHashLen === 0 || (substr($content, -$siteHashLen) === $needle)) {
            $content = substr($content, 0, strlen($content) - $siteHashLen);
        }

        $this->initHashContent = self::hashContent($content, $password, $this->expectedDBVersion);
        $this->content = $content;
        $this->password = $password;

        if ($this->hasMetadata()) {
            $this->metadata = 11;
        }

        return $this;
    }

    public function exists()
    {
        return !$this->isNew;
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function save()
    {
        $client = self::getClient();

        $this->encrypt();

        $response = $client->post($this->id, [
            'form_params' => [
                'action'             => 'save',
                'currentHashContent' => $this->getCurrentHashContent(),
                'encryptedContent'   => $this->encryptedContent,
                'initHashContent'    => $this->initHashContent
            ]
        ]);

        return json_decode($response->getBody()->getContents());
        // {"status": "success"}
    }

    public function delete()
    {
        $client = self::getClient();

        $response = $client->post($this->id, [
            'form_params' => [
                'action'             => 'delete',
                'initHashContent'    => $this->initHashContent
            ]
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function updateContent($tabs)
    {
        if ($this->hasMetadata()) {
            $oldTabs = $this->getTabs(false);

            // add metadata
            $metadata = end($oldTabs);
            $tabs[] = $metadata;
        }

        $this->content = implode($this->getTabSeparatorHash(), $tabs);
    }

    public function hasMetadata()
    {
        $needle = '♻ Reload this website to hide mobile app metadata! ♻';
        return strpos($this->content, $needle) !== false;
    }

    public function changeTabPosition($currentPosition, $newPosition)
    {
        $tabs = $this->getTabs();

        self::moveElement($tabs, $currentPosition, $newPosition);

        $this->updateContent($tabs);
    }

    public function getTabs($excludeMetadata = true)
    {
        if ($this->isNew()) {
            return [];
        }

        $separator = $this->getTabSeparatorHash();

        $tabs = explode($separator, $this->content);

        // exclude metadata
        if ($this->hasMetadata() && $excludeMetadata) {
            end($tabs); 
            $lastPosition = key($tabs);
            unset($tabs[$lastPosition]);
        }

        return $tabs;
    }

    public function updateTab($position, $content)
    {
        $tabs = $this->getTabs();

        if (!isset($tabs[$position])) {
            throw new \Exception('Tab position does not exist');
        }

        $tabs[$position] = $content;

        $this->updateContent($tabs);

        return $tabs;
    }

    public function removeTab($position)
    {
        $tabs = $this->getTabs();

        if (!isset($tabs[$position])) {
            throw new \Exception('Tab position does not exist');
        }

        unset($tabs[$position]);

        $this->updateContent($tabs);

        return $tabs;
    }

    public function addTab($content, $position = null)
    {
        $tabs = $this->getTabs();

        $tabs[] = $content;
        
        end($tabs); 
        $lastPosition = key($tabs);

        if ($position !== null) {
            $this->changeTabPosition($lastPosition, $position);
        }

        $this->updateContent($tabs);

        return $tabs;
    }

    public function getTabSeparatorHash()
    {
        return hash('sha512', '-- tab separator --');
    }

    public function getSiteHash()
    {
        return hash('sha512', '/'.$this->id);
    }

    public function getCurrentHashContent()
    {
        return self::hashContent($this->content, $this->password, $this->expectedDBVersion);
    }
}
