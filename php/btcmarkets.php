<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \ccxt\ExchangeError;
use \ccxt\ArgumentsRequired;
use \ccxt\OrderNotFound;

class btcmarkets extends Exchange {

    public function describe () {
        return array_replace_recursive(parent::describe (), array(
            'id' => 'btcmarkets',
            'name' => 'BTC Markets',
            'countries' => array( 'AU' ), // Australia
            'rateLimit' => 1000, // market data cached for 1 second (trades cached for 2 seconds)
            'has' => array(
                'CORS' => false,
                'fetchOHLCV' => true,
                'fetchOrder' => true,
                'fetchOrders' => true,
                'fetchClosedOrders' => 'emulated',
                'fetchOpenOrders' => true,
                'fetchMyTrades' => true,
                'cancelOrders' => true,
            ),
            'urls' => array(
                'logo' => 'https://user-images.githubusercontent.com/1294454/29142911-0e1acfc2-7d5c-11e7-98c4-07d9532b29d7.jpg',
                'api' => array(
                    'public' => 'https://api.btcmarkets.net',
                    'private' => 'https://api.btcmarkets.net',
                    'web' => 'https://btcmarkets.net/data',
                ),
                'www' => 'https://btcmarkets.net',
                'doc' => 'https://github.com/BTCMarkets/API',
            ),
            'api' => array(
                'public' => array(
                    'get' => array(
                        'market/{id}/tick',
                        'market/{id}/orderbook',
                        'market/{id}/trades',
                        'v2/market/{id}/tickByTime/{timeframe}',
                        'v2/market/{id}/trades',
                        'v2/market/active',
                    ),
                ),
                'private' => array(
                    'get' => array(
                        'account/balance',
                        'account/{id}/tradingfee',
                        'fundtransfer/history',
                        'v2/order/open',
                        'v2/order/open/{id}',
                        'v2/order/history/{instrument}/{currency}/',
                        'v2/order/trade/history/{id}',
                        'v2/transaction/history/{currency}',
                    ),
                    'post' => array(
                        'fundtransfer/withdrawCrypto',
                        'fundtransfer/withdrawEFT',
                        'order/create',
                        'order/cancel',
                        'order/history',
                        'order/open',
                        'order/trade/history',
                        'order/createBatch', // they promise it's coming soon...
                        'order/detail',
                    ),
                ),
                'web' => array(
                    'get' => array(
                        'market/BTCMarkets/{id}/tickByTime',
                    ),
                ),
            ),
            'timeframes' => array(
                '1m' => 'minute',
                '1h' => 'hour',
                '1d' => 'day',
            ),
            'exceptions' => array(
                '3' => '\\ccxt\\InvalidOrder',
                '6' => '\\ccxt\\DDoSProtection',
            ),
            'fees' => array(
                'percentage' => true,
                'tierBased' => true,
                'maker' => -0.05 / 100,
                'taker' => 0.20 / 100,
            ),
            'options' => array(
                'fees' => array(
                    'AUD' => array(
                        'maker' => 0.85 / 100,
                        'taker' => 0.85 / 100,
                    ),
                ),
            ),
        ));
    }

    public function fetch_transactions ($code = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array();
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        if ($since !== null) {
            $request['since'] = $since;
        }
        $response = $this->privateGetFundtransferHistory (array_merge($request, $params));
        $transactions = $response['fundTransfers'];
        return $this->parse_transactions($transactions, null, $since, $limit);
    }

