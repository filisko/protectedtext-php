<?php
namespace Filisko\ProtectedText;

class SiteFactory
{
    const TAB_SEPARATOR_STRING = '-- tab separator --';
    
    public static function create($name, $password, array $tabs)
    {
        $siteHash = hash('sha512', '/'.$name);
        $decryptedContent = implode(self::getTabSeparatorHash(), $tabs);
        $encryptedContent = Helper::encrypt("{$decryptedContent}{$siteHash}", $password);
        
        $isNew = true;
        $currentDBVersion = 2;
        $expectedDBVersion = 2;

        $site = new Site($name, $encryptedContent, $isNew, $currentDBVersion, $expectedDBVersion);
        $site->setPassword($password);
        return $site;
    }

    protected static function getTabSeparatorHash()
    {
        return hash('sha512', self::TAB_SEPARATOR_STRING);
    }
}
