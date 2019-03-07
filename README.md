# Alpaca PHP SDK

This repository contains a PHP SDK for use with the [Alpaca](https://alpaca.markets/) API.

**DISCLAIMER:** This is **NOT** an official SDK, it is not affiliated with nor endorsed by Alpaca in any way.

## Installation

> __NOTE__: This package currently requires PHP >= 7.0.0
>
> If you have a need for PHP 5.x support let me know by opening an issue (or feel free to submit a pull request).

#### Via Composer

```shell
$ composer require jeffreyhyer/alpaca-trade-api-php
```

## Usage

From within your PHP application you can access the Alpaca API with just a couple of lines:

```php
<?php

require './vendor/autoload.php';

use Alpaca\Alpaca;

$alpaca = new Alpaca();

$positions = $alpaca->getPositions();

## API

TODO