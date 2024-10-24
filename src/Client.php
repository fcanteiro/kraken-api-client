<?php

namespace Butschster\Kraken;

use Butschster\Kraken\Contracts\{
    AddOrderRequest, NonceGenerator, Response
};
use Brick\Math\BigDecimal;
use Butschster\Kraken\Exceptions\KrakenApiErrorException;
use Butschster\Kraken\Responses\{AccountBalanceResponse,
    AddOrderResponse,
    AssetInfoResponse,
    CancelOrderResponse,
    CancelOrdersAfterTimeoutResponse,
    ClosedOrdersResponse,
    DepositAddressesResponse,
    DepositMethodsResponse,
    Entities\AddOrder\OrderAdded,
    Entities\CancelOrdersAfterTimeout,
    Entities\DepositMethods,
    Entities\Orders\ClosedOrders,
    Entities\ServerTime,
    Entities\SystemStatus,
    Entities\TradeBalance,
    Entities\WebsocketToken,
    Entities\WithdrawalInformation,
    GetWebSocketsTokenResponse,
    OpenOrdersResponse,
    OrderBookResponse,
    QueryOrdersResponse,
    ServerTimeResponse,
    SystemStatusResponse,
    TickerInformationResponse,
    TradableAssetPairsResponse,
    TradeBalanceResponse,
    WithdrawalInformationResponse
};
use Butschster\Kraken\ValueObjects\{
    AssetClass, AssetPair, TradableInfo
};
use DateTimeInterface;
use Illuminate\Support\Str;
use JMS\Serializer\SerializerInterface;

use GuzzleHttp\ClientInterface as HttpClient;
use Webmozart\Assert\Assert;

final class Client implements Contracts\Client
{
    private const API_URL = 'https://api.kraken.com';
    private const API_VERSION = 0;
    private const API_USER_AGENT = 'Kraken PHP API Agent';

    /**
     * @param HttpClient $client
     * @param string $key API key
     * @param string $secret API secret
     * @param string|null $otp Two-factor password (if two-factor enabled, otherwise not required)
     */
    public function __construct(
        private HttpClient $client,
        private NonceGenerator $nonce,
        private SerializerInterface $serializer,
        private string $key,
        private string $secret,
        private ?string $otp = null,
    ) {}

    public function getServerTime(): ServerTime
    {
        return $this->request(
            method: 'public/Time',
            responsePayload: ServerTimeResponse::class,
            requestMethod: 'GET',
        )->result;
    }

    public function getSystemStatus(): SystemStatus
    {
        return $this->request(
            method: 'public/SystemStatus',
            responsePayload: SystemStatusResponse::class,
            requestMethod: 'GET',
        )->result;
    }

    public function getAssetInfo(array $assets = ['all'], ?AssetClass $class = null): array
    {
        $params = [
            'asset' => implode(',', $assets),
        ];

        if ($class) {
            $params['aclass'] = (string) $class;
        }

        return (array) $this->request(
            'public/Assets',
            AssetInfoResponse::class,
            $params,
            'GET',
        )->result;
    }

    public function getTradableAssetPairs(AssetPair $pair, ?TradableInfo $info = null): array
    {
        $params = [
            'pair' => (string) $pair,
        ];

        if ($info) {
            $params['info'] = (string) $info;
        }

        return (array) $this->request(
            'public/AssetPairs',
            TradableAssetPairsResponse::class,
            $params,
            'GET',
        )->result;
    }

    public function getTickerInformation(array $pairs): array
    {
        return (array) $this->request(
            method: 'public/Ticker',
            responsePayload: TickerInformationResponse::class,
            parameters: [
                'pair' => implode(',', $pairs),
            ],
            requestMethod: 'GET',
        )->result;
    }

    public function getOrderBook(array $pairs, int $count = 100): array
    {
        return (array) $this->request('public/Depth', OrderBookResponse::class, [
            'pair' => implode(',', $pairs),
            'count' => $count,
        ], 'GET')->result;
    }

    public function getAccountBalance(): array
    {
        return (array) $this->request('private/Balance', AccountBalanceResponse::class)->result;
    }

    public function getTradeBalance(string $asset = 'ZUSD'): TradeBalance
    {
        return $this->request('private/TradeBalance', TradeBalanceResponse::class, [
            'asset' => $asset,
        ])->result;
    }

    public function getOpenOrders(bool $trades = false, ?int $userRef = null): array
    {
        $params = [
            'trades' => $trades,
        ];

        if ($userRef) {
            $params['userref'] = $userRef;
        }

        return (array) $this->request(
            'private/OpenOrders',
            OpenOrdersResponse::class,
            $params,
        )->result?->open;
    }

    public function getClosedOrders(
        DateTimeInterface|string|null $start = null,
        DateTimeInterface|string|null $end = null,
        ?string $closeTime = null,
        ?int $offset = null,
        bool $trades = false,
        ?int $userRef = null,
    ): ClosedOrders {
        $params = [
            'trades' => $trades,
        ];

        if ($userRef) {
            $params['userref'] = $userRef;
        }

        if ($start) {
            $params['start'] = $start instanceof DateTimeInterface ? $start->getTimestamp() : $start;
        }

        if ($end) {
            $params['end'] = $end instanceof DateTimeInterface ? $end->getTimestamp() : $end;
        }

        if ($offset) {
            $params['ofs'] = $offset;
        }

        if ($closeTime) {
            $params['closetime'] = $closeTime;
        }

        return $this->request(
            'private/ClosedOrders',
            ClosedOrdersResponse::class,
            $params,
        )->result;
    }

