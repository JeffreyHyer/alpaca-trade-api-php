<?php

namespace Alpaca;

use Carbon\Carbon;
use GuzzleHttp\Client;

class Alpaca
{
    /**
     * The Guzzle instance used for all requests to the Alpaca API
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Key ID
     *
     * @var string
     */
    private $key;

    /**
     * Secret Key
     *
     * @var string
     */
    private $secret;

    /**
     * Whether or not to use the paper trading endpoint
     * or the live/production endpoint.
     *
     * @var bool
     */
    private $paper;

    /**
     * Alpaca API constructor
     *
     * @param string $domain    The company name/subdomain in BambooHR
     * @param string $token     The API Token to use when sending requests
     * @param array  $options   An array of options ['base_uri', 'version'] to override the defaults
     *
     * @return void
     */
    public function __construct($key = "", $secret = "", $paper = true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->paper = $paper;

        $this->client = new Client();
    }

    /**
     * Undocumented function
     *
     * @param string $key
     *
     * @return void
     */
    public function setKey($key = "")
    {
        $this->key = $key;
    }

    /**
     * Undocumented function
     *
     * @param string $secret
     *
     * @return void
     */
    public function setSecret($secret = "")
    {
        $this->secret = $secret;
    }

    /**
     * Undocumented function
     *
     * @param boolean $paper
     *
     * @return void
     */
    public function setPaper($paper = true)
    {
        $this->paper = $paper;
    }

    /**
     * [_buildUrl description]
     *
     * @param  string $path         [description]
     * @param  array  $queryStrings [description]
     *
     * @return string               [description]
     */
    private function _buildUrl($path = "", $queryStrings = [], $domain = null, $version = "v2")
    {
        $queryString = "";

        foreach ($queryStrings as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $queryString .= "&{$key}={$value}";
        }

        if (strlen($queryString) > 0) {
            $queryString = "?" . substr($queryString, 1);
        }

        if (is_null($domain)) {
            if ($this->paper === true) {
                $domain = "https://paper-api.alpaca.markets";
            } else {
                $domain = "https://api.alpaca.markets";
            }
        }

        $path = trim($path, "/");

        return "{$domain}/{$version}/{$path}{$queryString}";
    }

    /**
     * Undocumented function
     *
     * @param string $path
     * @param array $queryString
     * @param string $type
     * @param mixed $body
     * @param string $domain
     *
     * @return Response
     */
    private function _request($path, $queryString = [], $type = "GET", $body = null, $domain = null)
    {
        try {
            $request = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'APCA-API-KEY-ID' => "{$this->key}",
                    'APCA-API-SECRET-KEY' => "{$this->secret}",
                ],
            ];

            if (is_array($body)) {
                $request['body'] = json_encode($body);
            } elseif (!empty($body)) {
                $request['body'] = $body;
            }

            $response = $this->client->request($type, $this->_buildUrl($path, $queryString, $domain), $request);

