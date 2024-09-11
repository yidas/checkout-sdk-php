Checkout SDK Tool
=================

<img src="https://raw.githubusercontent.com/yidas/checkout-sdk-php/master/img/tool-index-desktop.png" height="500" /><img src="https://raw.githubusercontent.com/yidas/checkout-sdk-php/master/img/tool-index-mobile.png" height="500" />

FEATURES
--------

*1. **No database** required.*

*2. **Saving config with authentication** by session for payment processes and next order.*

*3. **View Logs, Merchants Setting** feature.*

---

INSTALLATION
------------

Download repository and run Composer install in your Web directory: 

```
git clone https://github.com/yidas/checkout-sdk-php.git;
cd checkout-sdk-php;
composer install;
```

Then you can access the sample site from `https://{yourweb-dir}/checkout-sdk-php/tool`.



---

MERCHANTS SETTING
-----------------

You can save your favorite or test LINE Pay merchant account for display and selection on the sample page.

To enable the setting, create `tool/_merchants.php` file (Under `tool` folder) using the following PHP array format:

```php
<?php

return [
    [
        "title" => "My Key",
        "keyId" => "1788DDA01B395A8F639E4B3FC2AFA96E",
        "apiSecretKey" => "sk_sbox_8aleoahndbseskriirsgiehohon",
    ],
    [
        "keyId" => "7798DAA03A359A7E399F4B3FC1AEA25A",
        "apiSecretKey" => "sk_sbox_bseskrindasegkrvirsqiehohbo",
    ],
    [
        "title" => "My Key 3",
        "apiSecretKey" => "sk_sbox_oahnoahndsqiekrvirsqiehndbs",
    ],
];

```

> Warning: Saved merchants will be operated by the tool through the API with the merchant credentials, please add the sandbox merchant account only.




