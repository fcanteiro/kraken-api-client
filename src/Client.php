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
    EarnAllocationsResponse,
    EarnStrategiesResponse,
    Entities\AddOrder\OrderAdded,
    Entities\CancelOrdersAfterTimeout,
    Entities\DepositMethods,
    Entities\Earn\Allocation\Allocations,
    Entities\Earn\EarnStrategies,
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
     * List earn strategies along with their parameters.
     *
     * Requires a valid API key but not specific permission is required.
     *
     * Returns only strategies that are available to the user based on geographic region.
     *
     * When the user does not meet the tier restriction:
     * - `can_allocate` will be false
     * - `allocation_restriction_info` indicates `Tier` as the restriction reason
     *
     * Earn products generally require Intermediate tier. Get your account verified to access earn.
     *
     * A note about `lock_type`:
     * - `instant`: can be deallocated without an unbonding period. This is called flexible in the UI.
     * - `bonded`: has an unbonding period. Deallocation will not happen until this period has passed.
     * - `flex`: "Kraken rewards". This is earning on your spot balances where eligible. It's turned on account wide from the UI and you cannot manually allocate to these strategies.
     *
     * Paging isn't yet implemented, so the endpoint always returns all data in the first page.
     *
     * @param  string|null  $asset
     * @param  array  $lockType
     * @param  bool  $ascending
     * @return EarnStrategies
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://docs.kraken.com/api/docs/rest-api/list-strategies
     */
    public function getEarnStrategies(?string $asset = null, array $lockType = ['flex', 'bonded', 'instant'], bool $ascending = false): EarnStrategies
    {
        return $this->request(
            method: 'private/Earn/Strategies',
            responsePayload: EarnStrategiesResponse::class,
            parameters: [
                'asset' => $asset,
                'lock_type' => $lockType,
                'ascending' => $ascending ? 'true' : 'false',
                // 'cursor' => '10', // not yet implemented
                // 'limit' => 10, // not yet implemented
            ],
        )->result;
    }

    /**
     * List all allocations for the user.
     *
     * Requires the `Query Funds` API key permission.
     *
     * By default, all allocations are returned, even for strategies that have been used in the past and have zero balance now.
     * This allows the user to see how much was earned with a given strategy in the past. The `hide_zero_allocations` parameter
     * can be used to remove zero balance entries from the output. Paging hasn't been implemented for this method as we don't
     * expect the result for a particular user to be overwhelmingly large.
     *
     * All amounts in the output can be denominated in a currency of the user's choice (the `converted_asset` parameter).
     *
     * Information about when the next reward will be paid to the client is also provided in the output.
     *
     * Allocated funds can be in up to 4 states:
     * - bonding
     * - allocated
     * - exit_queue (ETH only)
     * - unbonding
     *
     * Any funds in `total` not in `bonding`/`unbonding` are simply allocated and earning rewards. Depending on the strategy, funds
     * in the other 3 states can also be earning rewards. Consult the output of `/Earn/Strategies` to know whether `bonding`/`unbonding`
     * earn rewards. `ETH` in `exit_queue` still earns rewards.
     *
     * Note that for `ETH`, when the funds are in the `exit_queue` state, the `expires` time given is the time when the funds will have
     * finished unbonding, not when they go from exit queue to unbonding.
     *
     * (Un)bonding time estimate can be inaccurate right after having (de)allocated the funds. Wait 1-2 minutes after (de)allocating
     * to get an accurate result.
     *
     * @param  string  $convertedAsset The currency to which amounts should be converted. Default is 'USD'.
     * @param  bool  $hideZeroAllocations Whether to hide allocations with zero balance. Default is false.
     * @param  bool  $ascending Whether to sort the results in ascending order. Default is false.
     * @return Allocations The list of allocations.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEarnAllocations(string $convertedAsset = 'USD', bool $hideZeroAllocations = false, bool $ascending = false): Allocations
    {
        return $this->request(
            method: 'private/Earn/Allocations',
            responsePayload: EarnAllocationsResponse::class,
            parameters: [
                'ascending' => $ascending ? 'true' : 'false',
                'converted_asset' => $convertedAsset,
                'hide_zero_allocations' => $hideZeroAllocations ? 'true' : 'false',
            ]
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
