<?php

namespace Alpaca;

use GuzzleHttp\Client;

class Alpaca
{
    /**
     * The Guzzle instance used for all requests to the Alpaca API
     *
     * @var \GuzzleHttp\Client
     */
    public $client;

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
    protected function _buildUrl($path = "", $queryStrings = [], $version = "v1")
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

        if ($this->paper === true) {
            $domain = "https://paper-api.alpaca.markets";
        } else {
            $domain = "https://api.alpaca.markets";
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
     *
     * @return Response
     */
    protected function _request($path, $queryString = [], $type = "GET", $body = null)
    {
        try {
            $request = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'APCA-API-KEY-ID' => "{$this->key}",
                    'APCA-API-SECRET-KEY' => "{$this->secret}",
                ]
            ];

            if (is_array($body)) {
                $request['body'] = json_encode($body);
            } else if (!empty($body)) {
                $request['body'] = $body;
            }

            $response = $this->client->request($type, $this->_buildUrl($path, $queryString), $request);

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
     *
     * @return Response
     */
    public function getOrders($status = null, $limit = null, $after = null, $until = null, $direction = null)
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
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#get-an-order-by-client-order-id
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
     * Create a new order
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#request-a-new-order
     *
     * @param string $symbol
     * @param int $qty
     * @param string $side 'buy' or 'sell'
     * @param string $type 'market', 'limit', 'stop', 'stop_limit'
     * @param string $time_in_force 'day', 'gtc', 'opg'
     * @param double $limit_price Required if type is 'limit' or 'stop_limit'
     * @param double $stop_price Required if type is 'stop' or 'stop_limit'
     * @param string $client_order_id Max 48 chars
     *
     * @return Response
     */
    public function createOrder($symbol, $qty, $side, $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null)
    {
        $body = [
            'symbol'        => $symbol,
            'qty'           => $qty,
            'side'          => $side,
            'type'          => $type,
            'time_in_force' => $time_in_force
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
     * Undocumented function
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