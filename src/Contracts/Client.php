<?php

namespace Butschster\Kraken\Contracts;

use Brick\Math\BigDecimal;
use Butschster\Kraken\Responses\Entities\AccountBalance;
use Butschster\Kraken\Responses\Entities\AssetInfo;
use Butschster\Kraken\Responses\Entities\AddOrder\OrderAdded;
use Butschster\Kraken\Responses\Entities\CancelOrdersAfterTimeout;
use Butschster\Kraken\Responses\Entities\DepositAddresses;
use Butschster\Kraken\Responses\Entities\DepositMethods;
use Butschster\Kraken\Responses\Entities\Earn\Allocation\Allocations;
use Butschster\Kraken\Responses\Entities\Earn\EarnStrategies;
use Butschster\Kraken\Responses\Entities\OrderBook\Orders;
use Butschster\Kraken\Responses\Entities\Orders\ClosedOrders;
use Butschster\Kraken\Responses\Entities\Orders\Order;
use Butschster\Kraken\Responses\Entities\ServerTime;
use Butschster\Kraken\Responses\Entities\SystemStatus;
use Butschster\Kraken\Responses\Entities\TickerInformation;
use Butschster\Kraken\Responses\Entities\TradableAsset;
use Butschster\Kraken\Responses\Entities\TradeBalance;
use Butschster\Kraken\Responses\Entities\WebsocketToken;
use Butschster\Kraken\Responses\Entities\WithdrawalInformation;
use Butschster\Kraken\ValueObjects\AssetClass;
use Butschster\Kraken\ValueObjects\AssetPair;
use Butschster\Kraken\ValueObjects\TradableInfo;
use DateTimeInterface;
use GuzzleHttp\Exception\GuzzleException;

interface Client
{
    /**
     * Get the server's time.
     * @see https://docs.kraken.com/rest/#operation/getServerTime
     * @return ServerTime
     */
    public function getServerTime(): ServerTime;

    /**
     * Get System Status
     * @see https://docs.kraken.com/rest/#operation/getSystemStatus
     * @return SystemStatus
     */
    public function getSystemStatus(): SystemStatus;

    /**
     * Get information about the assets that are available for deposit, withdrawal, trading and staking.
     * @see https://docs.kraken.com/rest/#operation/getSystemStatus
     * @param array|string[] $assets List of assets to get info on (Default = all)
     * @param AssetClass|null $class Asset class. (optional, default: currency)
     * @return AssetInfo[]
     */
    public function getAssetInfo(array $assets = ['all'], ?AssetClass $class = null): array;

    /**
     * Get Tradable Asset Pairs
     * @see https://docs.kraken.com/rest/#operation/getTradableAssetPairs
     * @param AssetPair $pair Asset pairs to get data for
     * @param TradableInfo|null $info Info to retrieve. (optional)
     * @return TradableAsset[]
     */
    public function getTradableAssetPairs(AssetPair $pair, ?TradableInfo $info = null): array;

    /**
     * Get Ticker Information
     * @see https://docs.kraken.com/rest/#operation/getTickerInformation
     * @param string[] $pairs Asset pair to get data for (Example: XBTUSD)
     * @return TickerInformation[]
     */
    public function getTickerInformation(array $pairs): array;

    /**
     * Get Order Book
     * @see https://docs.kraken.com/rest/#operation/getOrderBook
     * @param string[] $pairs Asset pair to get data for
     * @param int $count Maximum number of asks/bids [ 1 .. 500 ]
     * @return Orders[]
     */
    public function getOrderBook(array $pairs, int $count = 100): array;

    /**
     * Retrieve all cash balances, net of pending withdrawals.
     * @see https://docs.kraken.com/rest/#operation/getAccountBalance
     * @return AccountBalance[]
     */
    public function getAccountBalance(): array;

    /**
     * Retrieve a summary of collateral balances, margin position valuations, equity and margin level.
     * @see https://docs.kraken.com/rest/#operation/getTradeBalance
     * @param string $asset Base asset used to determine balance
     * @return TradeBalance
     */
    public function getTradeBalance(string $asset = 'ZUSD'): TradeBalance;

    /**
     * Retrieve information about currently open orders.
     * @see https://docs.kraken.com/rest/#operation/getOpenOrders
     * @param bool $trades Whether or not to include trades related to position in output
     * @param int|null $userRef Restrict results to given user reference id
     * @return Order[]
     */
    public function getOpenOrders(bool $trades = false, ?int $userRef = null): array;

