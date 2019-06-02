<?php
namespace Filisko\ProtectedText;

class SiteFactory
{
    const TAB_SEPARATOR_STRING = '-- tab separator --';
    
    public static function create($name, $password, $tabs)
    {
        $siteHash = hash('sha512', '/'.$name);
        $decryptedContent = implode(self::getTabSeparatorHash(), $tabs);
        $encryptedContent = Helper::encrypt("{$decryptedContent}{$siteHash}", $password);
        
        $isNew = false;
        $currentDBVersion = 2;
        $expectedDBVersion = 2;

        return new Site($name, $encryptedContent, $isNew, $currentDBVersion, $expectedDBVersion);
    }

    protected static function getTabSeparatorHash()
    {
        return hash('sha512', self::TAB_SEPARATOR_STRING);
    }

    protected function getSiteHash()
    {
        return hash('sha512', '/'.$this->name);
    }
}
