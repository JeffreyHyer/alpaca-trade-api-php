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
     * Access Token. If present, this will be used instead of
     * $key and $secret for authentication.
     *
     * @var string
     */
    private $accessToken;

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
     * @param string $key       The Alpaca account key
     * @param string $secret    The Alpaca account secret key
     * @param boolean $paper    Use the paper trading endpoint (true) or the production endpoint (false)
     *
     * @return void
     */
    public function __construct($key = "", $secret = "", $paper = true, $accessToken = null)
    {
        $this->setKey($key);
        $this->setSecret($secret);
        $this->setPaper($paper);
        $this->setAccessToken($accessToken);

        $this->client = new Client();
    }

    /**
     * Set the account key.
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
     * Set the account secret key.
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
     * Set whether or not to use the paper trading endpoint.
     *
     * @param boolean $paper
     *
     * @return void
     */
    public function setPaper($paper = true)
    {
        $this->paper = $paper;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * Build a request URL from the various parts
     *
     * @param  string $path
     * @param  array  $queryStrings
     *
     * @return string
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

        if (!is_null($version)) {
            $version = "/{$version}/";
        } else {
            $version = "";
        }

        return "{$domain}{$version}{$path}{$queryString}";
    }

    /**
     * Make a request to a specified endpoint path with optional
     * query string parameters, request type (GET, POST, etc)
     * and request content/body.
     *
     * @param string $path
     * @param array $queryString
     * @param string $type
     * @param mixed $body
     * @param string $domain
     *
     * @return Response
     */
    private function _request($path, $queryString = [], $type = "GET", $body = null, $domain = null, $version = "v2")
    {
        try {
            $request = [
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ];

            if (!is_null($this->accessToken)) {
                $request["headers"]["Authorization"] = "Bearer {$this->accessToken}";
            } else {
                $request["headers"]["APCA-API-KEY-ID"] = "{$this->key}";
                $request["headers"]["APCA-API-SECRET-KEY"] = "{$this->secret}";
            }

            if (is_array($body)) {
                $request["body"] = json_encode($body);
            } elseif (!empty($body)) {
                $request["body"] = $body;
            }

            $response = $this->client->request($type, $this->_buildUrl($path, $queryString, $domain, $version), $request);

            return new Response($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                return new Response($e->getResponse());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get the current account.
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
     * Get a list of orders.
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#get-a-list-of-orders
     *
     * @param string $status "open", "closed", "all"
     * @param int $limit Max 500, default 50
     * @param string $after
     * @param string $until
     * @param string $direction "asc", "desc"
     * @param boolean $nested
     *
     * @return Response
     */
    public function getOrders($status = null, $limit = null, $after = null, $until = null, $direction = null, $nested = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs["status"] = $status;
        }

        if (!is_null($limit)) {
            $qs["limit"] = $limit;
        }

        if (!is_null($after)) {
            $qs["after"] = $after;
        }

        if (!is_null($until)) {
            $qs["until"] = $until;
        }

        if (!is_null($direction)) {
            $qs["direction"] = $direction;
        }

        if (!is_null($nested)) {
            $qs["nested"] = $nested;
        }

        return $this->_request("orders", $qs);
    }

    /**
     * Get an order specified by the order ID.
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
     * Get an order specified by the client order ID.
     *
     * @link https://docs.alpaca.markets/api-documentation/api-v2/orders/#get-an-order-by-client-order-id
     *
     * @param string $client_order_id
     *
     * @return Response
     */
    public function getOrderByClientId($client_order_id)
    {
        return $this->_request("orders:by_client_order_id", ["client_order_id" => $client_order_id]);
    }

    /**
     * Replace/update an order with newly specified paramters.
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
            "qty" => $qty,
            "time_in_force" => $time_in_force,
        ];

        if (!is_null($limit_price)) {
            $body["limit_price"] = $limit_price;
        }

        if (!is_null($stop_price)) {
            $body["stop_price"] = $stop_price;
        }

        if (!is_null($client_order_id)) {
            $body["client_order_id"] = $client_order_id;
        }

        return $this->_request("orders/{$order_id}", [], "PATCH", $body);
    }

    /**
     * Cancel an order.
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
     * Cancel all orders.
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
     * Create a new order.
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/orders/#request-a-new-order
     *
     * @param string $symbol
     * @param int $qty
     * @param string $side "buy" or "sell"
     * @param string $type "market", "limit", "stop", "stop_limit"
     * @param string $time_in_force "day", "gtc", "opg", "cls", "ioc", "fok"
     * @param double $limit_price Required if type is "limit" or "stop_limit"
     * @param double $stop_price Required if type is "stop" or "stop_limit"
     * @param string $client_order_id Max 48 chars
     * @param boolean $extended_hours default: false
     * @param array $additional ['take_profit' => ['limit_price' => $limti_price], 'stop_loss' => ['stop_price' => $stop_price]], default: []
     *
     * @return Response
     */
    public function createOrder($symbol, $qty, $side, $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null, $extended_hours = null, $order_class = null, $additional = [])
    {
        $body = [
            "symbol" => $symbol,
            "qty" => $qty,
            "side" => $side,
            "type" => $type,
            "time_in_force" => $time_in_force,
        ];

        if (!is_null($limit_price)) {
            $body["limit_price"] = $limit_price;
        }

        if (!is_null($stop_price)) {
            $body["stop_price"] = $stop_price;
        }

        if (!is_null($extended_hours)) {
            $body["extended_hours"] = $extended_hours;
        }

        if (!is_null($client_order_id)) {
            $body["client_order_id"] = $client_order_id;
        }

        if (!is_null($order_class)) {
            $body["order_class"] = $order_class;
        }

        if (!empty($additional)) {
            foreach ($additional as $key => $val) {
                $body[$key] = $val;
            }
        }

        return $this->_request("orders", [], "POST", $body);
    }

    /**
     * Get all positions for the account.
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
     * Get a specific position.
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
     * Close all positions.
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
     * Close a position.
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
     * Get assets.
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/assets/#get-assets
     *
     * @param string $status "active", etc.
     * @param string $asset_class "us_equity", etc.
     *
     * @return Response
     */
    public function getAssets($status = null, $asset_class = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs["status"] = $status;
        }

        if (!is_null($asset_class)) {
            $qs["asset_class"] = $asset_class;
        }

        return $this->_request("assets", $qs);
    }

    /**
     * Get an asset by ID.
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
     * Get an asset for the given symbol.
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
     * Get a list of watchlists.
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
     * Create a new watchlist with an initial set of assets.
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
     * Get a watchlist.
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
     * Get a watchlist by name.
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
            "name" => $name,
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
            "name" => $name,
        ];

        $body = [
            "name" => $name,
            "symbols" => $symbols,
        ];

        return $this->_request("watchlists:by_name", $qs, "PUT", $body);
    }

    /**
     * Add an asset to a watchlist.
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
     * Delete a watchlist.
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
     * Delete a watchlist by name.
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
     * Get the market calendar.
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
            $qs["start"] = (new Carbon($start))->format("Y-m-d");
        }

        if (!is_null($end)) {
            $qs["end"] = (new Carbon($end))->format("Y-m-d");
        }

        return $this->_request("calendar", $qs);
    }

    /**
     * Get the current market time and status.
     *
     * @link https://docs.alpaca.markets/api-documentation/web-api/clock/#get-the-clock
     *
     * @return Response
     */
    public function getClock()
    {
        return $this->_request("clock");
    }

    /**
     * Get account configurations.
     *
     * @return Response
     */
    public function getAccountConfigurations()
    {
        return $this->_request("account/configurations");
    }

    /**
     * Update account configurations.
     *
     * @param  array $config ["key" => "value"]
     *
     * @return Response
     */
    public function updateAccountConfigurations($config = [])
    {
        return $this->_request("account/configurations", [], "PATCH", $config);
    }

    /**
     * Get account activities of a specified type.
     *
     * @param  string $type
     * @param  string $date
     * @param  string $until
     * @param  string $after
     * @param  string $direction
     * @param  string $page_size
     * @param  string $page_token
     *
     * @return Response
     */
    public function getAccountActivitiesOfType($type, $date = null, $until = null, $after = null, $direction = null, $page_size = null, $page_token = null)
    {
        $qs = [];

        if (!is_null($date)) {
            $qs["date"] = (new Carbon($date))->format("Y-m-d");
        }

        if (!is_null($until)) {
            $qs["until"] = (new Carbon($until))->format("Y-m-d");
        }

        if (!is_null($after)) {
            $qs["after"] = (new Carbon($after))->format("Y-m-d");
        }

        if (!is_null($direction)) {
            $qs["direction"] = $direction;
        }

        if (!is_null($page_size)) {
            $qs["page_size"] = $page_size;
        }

        if (!is_null($page_token)) {
            $qs["page_token"] = $page_token;
        }

        return $this->_request("account/activities/{$type}", $qs);
    }

    /**
     * Get account activities.
     *
     * @param  string[] $types
     *
     * @return Response
     */
    public function getAccountActivities($types = [], $date = null, $until = null, $after = null, $direction = null, $page_size = null, $page_token = null)
    {
        $qs = [
            "activity_types" => $types,
        ];

        if (!is_null($date)) {
            $qs["date"] = (new Carbon($date))->format("Y-m-d");
        }

        if (!is_null($until)) {
            $qs["until"] = (new Carbon($until))->format("Y-m-d");
        }

        if (!is_null($after)) {
            $qs["after"] = (new Carbon($after))->format("Y-m-d");
        }

        if (!is_null($direction)) {
            $qs["direction"] = $direction;
        }

        if (!is_null($page_size)) {
            $qs["page_size"] = $page_size;
        }

        if (!is_null($page_token)) {
            $qs["page_token"] = $page_token;
        }

        return $this->_request("account/activities", $qs);
    }

    /**
     * Get portfolio history.
     *
     * @param  string $period
     * @param  string $timeframe
     * @param  string $date_end
     * @param  boolean $extended_hours
     *
     * @return Response
     */
    public function getPortfolioHistory($period = null, $timeframe = null, $date_end = null, $extended_hours = null)
    {
        $qs = [];

        if (!is_null($period)) {
            $qs["period"] = $period;
        }

        if (!is_null($timeframe)) {
            $qs["timeframe"] = $timeframe;
        }

        if (!is_null($date_end)) {
            $qs["date_end"] = $date_end;
        }

        if (!is_null($extended_hours)) {
            $qs["extended_hours"] = $extended_hours;
        }

        return $this->_request("account/portfolio/history", $qs);
    }

    /**
     * Returns trades for the queried stock symbol
     *
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#trades
     *
     * @param string $symbol The symbol to query for
     * @param string $start Filter data equal to or after this time. Fractions of a second are not accepted.
     * @param string $end Filter data equal to or before this time. Fractions of a second are not accepted.
     * @param integer $limit Number of data points to return. Must be in range 1-10000, defaults to 1000.
     * @param string $page_token Pagination token to continue from.
     *
     * @return Response
     */
    public function getTrades($symbol, $start, $end, $limit = null, $page_token = null)
    {
        $qs = [];

        if (!is_null($start)) {
            $qs['start'] = (new Carbon($start))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($end)) {
            $qs['end'] = (new Carbon($end))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($page_token)) {
            $qs['page_token'] = $page_token;
        }

        return $this->_request("stocks/{$symbol}/trades", $qs, "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns latest trade for the requested security.
     * 
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#latest-trade
     *
     * @param string $symbol The symbol to query for
     *
     * @return Response
     */
    public function getLastTrade($symbol)
    {
        return $this->_request("stocks/{$symbol}/trades/latest", [], "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns quote (NBBO) historical data for the requested security.
     * 
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#quotes
     *
     * @param string $symbol The symbol to query for
     * @param string $start Filter data equal to or after this time. Fractions of a second are not accepted.
     * @param string $end Filter data equal to or before this time. Fractions of a second are not accepted.
     * @param integer $limit Number of data points to return. Must be in range 1-10000, defaults to 1000.
     * @param string $page_token Pagination token to continue from.
     *
     * @return Response
     */
    public function getQuotes($symbol, $start, $end, $limit = null, $page_token = null)
    {
        $qs = [];

        if (!is_null($start)) {
            $qs['start'] = (new Carbon($start))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($end)) {
            $qs['end'] = (new Carbon($end))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($page_token)) {
            $qs['page_token'] = $page_token;
        }

        return $this->_request("stocks/{$symbol}/quotes", $qs, "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns latest quote for the requested security.
     *
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#latest-quote
     *
     * @param string $symbol
     *
     * @return Response
     */
    public function getLastQuote($symbol)
    {
        return $this->_request("stocks/{$symbol}/quotes/latest", [], "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns aggregate historical data for the requested security.
     *
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#bars
     *
     * @param string        $timeframe  Timeframe for the aggregation. Available values are: "1Min", "5Min", "15Min", "1Hour", "1Day".
     * @param string        $symbol     The symbol to query for
     * @param string        $start      Filter data equal to or after this time. Fractions of a second are not accepted.
     * @param string        $end        Filter data equal to or before this time. Fractions of a second are not accepted.
     * @param int           $limit      Number of data points to return. Must be in range 1-10000, defaults to 1000.
     * @param string        $page_token Pagination token to continue from.
     *
     * @return Response
     */
    public function getBars($timeframe, $symbol, $start, $end, $limit = null, $page_token = null)
    {
        $qs = [];

        if (!is_null($timeframe)) {
            $qs["timeframe"] = $timeframe;
        }

        if (!is_null($start)) {
            $qs["start"] = (new Carbon($start))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($end)) {
            $qs["end"] = (new Carbon($end))->format('Y-m-d\TH:i:s\Z');
        }

        if (!is_null($limit)) {
            $qs["limit"] = $limit;
        }

        if (!is_null($page_token)) {
            $qs["page_token"] = $page_token;
        }

        // print_r($qs);
        // return null;

        return $this->_request("stocks/{$symbol}/bars", $qs, "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns the snapshots for the requested securities.
     * 
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#snapshot---multiple-tickers
     *
     * @param string|array $symbols Array or comma-separated string of symbols to query for.
     *
     * @return Response
     */
    public function getMultiSnapshot($symbols)
    {
        if (is_array($symbols)) {
            $qs['symbols'] = implode(',', $symbols);
        } else {
            $qs['symbols'] = $symbols;
        }

        return $this->_request("stocks/snapshots", $qs, "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Returns the snapshot for the requested security.
     * 
     * @link https://alpaca.markets/docs/api-documentation/api-v2/market-data/alpaca-data-api-v2/historical/#snapshot---ticker
     *
     * @param [type] $symbol
     * @return void
     */
    public function getSnapshot($symbol)
    {
        return $this->_request("stocks/{$symbol}/snapshot", [], "GET", null, "https://data.alpaca.markets", "v2");
    }

    /**
     * Get the OAuth Authorization URL for the provided parameters:
     * $client_id, $redirect_uri, $scope, and $state.
     *
     * @param  string $client_id
     * @param  string $redirect_uri
     * @param  string $scope A space-delimited list of valid scopes (account:write, trading, data)
     * @param  string $state
     *
     * @return string The URL to redirect the user to
     */
    public function getOauthAuthorizeUrl($client_id, $redirect_uri, $scope = "", $state = null)
    {
        $redirect_uri = urlencode($redirect_uri);

        if (is_null($state)) {
            $state = bin2hex(random_bytes(8));
        }

        $scope = urlencode($scope);

        return "https://app.alpaca.markets/oauth/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}&scope={$scope}";
    }

    /**
     * Exchange an OAuth authorization code for an access token.
     *
     * @param  string $code The Authorization code returned from Alapaca in the URL when the user was redirected back to your application.
     * @param  string $client_id Your applications Client ID.
     * @param  string $client_secret Your applications Client Secret.
     * @param  string $redirect_uri Should be the same $redirect_uri used in the `getOauthAuthorizeUrl()` call.
     *
     * @return Response
     */
    public function getOauthAccessToken($code, $client_id, $client_secret, $redirect_uri)
    {
        $body = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri" => $redirect_uri,
        ];

        return $this->_request("oauth/token", [], "POST", $body, "https://api.alpaca.markets/", null);
    }

    /**
     * Get the details of the current OAuth access token.
     *
     * @return Response
     */
    public function getOauthAccessTokenDetails()
    {
        return $this->_request("oauth/token", [], "GET", null, "https://api.alpaca.markets/", null);
    }

    /**
     * Get historical news articles across stocks and crypto.
     *
     * @param array $options Valid options include:
     *  - symbols | string
     *  - start | Date in RFC 3339 format | Default: 01-01-2015
     *  - end | Date in RFC 3339 format | Default: now
     *  - limit | string | Default: 10 | Max: 50
     *  - sort | 'ASC', 'DESC' | Default: 'DESC'
     *  - include_content | 'true' or 'false | Default: 'false'
     *  - exclude_contentless | 'true' or 'false' | Default: 'false'
     *  - page_token | string
     *
     * @return Response
     */
    public function getNews($options = [])
    {
        return $this->_request("news", $options, "GET", null, "https://data.alpaca.markets/", "v1beta1");
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \Exception("Unknown method: {$method}");
    }

    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->$property();
        }

        throw new \Exception("Unknown property: {$property}");
    }
}