    /**
     * Retrieve information about orders that have been closed (filled or cancelled). 50 results are returned at a time, the most recent by default.
     * Note: If an order's tx ID is given for start or end time, the order's opening time (opentm) is used
     * @see https://docs.kraken.com/rest/#operation/getClosedOrders
     * @param DateTimeInterface|string|null $start Starting unix timestamp or order tx ID of results (exclusive)
     * @param DateTimeInterface|string|null $end Ending unix timestamp or order tx ID of results (inclusive)
     * @param string|null $closeTime Which time to use to search Enum: "open" "close" "both"
     * @param int|null $offset Result offset for pagination
     * @param bool $trades Whether or not to include trades related to position in output
     * @param int|null $userRef Restrict results to given user reference id
     * @return ClosedOrders
     */
    public function getClosedOrders(
        DateTimeInterface|string|null $start = null,
        DateTimeInterface|string|null $end = null,
        ?string $closeTime = null,
        ?int $offset = null,
        bool $trades = false,
        ?int $userRef = null
    ): ClosedOrders;

    /**
     * Retrieve information about specific orders.
     * @see https://docs.kraken.com/rest/#operation/getClosedOrders
     * @param array $txIds List of transaction IDs to query info about (20 maximum)
     * @param bool $trades Whether or not to include trades related to position in output
     * @param int|null $userRef Restrict results to given user reference id
     * @return Order[]
     */
    public function queryOrdersInfo(array $txIds, bool $trades = false, ?int $userRef = null): array;

    /**
     * Place a new order.
     * Note: See the AssetPairs endpoint for details on the available trading pairs, their price and quantity
     * precisions, order minimums, available leverage, etc.
     * @see https://docs.kraken.com/rest/#operation/addOrder
     * @param AddOrderRequest $request
     * @return OrderAdded
     */
    public function addOrder(AddOrderRequest $request): OrderAdded;

    /**
     * Cancel a particular open order (or set of open orders) by txid or userref
     * @see https://docs.kraken.com/rest/#operation/cancelOrder
     * @param string|int $txId Open order transaction ID (txid) or user reference (userref)
     * @return int Number of orders cancelled.
     */
    public function cancelOrder(string|int $txId): int;

    /**
     * Cancel all open orders
     * @see https://docs.kraken.com/rest/#operation/cancelAllOrders
     * @return int Number of orders that were cancelled
     */
    public function cancelAllOrders(): int;

    /**
     * CancelAllOrdersAfter provides a "Dead Man's Switch" mechanism to protect the client from network malfunction,
     * extreme latency or unexpected matching engine downtime. The client can send a request with a timeout (in seconds),
     * that will start a countdown timer which will cancel all client orders when the timer expires. The client has to
     * keep sending new requests to push back the trigger time, or deactivate the mechanism by specifying a timeout of 0.
     * If the timer expires, all orders are cancelled and then the timer remains disabled until the client provides a
     * new (non-zero) timeout.
     *
     * The recommended use is to make a call every 15 to 30 seconds, providing a timeout of 60 seconds. This allows
     * the client to keep the orders in place in case of a brief disconnection or transient delay, while keeping them
     * safe in case of a network breakdown. It is also recommended to disable the timer ahead of regularly scheduled
     * trading engine maintenance (if the timer is enabled, all orders will be cancelled when the trading engine comes
     * back from downtime - planned or otherwise).
     *
     * @see https://docs.kraken.com/rest/#operation/cancelAllOrdersAfter
     * @param int $timeout Duration (in seconds) to set/extend the timer by (0 - disable)
     */
    public function cancelAllOrdersAfter(int $timeout): CancelOrdersAfterTimeout;

    /**
     * An authentication token must be requested via this REST API endpoint in order to connect to and authenticate
     * with our Websockets API. The token should be used within 15 minutes of creation, but it does not expire once a
     * successful Websockets connection and private subscription has been made and is maintained.
     */
    public function getWebsocketsToken(): WebsocketToken;

    /**
     * Retrieve methods available for depositing a particular asset.
     * @see https://docs.kraken.com/rest/#operation/getDepositMethods
     * @param string $asset Asset being deposited
     * @return DepositMethods|null
     */
    public function getDepositMethods(string $asset): ?DepositMethods;

