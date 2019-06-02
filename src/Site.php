<?php
namespace Filisko\ProtectedText;

use Blocktrail\CryptoJSAES\CryptoJSAES;
use Filisko\ProtectedText\Helper;
use Filisko\ProtectedText\Exceptions\DecryptionFailed;
use Filisko\ProtectedText\Exceptions\DecryptionNeeded;

class Site
{
    const TAB_SEPARATOR_STRING = '-- tab separator --';
    const APP_METADATA_STRING = '♻ Reload this website to hide mobile app metadata! ♻';

    /**
     * Name.
     * 
     * @var string
     */
    private $name;

    /**
     * Password.
     * 
     * @var string
     */
    private $password;

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
     * Whether the pad is new or not.
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
    
    /**
     * Whether is decrypted or not.
     *
     * @var boolean
     */
    private $decryptedContent;


    public function __construct(
        $name, $encryptedContent, $isNew, $currentDBVersion, $expectedDBVersion
    ) {
        $this->name = $name;
        $this->encryptedContent = $encryptedContent;
        $this->isNew = $isNew;
        $this->currentDBVersion = $currentDBVersion;
        $this->expectedDBVersion = $expectedDBVersion;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEncryptedContent()
    {
        return $this->encryptedContent;
    }

    protected function getDecryptedContent()
    {
        if (!$this->isDecrypted()) throw new DecryptionNeeded('Decrypt this site first to get decrypted content');

        return $this->decryptedContent;
    }

    public function exists()
    {
        return !$this->isNew;
    }

    public function getInitHashContent()
    {
        if (!$this->isDecrypted()) throw new DecryptionNeeded('Decrypt this site first to get initHashContent');

        return $this->initHashContent;
    }

    public function getCurrentHashContent()
    {
        return self::hashContent($this->getDecryptedContent(), $this->password, $this->expectedDBVersion);
    }

    protected function getTabSeparatorHash()
    {
        return hash('sha512', self::TAB_SEPARATOR_STRING);
    }

    protected function getSiteHash()
    {
        return hash('sha512', '/'.$this->name);
    }

    protected static function hashContent($content, $password, $version)
    {
        return hash('sha512', $content . hash('sha512', $password)) . $version;
        // if ($this->currentDBVersion === 1) return hash('sha512', $content);
    }

    public function setPassword($password)
    {
        if (!$password) throw new \Exception('Password can not be empty');

        $this->password = $password;
        
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function decrypt($password)
    {
        if (!$this->exists()) throw new DecryptionFailed('This site needs to be created first.');

        $content = Helper::decrypt($this->encryptedContent, $password);

        if (!$content) throw new DecryptionFailed('Content could not be decrypted.');

        // it's necessary to remove the site hash from the end of decrypted content
        $content = Helper::removeStringFromEnd($this->getSiteHash(), $content);

        // this is needed to make the save, update or delete request
        $this->initHashContent = self::hashContent($content, $password, $this->expectedDBVersion);
        $this->decryptedContent = $content;
        $this->password = $password;

        return $this;
    }

    public function isDecrypted()
    {
        return $this->initHashContent !== null && $this->decryptedContent !== null && $this->password !== null || $this->isNew;
    }

    public function hasContent()
    {
        return (bool)$this->encryptedContent;
    }

    public function hasMetadata()
    {
        return strpos($this->decryptedContent, self::APP_METADATA_STRING) !== false;
    }

    protected function getRawMetadata()
    {
        if ($this->hasMetadata()) {
            $rawTabs = $this->getRawTabs();
            return end($rawTabs);
        }

        return null;
    }

    public function getMetadata()
    {
        $metadataTab = $this->getRawMetadata();
        if (!$metadataTab) return null;

        $json = json_decode(str_replace(self::APP_METADATA_STRING, '', $metadataTab), true);
        return $json;
    }

    public function setMetadata(array $metadata)
    {
        $tabs = $this->getRawTabs();
        end($tabs);
        $key = key($tabs);

        $tabs[$key] = self::APP_METADATA_STRING . json_encode($metadata);
        
        return $this->updateTabs($tabs, true);
    }

    /**
     * It will also include the 'virtual' metadata tab
     *
     * @return array
     */
    protected function getRawTabs()
    {
        if (!$this->isDecrypted()) throw new \Exception('Decrypt this site first');

        return explode($this->getTabSeparatorHash(), $this->decryptedContent);
    }

    /**
     * Get site tabs.
     *
     * @param boolean $excludeMetadata
     * @return array
     */
    public function getTabs()
    {
        $rawTabs = $this->getRawTabs();
        
        // exclude metadata
        if ($this->hasMetadata()) {
            end($rawTabs); 
            $lastPosition = key($rawTabs);
            unset($rawTabs[$lastPosition]);
        }

        return $rawTabs;
    }

    public function updateTabs(array $tabs, $tabsComeWithMetadata = false)
    {
        // keep metadata (if there is any) on the content
        if (!$tabsComeWithMetadata && $this->hasMetadata()) {
            $tabs[] = $this->getRawMetadata();
        }

        $this->decryptedContent = implode($this->getTabSeparatorHash(), $tabs);
        $this->encryptedContent = Helper::encrypt("{$this->decryptedContent}{$this->getSiteHash()}", $this->password);

        return $this;
    }
}
