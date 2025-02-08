# ProtectedText client for PHP

https://www.protectedtext.com/

<!-- ![ProtectedText flow](https://www.protectedtext.com/img/logo.png) -->

```php
use Filisko\ProtectedText\ApiClientFactory;
use Filisko\ProtectedText\SiteFactory;

$apiClient = ApiClientFactory::create();

$site = $apiClient->get('existent_site');
$site->decrypt('password');

$tabs = $site->getTabs();
$tabs[0] = 'tab content 1';

$site->updateTabs($tabs);

// update
$apiClient->update($site);

// delete
$apiClient->delete($site);

// create new one
$newSite = SiteFactory::create('my_new_site', 'my_secure_password', ['my first tab content']);
$apiClient->create($newSite);
```