    /**
     * Retrieve (or generate a new) deposit addresses for a particular asset and method.
     * @see https://docs.kraken.com/rest/#operation/getDepositAddresses
     * @param string $asset Asset being deposited
     * @param string $method Name of the deposit method
     * @param bool $new Whether or not to generate a new address
     * @return DepositAddresses[]
     */
    public function getDepositAddresses(string $asset, string $method, bool $new = false): array;

    /**
     * Retrieve fee information about potential withdrawals for a particular asset, key and amount.
     * @see https://docs.kraken.com/rest/#operation/getWithdrawalInformation
     * @param string $asset Asset being withdrawn
     * @param string $key Withdrawal key name, as set up on your account
     * @param BigDecimal $amount Amount to be withdrawn
     * @return WithdrawalInformation
     */
    public function getWithdrawalInformation(string $asset, string $key, BigDecimal $amount): WithdrawalInformation;

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
     * @see https://docs.kraken.com/api/docs/rest-api/list-strategies
     * @param  string|null  $asset
     * @param  array  $lockType
     * @param  bool  $ascending
     * @return EarnStrategies
     */
    public function getEarnStrategies(?string $asset = null, array $lockType = ['flex', 'bonded', 'instant'], bool $ascending = false): EarnStrategies;

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
     * @see https://docs.kraken.com/api/docs/rest-api/list-allocations
     * @param  string  $convertedAsset The currency to which amounts should be converted. Default is 'USD'.
     * @param  bool  $hideZeroAllocations Whether to hide allocations with zero balance. Default is false.
     * @param  bool  $ascending Whether to sort the results in ascending order. Default is false.
     * @return Allocations The list of allocations.
     */
    public function getEarnAllocations(string $convertedAsset = 'USD', bool $hideZeroAllocations = false, bool $ascending = false): Allocations;

    /**
     * Allocate funds to the Strategy.
     *
     * Requires the `Earn Funds` API key permission. The amount must always be defined.
     *
     * This method is asynchronous. A couple of preflight checks are performed synchronously on behalf of the
     * method before it is dispatched further. The client is required to poll the result using the
     * `/0/private/Earn/AllocateStatus` endpoint.
     *
     * There can be only one (de)allocation request in progress for a given user and strategy at any time. While the operation is in progress:
     * - `pending` attribute in `/Earn/Allocations` response for the strategy indicates that funds are being allocated,
     * - `pending` attribute in `/Earn/AllocateStatus` response will be true.
     *
     * Following specific errors within `Earnings` class can be returned by this method:
     * - Minimum allocation: `EEarnings:Below min:(De)allocation operation amount less than minimum`
     * - Allocation in progress: `EEarnings:Busy:Another (de)allocation for the same strategy is in progress`
     * - Service temporarily unavailable: `EEarnings:Busy`. Try again in a few minutes.
     * - User tier verification: `EEarnings:Permission denied:The user's tier is not high enough`
     * - Strategy not found: `EGeneral:Invalid arguments:Invalid strategy ID`
     *
     * @see https://docs.kraken.com/api/docs/rest-api/allocate-strategy
     * @param  BigDecimal  $amount
     * @param  string  $strategyId
     * @return bool
     */
    public function allocateEarnFunds(BigDecimal $amount, string $strategyId): bool;


    /**
     * Deallocate funds from a strategy.
     *
     * Requires the `Earn Funds` API key permission. The amount must always be defined.
     *
     * This method is asynchronous. A couple of preflight checks are performed synchronously on behalf of the
     * method before it is dispatched further. If the method returns HTTP 202 code, the client is required to poll
     * the result using the `/Earn/DeallocateStatus` endpoint.
     *
     * There can be only one (de)allocation request in progress for a given user and strategy. While the operation is in progress:
     * - `pending` attribute in `Allocations` response for the strategy will hold the amount that is being deallocated (negative amount)
     * - `pending` attribute in `DeallocateStatus` response will be true.
     *
     * Following specific errors within `Earnings` class can be returned by this method:
     * - Minimum allocation: `EEarnings:Below min:(De)allocation operation amount less than minimum allowed`
     * - Allocation in progress: `EEarnings:Busy:Another (de)allocation for the same strategy is in progress`
     * - Strategy not found: `EGeneral:Invalid arguments:Invalid strategy ID`
     *
     * @see https://docs.kraken.com/api/docs/rest-api/deallocate-strategy
     * @param  BigDecimal  $amount
     * @param  string  $strategyId
     * @return bool
     */
    public function deallocateEarnFunds(BigDecimal $amount, string $strategyId): bool;
}
