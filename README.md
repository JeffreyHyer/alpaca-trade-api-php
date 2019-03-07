# Alpaca PHP SDK

This repository contains a PHP SDK for use with the [Alpaca](https://alpaca.markets?ref_by=858915e73e) API.

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

$alpaca = new Alpaca("--KEY--", "--SECRET--");

$positions = $alpaca->getPositions();
```

## API

### Constructor

To get started initialize the `Alpaca` class with your Alpaca Key and Secret. You can also set the third parameter to enable or disable paper trading. The default is `true` to enable calling against the paper trading endpoint.

```php
use Alpaca\Alpaca;

$alpaca = new Alpaca("KEY", "SECRET", true);

// This call will now work as expected if your KEY and SECRET are valid.
// If not, the response will contain an error explaining what went wrong.
$resp = $alpaca->getAccount();
```

You can change these values after initialization if necessary using the following methods:

#### `setKey($key)`

Set your Alpaca Key

#### `setSecret($secret)`

Set your Alpaca Secret

#### `setPaper(true)`

Enable or disable paper trading. `true` = Paper Trading, `false` = Live Trading.


### Response

All methods return an instance of the `\Alpaca\Response` class which has a number of convenient methods for working with the API response.

#### `getCode()`

Returns the HTTP status code of the request (e.g. `200`, `403`, etc).

#### `getReason()`

Returns the HTTP status reason of the request (e.g. `OK`, `Forbidden`, etc).

#### `getResponse()`

Returns the JSON decoded response. For example:

```php
print_r($alpaca->getAccount()->getResponse());

/*
Results in:

stdClass Object
(
    [id] => null
    [status] => ACTIVE
    [currency] => USD
    [buying_power] => 25000
    [cash] => 25000
    [cash_withdrawable] => 0
    [portfolio_value] => 25000
    [pattern_day_trader] => 
    [trading_blocked] => 
    [transfers_blocked] => 
    [account_blocked] => 
    [created_at] => 2018-11-01T18:41:35.990779Z
    [trade_suspended_by_user] => 
)
*/
```


### Account

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/account/)

`getAccount()`

Returns the account associated with the API key.


### Orders

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/orders/)

`getOrders($status = null, $limit = null, $after = null, $until = null, $direction = null)`

Retrieves a list of orders for the account, filtered by the supplied query parameters.

`createOrder($symbol, $qty, $side, $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null)`

Places a new order for the given account. An order request may be rejected if the account is not authorized for trading, or if the tradable balance is insufficient to fill the order.

`getOrder($order_id)`

Retrieves a single order for the given `$order_id`.

`getOrderByClientId($client_order_id)`

Retrieves a single order for the given `$client_order_id`.

`cancelOrder($order_id)`

Attempts to cancel an open order. If the order is no longer cancelable (example: `status=order_filled`), the server will respond with status 422, and reject the request.


### Positions

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/positions/)

`getPositions()`

Retrieves a list of the account's open positions.

`getPosition($symbol)`

Retrieves the account's open position for the given `$symbol`.


### Assets

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/assets/)

`getAssets($status = null, $asset_class = null)`

Get a list of assets

`getAsset($symbol)`

Get an asset for the given `symbol`.


### Calendar

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/calendar/)

`getCalendar($start = null, $end = null)`

Returns the market calendar.


### Clock

[:ledger: Alpaca Docs](https://docs.alpaca.markets/api-documentation/web-api/clock/)

`getClock()`

Returns the market clock.


### Market Data

`getBars($timeframe, $symbols, $limit = null, $start = null, $end = null, $after = null, $until = null)`

Retrieves a list of bars for each requested symbol. It is guaranteed all bars are in ascending order by time. Currently, no “incomplete” bars are returned. For example, a 1 minute bar for 09:30 will not be returned until 09:31.