    public function parse_transaction_status ($status) {
        // todo => find more $statuses
        $statuses = array(
            'Complete' => 'ok',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_transaction ($item, $currency = null) {
        //
        //     {
        //         $status => 'Complete',
        //         fundTransferId => 1904311906,
        //         description => 'ETH withdraw from [me@email.com] to Address => 0xF123aa44FadEa913a7da99cc2eE202Db684Ce0e3 $amount => 8.28965701 $fee => 0.00000000',
        //         creationTime => 1529418358525,
        //         $currency => 'ETH',
        //         $amount => 828965701,
        //         $fee => 0,
        //         $transferType => 'WITHDRAW',
        //         errorMessage => null,
        //         $lastUpdate => 1529418376754,
        //         $cryptoPaymentDetail => {
        //             $address => '0xF123aa44FadEa913a7da99cc2eE202Db684Ce0e3',
        //             txId => '0x8fe483b6f9523559b9ebffb29624f98e86227d2660d4a1fd4785d45e51c662c2'
        //         }
        //     }
        //
        //     {
        //         $status => 'Complete',
        //         fundTransferId => 494077500,
        //         description => 'BITCOIN Deposit, B 0.1000',
        //         creationTime => 1501077601015,
        //         $currency => 'BTC',
        //         $amount => 10000000,
        //         $fee => 0,
        //         $transferType => 'DEPOSIT',
        //         errorMessage => null,
        //         $lastUpdate => 1501077601133,
        //         $cryptoPaymentDetail => null
        //     }
        //
        //     {
        //         "$fee" => 0,
        //         "$amount" => 56,
        //         "$status" => "Complete",
        //         "$currency" => "BCHABC",
        //         "$lastUpdate" => 1542339164044,
        //         "description" => "BitcoinCashABC Deposit, P 0.00000056",
        //         "creationTime" => 1542339164003,
        //         "errorMessage" => null,
        //         "$transferType" => "DEPOSIT",
        //         "fundTransferId" => 2527326972,
        //         "$cryptoPaymentDetail" => null
        //     }
        //
        $timestamp = $this->safe_integer($item, 'creationTime');
        $lastUpdate = $this->safe_integer($item, 'lastUpdate');
        $transferType = $this->safe_string($item, 'transferType');
        $cryptoPaymentDetail = $this->safe_value($item, 'cryptoPaymentDetail', array());
        $address = $this->safe_string($cryptoPaymentDetail, 'address');
        $txid = $this->safe_string($cryptoPaymentDetail, 'txId');
        $type = null;
        if ($transferType === 'DEPOSIT') {
            $type = 'deposit';
        } else if ($transferType === 'WITHDRAW') {
            $type = 'withdrawal';
        } else {
            $type = $transferType;
        }
        $fee = $this->safe_float($item, 'fee');
        $status = $this->parse_transaction_status ($this->safe_string($item, 'status'));
        $ccy = $this->safe_string($item, 'currency');
        $code = $this->safe_currency_code($ccy);
        // todo => this logic is duplicated below
        $amount = $this->safe_float($item, 'amount');
        if ($amount !== null) {
            $amount = $amount * 1e-8;
        }
        return array(
            'id' => $this->safe_string($item, 'fundTransferId'),
            'txid' => $txid,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'address' => $address,
            'tag' => null,
            'type' => $type,
            'amount' => $amount,
            'currency' => $code,
            'status' => $status,
            'updated' => $lastUpdate,
            'fee' => array(
                'currency' => $code,
                'cost' => $fee,
            ),
            'info' => $item,
        );
    }

    public function fetch_markets ($params = array ()) {
        $response = $this->publicGetV2MarketActive ($params);
        $result = array();
        $markets = $this->safe_value($response, 'markets');
        for ($i = 0; $i < count($markets); $i++) {
            $market = $markets[$i];
            $baseId = $this->safe_string($market, 'instrument');
            $quoteId = $this->safe_string($market, 'currency');
            $id = $baseId . '/' . $quoteId;
            $base = $this->safe_currency_code($baseId);
            $quote = $this->safe_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $fees = $this->safe_value($this->safe_value($this->options, 'fees', array()), $quote, $this->fees);
            $pricePrecision = 2;
            $amountPrecision = 4;
            $minAmount = 0.001; // where does it come from?
            $minPrice = null;
            if ($quote === 'AUD') {
                if (($base === 'XRP') || ($base === 'OMG')) {
                    $pricePrecision = 4;
                }
                $amountPrecision = -log10 ($minAmount);
                $minPrice = pow(10, -$pricePrecision);
            }
            $precision = array(
                'amount' => $amountPrecision,
                'price' => $pricePrecision,
            );
            $limits = array(
                'amount' => array(
                    'min' => $minAmount,
                    'max' => null,
                ),
                'price' => array(
                    'min' => $minPrice,
                    'max' => null,
                ),
                'cost' => array(
                    'min' => null,
                    'max' => null,
                ),
            );
            $result[] = array(
                'info' => $market,
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => null,
                'maker' => $fees['maker'],
                'taker' => $fees['taker'],
                'limits' => $limits,
                'precision' => $precision,
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $balances = $this->privateGetAccountBalance ($params);
        $result = array( 'info' => $balances );
        for ($i = 0; $i < count($balances); $i++) {
            $balance = $balances[$i];
            $currencyId = $this->safe_string($balance, 'currency');
            $code = $this->safe_currency_code($currencyId);
            $multiplier = 100000000;
            $total = $this->safe_float($balance, 'balance');
            if ($total !== null) {
                $total /= $multiplier;
            }
            $used = $this->safe_float($balance, 'pendingFunds');
            if ($used !== null) {
                $used /= $multiplier;
            }
            $account = $this->account ();
            $account['used'] = $used;
            $account['total'] = $total;
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function parse_ohlcv ($ohlcv, $market = null, $timeframe = '1m', $since = null, $limit = null) {
        //
        //     {
        //         "timestamp":1572307200000,
        //         "open":1962218,
        //         "high":1974850,
        //         "low":1962208,
        //         "close":1974850,
        //         "volume":305211315,
        //     }
        //
        $multiplier = 100000000; // for price and volume
        $keys = array( 'open', 'high', 'low', 'close', 'volume' );
        $result = array(
            $this->safe_integer($ohlcv, 'timestamp'),
        );
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $this->safe_float($ohlcv, $key);
            if ($value !== null) {
                $value = $value / $multiplier;
            }
            $result[] = $value;
        }
        return $result;
    }

    public function fetch_ohlcv ($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets ();
        $market = $this->market ($symbol);
        $request = array(
            'id' => $market['id'],
            'timeframe' => $this->timeframes[$timeframe],
            // set to true to see candles more recent than the timestamp in the
            // $since parameter, if a $since parameter is used, default is false
            'indexForward' => true,
            // set to true to see the earliest candles first in the list of
            // returned candles in chronological order, default is false
            'sortForward' => true,
        );
        if ($since !== null) {
            $request['since'] = $since;
        }
        if ($limit !== null) {
            $request['limit'] = $limit; // default is 3000
        }
        $response = $this->publicGetV2MarketIdTickByTimeTimeframe (array_merge($request, $params));
        //
        //     {
        //         "success":true,
        //         "paging":array(
        //             "newer":"/v2/market/ETH/BTC/tickByTime/day?indexForward=true&$since=1572307200000",
        //             "older":"/v2/market/ETH/BTC/tickByTime/day?$since=1457827200000"
        //         ),
        //         "$ticks":array(
        //             array("timestamp":1572307200000,"open":1962218,"high":1974850,"low":1962208,"close":1974850,"volume":305211315),
        //             array("timestamp":1572220800000,"open":1924700,"high":1951276,"low":1909328,"close":1951276,"volume":1086067595),
        //             array("timestamp":1572134400000,"open":1962155,"high":1962734,"low":1900905,"close":1930243,"volume":790141098),
        //         ),
        //     }
        //
        $ticks = $this->safe_value($response, 'ticks', array());
        return $this->parse_ohlcvs($ticks, $market, $timeframe, $since, $limit);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array(
            'id' => $market['id'],
        );
        $response = $this->publicGetMarketIdOrderbook (array_merge($request, $params));
        $timestamp = $this->safe_timestamp($response, 'timestamp');
        return $this->parse_order_book($response, $timestamp);
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $this->safe_timestamp($ticker, 'timestamp');
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $last = $this->safe_float($ticker, 'lastPrice');
        return array(
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => null,
            'low' => null,
            'bid' => $this->safe_float($ticker, 'bestBid'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'bestAsk'),
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->safe_float($ticker, 'volume24h'),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array(
            'id' => $market['id'],
        );
        $response = $this->publicGetMarketIdTick (array_merge($request, $params));
        return $this->parse_ticker($response, $market);
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->safe_timestamp($trade, 'date');
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $id = $this->safe_string($trade, 'tid');
        $price = $this->safe_float($trade, 'price');
        $amount = $this->safe_float($trade, 'amount');
        $cost = null;
        if ($amount !== null) {
            if ($price !== null) {
                $cost = $amount * $price;
            }
        }
        return array(
            'info' => $trade,
            'id' => $id,
            'order' => null,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'type' => null,
            'side' => null,
            'takerOrMaker' => null,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => null,
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array(
            // 'since' => 59868345231,
            'id' => $market['id'],
        );
        $response = $this->publicGetMarketIdTrades (array_merge($request, $params));
        return $this->parse_trades($response, $market, $since, $limit);
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $multiplier = 100000000; // for $price and volume
        $orderSide = ($side === 'buy') ? 'Bid' : 'Ask';
        $request = $this->ordered (array(
            'currency' => $market['quote'],
        ));
        $request['currency'] = $market['quote'];
        $request['instrument'] = $market['base'];
        $request['price'] = intval ($price * $multiplier);
        $request['volume'] = intval ($amount * $multiplier);
        $request['orderSide'] = $orderSide;
        $request['ordertype'] = $this->capitalize ($type);
        $request['clientRequestId'] = (string) $this->nonce ();
        $response = $this->privatePostOrderCreate (array_merge($request, $params));
        $id = $this->safe_string($response, 'id');
        return array(
            'info' => $response,
            'id' => $id,
        );
    }

    public function cancel_orders ($ids, $symbol = null, $params = array ()) {
        $this->load_markets();
        for ($i = 0; $i < count($ids); $i++) {
            $ids[$i] = intval ($ids[$i]);
        }
        $request = array(
            'orderIds' => $ids,
        );
        return $this->privatePostOrderCancel (array_merge($request, $params));
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        return $this->cancel_orders (array( $id ));
    }

    public function calculate_fee ($symbol, $type, $side, $amount, $price, $takerOrMaker = 'taker', $params = array ()) {
        $market = $this->markets[$symbol];
        $rate = $market[$takerOrMaker];
        $currency = null;
        $cost = null;
        if ($market['quote'] === 'AUD') {
            $currency = $market['quote'];
            $cost = floatval ($this->cost_to_precision($symbol, $amount * $price));
        } else {
            $currency = $market['base'];
            $cost = floatval ($this->amount_to_precision($symbol, $amount));
        }
        return array(
            'type' => $takerOrMaker,
            'currency' => $currency,
            'rate' => $rate,
            'cost' => floatval ($this->fee_to_precision($symbol, $rate * $cost)),
        );
    }

    public function parse_my_trade ($trade, $market) {
        $multiplier = 100000000;
        $timestamp = $this->safe_integer($trade, 'creationTime');
        $side = $this->safe_float($trade, 'side');
        $side = ($side === 'Bid') ? 'buy' : 'sell';
        // BTCMarkets always charge in AUD for AUD-related transactions.
        $feeCurrencyCode = null;
        $symbol = null;
        if ($market !== null) {
            $feeCurrencyCode = ($market['quote'] === 'AUD') ? $market['quote'] : $market['base'];
            $symbol = $market['symbol'];
        }
        $id = $this->safe_string($trade, 'id');
        $price = $this->safe_float($trade, 'price');
        if ($price !== null) {
            $price /= $multiplier;
        }
        $amount = $this->safe_float($trade, 'volume');
        if ($amount !== null) {
            $amount /= $multiplier;
        }
        $feeCost = $this->safe_float($trade, 'fee');
        if ($feeCost !== null) {
            $feeCost /= $multiplier;
        }
        $cost = null;
        if ($price !== null) {
            if ($amount !== null) {
                $cost = $price * $amount;
            }
        }
        $orderId = $this->safe_string($trade, 'orderId');
        return array(
            'info' => $trade,
            'id' => $id,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'order' => $orderId,
            'symbol' => $symbol,
            'type' => null,
            'side' => $side,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => array(
                'currency' => $feeCurrencyCode,
                'cost' => $feeCost,
            ),
        );
    }

    public function parse_my_trades ($trades, $market = null, $since = null, $limit = null) {
        $result = array();
        for ($i = 0; $i < count($trades); $i++) {
            $trade = $this->parse_my_trade ($trades[$i], $market);
            $result[] = $trade;
        }
        return $result;
    }

    public function parse_order ($order, $market = null) {
        $multiplier = 100000000;
        $side = ($order['orderSide'] === 'Bid') ? 'buy' : 'sell';
        $type = ($order['ordertype'] === 'Limit') ? 'limit' : 'market';
        $timestamp = $this->safe_integer($order, 'creationTime');
        if ($market === null) {
            $market = $this->market ($order['instrument'] . '/' . $order['currency']);
        }
        $status = 'open';
        if ($order['status'] === 'Failed' || $order['status'] === 'Cancelled' || $order['status'] === 'Partially Cancelled' || $order['status'] === 'Error') {
            $status = 'canceled';
        } else if ($order['status'] === 'Fully Matched' || $order['status'] === 'Partially Matched') {
            $status = 'closed';
        }
        $price = $this->safe_float($order, 'price') / $multiplier;
        $amount = $this->safe_float($order, 'volume') / $multiplier;
        $remaining = $this->safe_float($order, 'openVolume', 0.0) / $multiplier;
        $filled = $amount - $remaining;
        $trades = $this->parse_my_trades ($order['trades'], $market);
        $numTrades = is_array($trades) ? count($trades) : 0;
        $cost = $filled * $price;
        $average = null;
        $lastTradeTimestamp = null;
        if ($numTrades > 0) {
            $cost = 0;
            for ($i = 0; $i < $numTrades; $i++) {
                $trade = $trades[$i];
                $cost = $this->sum ($cost, $trade['cost']);
            }
            if ($filled > 0) {
                $average = $cost / $filled;
            }
            $lastTradeTimestamp = $trades[$numTrades - 1]['timestamp'];
        }
        $id = $this->safe_string($order, 'id');
        return array(
            'info' => $order,
            'id' => $id,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => $lastTradeTimestamp,
            'symbol' => $market['symbol'],
            'type' => $type,
            'side' => $side,
            'price' => $price,
            'cost' => $cost,
            'amount' => $amount,
            'filled' => $filled,
            'remaining' => $remaining,
            'average' => $average,
            'status' => $status,
            'trades' => $trades,
            'fee' => null,
        );
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $ids = array( intval ($id) );
        $request = array(
            'orderIds' => $ids,
        );
        $response = $this->privatePostOrderDetail (array_merge($request, $params));
        $numOrders = is_array($response['orders']) ? count($response['orders']) : 0;
        if ($numOrders < 1) {
            throw new OrderNotFound($this->id . ' No matching $order found => ' . $id);
        }
        $order = $response['orders'][0];
        return $this->parse_order($order);
    }

    public function create_paginated_request ($market, $since = null, $limit = null) {
        $limit = ($limit === null) ? 100 : $limit;
        $since = ($since === null) ? 0 : $since;
        $request = $this->ordered (array(
            'currency' => $market['quoteId'],
            'instrument' => $market['baseId'],
            'limit' => $limit,
            'since' => $since,
        ));
        return $request;
    }

    public function fetch_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' => fetchOrders requires a `$symbol` argument.');
        }
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = $this->create_paginated_request ($market, $since, $limit);
        $response = $this->privatePostOrderHistory (array_merge($request, $params));
        return $this->parse_orders($response['orders'], $market);
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' => fetchOpenOrders requires a `$symbol` argument.');
        }
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = $this->create_paginated_request ($market, $since, $limit);
        $response = $this->privatePostOrderOpen (array_merge($request, $params));
        return $this->parse_orders($response['orders'], $market);
    }

    public function fetch_closed_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $orders = $this->fetch_orders($symbol, $since, $limit, $params);
        return $this->filter_by($orders, 'status', 'closed');
    }

    public function fetch_my_trades ($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' => fetchMyTrades requires a `$symbol` argument.');
        }
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = $this->create_paginated_request ($market, $since, $limit);
        $response = $this->privatePostOrderTradeHistory (array_merge($request, $params));
        return $this->parse_my_trades ($response['trades'], $market);
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $uri = '/' . $this->implode_params($path, $params);
        $url = $this->urls['api'][$api] . $uri;
        if ($api === 'private') {
            $this->check_required_credentials();
            $nonce = (string) $this->nonce ();
            $auth = null;
            $headers = array(
                'apikey' => $this->apiKey,
                'timestamp' => $nonce,
            );
            if ($method === 'POST') {
                $headers['Content-Type'] = 'application/json';
                $auth = $uri . "\n" . $nonce . "\n"; // eslint-disable-line quotes
                $body = $this->json ($params);
                $auth .= $body;
            } else {
                $query = $this->keysort ($this->omit ($params, $this->extract_params($path)));
                $queryString = '';
                if ($query) {
                    $queryString = $this->urlencode ($query);
                    $url .= '?' . $queryString;
                    $queryString .= "\n"; // eslint-disable-line quotes
                }
                $auth = $uri . "\n" . $queryString . $nonce . "\n"; // eslint-disable-line quotes
            }
            $secret = base64_decode($this->secret);
            $signature = $this->hmac ($this->encode ($auth), $secret, 'sha512', 'base64');
            $headers['signature'] = $this->decode ($signature);
        } else {
            if ($params) {
                $url .= '?' . $this->urlencode ($params);
            }
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body, $response, $requestHeaders, $requestBody) {
        if ($response === null) {
            return; // fallback to default $error handler
        }
        if (is_array($response) && array_key_exists('success', $response)) {
            if (!$response['success']) {
                $error = $this->safe_string($response, 'errorCode');
                $feedback = $this->id . ' ' . $body;
                $this->throw_exactly_matched_exception($this->exceptions, $error, $feedback);
                throw new ExchangeError($feedback);
            }
        }
    }
}