            return new Response($response);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            if ($e->hasResponse()) {
                return new Response($e->getResponse());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/account/#get-the-account
     *
     * @return Response
     */
    public function getAccount()
    {
        return $this->_request("account");
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#get-a-list-of-orders
     *
     * @param string $status 'open', 'closed', 'all'
     * @param int $limit Max 500, default 50
     * @param string $after
     * @param string $until
     * @param string $direction 'asc', 'desc'
     * @param boolean $nested
     *
     * @return Response
     */
    public function getOrders($status = null, $limit = null, $after = null, $until = null, $direction = null, $nested = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs['status'] = $status;
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($after)) {
            $qs['after'] = $after;
        }

        if (!is_null($until)) {
            $qs['until'] = $until;
        }

        if (!is_null($direction)) {
            $qs['direction'] = $direction;
        }

        if (!is_null($nested)) {
            $qs['nested'] = $nested;
        }

        return $this->_request("orders", $qs);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#get-an-order
     *
     * @param string $order_id
     *
     * @return Response
     */
    public function getOrder($order_id)
    {
        return $this->_request("orders/{$order_id}");
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/api-v2/orders/#get-an-order-by-client-order-id
     *
     * @param string $client_order_id
     *
     * @return Response
     */
    public function getOrderByClientId($client_order_id)
    {
        return $this->_request("orders:by_client_order_id", ['client_order_id' => $client_order_id]);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/api-v2/orders/#replace-an-order
     *
     * @param string $order_id
     * @param int $qty
     * @param string $time_in_force
     * @param double $limit_price
     * @param double $stop_price
     * @param string $client_order_id
     *
     * @return Response
     */
    public function replaceOrder($order_id, $qty, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null)
    {
        $body = [
            'qty' => $qty,
            'time_in_force' => $time_in_force,
        ];

        if (!is_null($limit_price)) {
            $body['limit_price'] = $limit_price;
        }

        if (!is_null($stop_price)) {
            $body['stop_price'] = $stop_price;
        }

        if (!is_null($client_order_id)) {
            $body['client_order_id'] = $client_order_id;
        }

        return $this->_request("orders/{$order_id}", [], "PATCH", $body);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#cancel-an-order
     *
     * @param string $order_id
     *
     * @return Response
     */
    public function cancelOrder($order_id)
    {
        return $this->_request("orders/{$order_id}", [], "DELETE");
    }

    /**
     * Cancel all orders
     * 
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#cancel-all-orders
     * 
     * @return Response
     */
    public function cancelAllOrders()
    {
        return $this->_request("orders", [], "DELETE");
    }

    /**
     * Create a new order
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#request-a-new-order
     *
     * @param string $symbol
     * @param int $qty
     * @param string $side 'buy' or 'sell'
     * @param string $type 'market', 'limit', 'stop', 'stop_limit'
     * @param string $time_in_force 'day', 'gtc', 'opg', 'cls', 'ioc', 'fok'
     * @param double $limit_price Required if type is 'limit' or 'stop_limit'
     * @param double $stop_price Required if type is 'stop' or 'stop_limit'
     * @param string $client_order_id Max 48 chars
     * @param boolean $extended_hours default: false
     *
     * @return Response
     */
    public function createOrder($symbol, $qty, $side, $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null, $extended_hours = null)
    {
        $body = [
            'symbol' => $symbol,
            'qty' => $qty,
            'side' => $side,
            'type' => $type,
            'time_in_force' => $time_in_force,
        ];

        if (!is_null($limit_price)) {
            $body['limit_price'] = $limit_price;
        }

        if (!is_null($stop_price)) {
            $body['stop_price'] = $stop_price;
        }

        if (!is_null($client_order_id)) {
            $body['client_order_id'] = $client_order_id;
        }

        if (!is_null($extended_hours)) {
            $body['extended_hours'] = $extended_hours;
        }

        return $this->_request("orders", [], "POST", $body);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/positions/#get-open-positions
     *
     * @return Response
     */
    public function getPositions()
    {
        return $this->_request("positions");
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/positions/#get-an-open-position
     *
     * @param string $symbol
     *
     * @return Response
     */
    public function getPosition($symbol)
    {
        return $this->_request("positions/{$symbol}");
    }
    
    /**
     * Close all positions
     * 
     * @link https://docs.alpaca.markets/api-documentation/api-v2/positions/#close-all-positions
     *
     * @return Response
     */
    public function closeAllPositions()
    {
        return $this->_request("positions", [], "DELETE");
    }

    /**
     * Close a position
     * 
     * @link https://docs.alpaca.markets/api-documentation/api-v2/positions/#close-a-position
     *
     * @param  string $symbol
     *
     * @return Response
     */
    public function closePosition($symbol)
    {
        return $this->_request("positions/{$symbol}", [], "DELETE");
    }

    /**
     * Get assets
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/assets/#get-assets
     *
     * @param string $status 'active', etc.
     * @param string $asset_class 'us_equity', etc.
     *
     * @return Response
     */
    public function getAssets($status = null, $asset_class = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs['status'] = $status;
        }

        if (!is_null($asset_class)) {
            $qs['asset_class'] = $asset_class;
        }

        return $this->_request("assets", $qs);
    }
    
    /**
     * Get an asset by ID
     *
     * @link https://docs.alpaca.markets/api-documentation/api-v2/assets/#get-assets/:id
     *
     * @param  string $id
     *
     * @return Response
     */
    public function getAssetById($id)
    {
        return $this->_request("assets/{$id}");
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/assets/#get-an-asset
     *
     * @param string $symbol Symbol or asset_id
     *
     * @return Response
     */
    public function getAsset($symbol)
    {
        return $this->_request("assets/{$symbol}");
    }
    
    /**
     * Get a list of watchlists
     * 
     * @link https://docs.alpaca.markets/api-documentation/api-v2/watchlist/#get-a-list-of-watchlists
     *
     * @return Response
     */
    public function getWatchlists()
    {
        return $this->_request("watchlists");
    }
    
    /**
     * Create a new watchlist with an initial set of assets
     *
     * @link https://docs.alpaca.markets/api-documentation/api-v2/watchlist/#create-a-watchlist
     *
     * @param  string $name
     * @param  string[] $symbols
     *
     * @return Response
     */
    public function createWatchlist($name, $symbols = [])
    {
        $body = [
            "name" => $name,
            "symbols" => $symbols,
        ];

        return $this->_request("watchlists", [], "POST", $body);
    }
    
    /**
     * Get a watchlist
     * 
     * @link https://docs.alpaca.markets/api-documentation/api-v2/watchlist/#get-a-watchlist
     *
     * @param  string $id
     *
     * @return Response
     */
    public function getWatchlist($id)
    {
        return $this->_request("watchlists/{$id}");
    }

    /**
     * Get a watchlist by name
     * 
     * @link https://docs.alpaca.markets/api-documentation/api-v2/watchlist/#endpoints-for-watchlist-name
     *
     * @param  string $name
     *
     * @return Response
     */
    public function getWatchlistByName($name)
    {
        $qs = [
            'name' => $name
        ];

        return $this->_request("watchlists:by_name", $qs);
    }
    
    /**
     * Update a watchlist by replacing it's contents/symbols.
     *
     * @param  string $id
     * @param  string $name
     * @param  string[] $symbols
     *
     * @return Response
     */
    public function updateWatchlist($id, $name, $symbols = [])
    {
        $body = [
            "name" => $name,
            "symbols" => $symbols,
        ];

        $this->_request("watchlists/{$id}", [], "PUT", $body);
    }
    
    /**
     * Update a watchlist by name.
     *
     * @param  string $name
     * @param  string[] $symbols
     *
     * @return Response
     */
    public function updateWatchlistByName($name, $symbols = [])
    {
        $qs = [
            "name" => $name
        ];

        $body = [
            "name" => $name,
            "symbols" => $symbols,
        ];

        return $this->_request("watchlists:by_name", $qs, "PUT", $body);
    }
    
    /**
     * Add an asset to a watchlist
     *
     * @param  string $id
     * @param  string $symbol
     *
     * @return Response
     */
    public function addAssetToWatchlist($id, $symbol)
    {
        $body = ["symbol" => $symbol];

        return $this->_request("watchlists/{$id}", [], "POST", $body);
    }
    
    /**
     * Add an asset to a watchlist by name.
     *
     * @param  string $name
     * @param  string $symbol
     *
     * @return Response
     */
    public function addAssetToWatchlistByName($name, $symbol)
    {
        $qs = ["name" => $name];
        $body = ["symbol" => $symbol];

        return $this->_request("watchlists:by_name", $qs, "POST", $body);
    }
    
    /**
     * Delete a watchlist
     * 
     * @param string $id
     *
     * @return Response
     */
    public function deleteWatchlist($id)
    {
        return $this->_request("watchlists/{$id}", [], "DELETE");
    }

    /**
     * Delete a watchlist by name
     * 
     * @param string $name
     * 
     * @return Response
     */
    public function deleteWatchlistByName($name)
    {
        $qs = ["name" => $name];

        return $this->_request("watchlists:by_name", $qs, "DELETE");
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/calendar/#get-the-calendar
     *
     * @param string $start
     * @param string $end
     *
     * @return Response
     */
    public function getCalendar($start = null, $end = null)
    {
        $qs = [];

        if (!is_null($start)) {
            $qs['start'] = (new Carbon($start))->format('Y-m-d');
        }

        if (!is_null($end)) {
            $qs['end'] = (new Carbon($end))->format('Y-m-d');
        }

        return $this->_request("calendar", $qs);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/clock/#get-the-clock
     *
     * @return void
     */
    public function getClock()
    {
        return $this->_request("clock");
    }
    
    /**
     * Get account configurations
     *
     * @return Response
     */
    public function getAccountConfigurations()
    {
        return $this->_request("account/configurations");
    }
    
    /**
     * Update account configurations
     *
     * @param  array $config ['key' => 'value']
     *
     * @return Response
     */
    public function updateAccountConfigurations($config = [])
    {
        return $this->_request("account/configurations", [], "PATCH", $config);
    }

    /**
     * Undocumented function
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/market-data/bars/#get-a-list-of-bars
     *
     * @param string        $timeframe  One of: 'minute', '1Min', '5Min', '15Min', 'day', '1D'.
     * @param string|array  $symbols    One or more (max 200) symbol names.
     * @param int           $limit      The maximum number of bars to be returned for each symbol. Max = 1000. Default = 100.
     * @param string        $start      Filter bars equal to or after this time. Cannot be used with 'after'.
     * @param string        $end        Filter bars equal to or before this time. Cannot be used with 'until'.
     * @param string        $after      Filter bars after this time. Cannot be used with 'start'.
     * @param string        $until      Filter bars before this time. Cannot be used with 'end'.
     *
     * @return Response
     */
    public function getBars($timeframe, $symbols, $limit = null, $start = null, $end = null, $after = null, $until = null)
    {
        $qs = [];

        if (is_array($symbols)) {
            $qs['symbols'] = implode(",", $symbols);
        } else {
            $qs['symbols'] = $symbols;
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($start)) {
            $qs['start'] = $start;
        }

        if (!is_null($end)) {
            $qs['end'] = $end;
        }

        if (!is_null($after)) {
            $qs['after'] = $after;
        }

        if (!is_null($until)) {
            $qs['until'] = $until;
        }

        return $this->_request("bars/{$timeframe}", $qs, "GET", null, "https://data.alpaca.markets");
    }

    /**
     * [__call description]
     *
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     *
     * @return [type]         [description]
     */
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \Exception("Unknown method: {$method}");
    }

    /**
     * [__get description]
     *
     * @param  [type] $property [description]
     *
     * @return [type]           [description]
     */
    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->$property();
        }

        throw new \Exception("Unknown property: {$property}");
    }
}