    public function queryOrdersInfo(array $txIds, bool $trades = false, ?int $userRef = null): array
    {
        Assert::minCount($txIds, 1, 'Min 1 ID of transactions');
        Assert::maxCount($txIds, 20, 'Max 20 IDs of transactions');

        $params = [
            'trades' => $trades,
            'txid' => implode(',', $txIds),
        ];

        if ($userRef) {
            $params['userref'] = $userRef;
        }

        return (array) $this->request(
            'private/QueryOrders',
            QueryOrdersResponse::class,
            $params,
        )->result;
    }

    public function addOrder(AddOrderRequest $request): OrderAdded
    {
        return $this->request(
            method: 'private/AddOrder',
            responsePayload: AddOrderResponse::class,
            parameters: $request->toArray(),
        )->result;
    }

    public function cancelOrder(int|string $txId): int
    {
        return $this->request(
            method: 'private/CancelOrder',
            responsePayload: CancelOrderResponse::class,
            parameters: ['txid' => $txId],
        )->result->count;
    }

    public function cancelAllOrders(): int
    {
        return $this->request(
            method: 'private/CancelAll',
            responsePayload: CancelOrderResponse::class,
        )->result->count;
    }

    public function cancelAllOrdersAfter(int $timeout): CancelOrdersAfterTimeout
    {
        return $this->request(
            method: 'private/CancelAllOrdersAfter',
            responsePayload: CancelOrdersAfterTimeoutResponse::class,
            parameters: ['timeout' => $timeout],
        )->result;
    }

    public function getDepositMethods(string $asset): ?DepositMethods
    {
        return $this->request(
            method: 'private/DepositMethods',
            responsePayload: DepositMethodsResponse::class,
            parameters: ['asset' => $asset],
        )->result[0] ?? null;
    }

    public function getDepositAddresses(string $asset, string $method, bool $new = false): array
    {
        return $this->request(
            method: 'private/DepositAddresses',
            responsePayload: DepositAddressesResponse::class,
            parameters: [
                'asset' => $asset,
                'method' => $method,
                'new' => $new,
            ],
        )->result;
    }

    public function getWebsocketsToken(): WebsocketToken
    {
        return $this->request(
            method: 'private/GetWebSocketsToken',
            responsePayload: GetWebSocketsTokenResponse::class,
        )->result;
    }

    public function getWithdrawalInformation(string $asset, string $key, BigDecimal $amount): WithdrawalInformation
    {
        return $this->request(
            method: 'private/WithdrawInfo',
            responsePayload: WithdrawalInformationResponse::class,
            parameters: [
                'asset' => $asset,
                'key' => $key,
                'amount' => (string) $amount,
            ],
        )->result;
    }

    /**
     * Make request
     *
     * @param string $method API Endpoint
     * @param string $responsePayload Payload Class
     * @param array $parameters Request data
     * @param string $requestMethod Request Method to use default: POST
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(
        string $method,
        string $responsePayload,
        array $parameters = [],
        string $requestMethod = 'POST',
    ): Response {
        $headers = ['User-Agent' => self::API_USER_AGENT];
        $isPublic = Str::startsWith($method, 'public/');

        if (!$isPublic) {
            if ($this->otp) {
                $parameters['otp'] = $this->otp;
            }

            $parameters['nonce'] = $this->nonce->generate();
            $headers['API-Key'] = $this->key;
            $headers['API-Sign'] = $this->makeSignature($method, $parameters);
        }

        $response = match ($requestMethod) {
            'GET' => $this->client->request($requestMethod, self::API_URL . $this->buildPath($method), [
                'headers' => $headers,
                'query' => $parameters,
                'verify' => true,
            ]),
            default => $this->client->request($requestMethod, self::API_URL . $this->buildPath($method), [
                'headers' => $headers,
                'form_params' => $parameters,
                'verify' => true,
            ]),
        };


        $responseObject = $this->serializer->deserialize(
            (string) $response->getBody(),
            $responsePayload,
            'json',
        );

        if ($responseObject->hasErrors()) {
            throw KrakenApiErrorException::fromArray($responseObject->error);
        }

        return $responseObject;
    }

    private function buildPath(string $method): string
    {
        return '/' . self::API_VERSION . '/' . $method;
    }

    /**
     * Message signature using HMAC-SHA512 of (URI path + SHA256(nonce + POST data))
     * and base64 decoded secret API key
     */
    private function makeSignature(string $method, array $parameters = []): string
    {
        $queryString = http_build_query($parameters, '', '&');

        $signature = hash_hmac(
            'sha512',
            $this->buildPath($method) . hash('sha256', $parameters['nonce'] . $queryString, true),
            base64_decode($this->secret),
            true,
        );

        return base64_encode($signature);
    }
}
