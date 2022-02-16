# Laravel Sail for Lumen
<p>
    <a href="https://packagist.org/packages/yackers/lumensail">
        <img src="https://img.shields.io/packagist/v/yackers/lumensail" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/yackers/lumensail">
        <img src="https://img.shields.io/packagist/l/yackers/lumensail" alt="License">
    </a>
</p>
Laravel Sail for Lumen Framework


#### Install package:

`composer require --dev yackers/lumensail`

Register Provider - add line to bootstrap/app.php

`$app->register(Yackers\LumenSail\LumenSailServiceProvider::class);`

Run command:

`php artisan sail:install`