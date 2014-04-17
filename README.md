PHP API for WebtranslateIt.com service
=================

Installation:

Usage:
```php
$apiKey = 'YOUR_API_KEY';
$api = new WtiApi($apiKey);

// Add translator to project with proofreader rights
// https://webtranslateit.com/en/docs/api/user/#create-user
$api->addUser('email@email.com', 'ru', true);

// Add manager to project with proofreader rights
// https://webtranslateit.com/en/docs/api/user/#create-user
$api->addUser('email@email.com', 'ru', true, 'manager');


// Search strings by key in file
// https://webtranslateit.com/en/docs/api/string/#list-string
$strings = $api->getStringsByKey('someKey', 'FILE_ID');

// Get Top Translators
// https://webtranslateit.com/en/docs/api/stats/#top-translators
$topTranslators = $api->getTopTranslators();
```
