# ProtectedText client for PHP

https://www.protectedtext.com/

<!-- ![ProtectedText flow](https://www.protectedtext.com/img/image.png) -->

```php
use Filisko\ProtectedText\ApiClient;

$apiClient = new ApiClient();

$site = $apiClient->get('phptest');
$site->decrypt('password');

$tabs = $site->getTabs();
$tabs[0] = 'tab content 1';

$site->updateTabs($tabs);

// update
$apiClient->update($site);

// delete
$apiClient->delete($site);

// create new one
$newSite = $apiClient->get('phptest2');
$newSite->setPassword('my_secure_password');
$tabs = ['my first tab content'];
$newSite->updateTabs($tabs);

$apiClient->create($newSite);
```