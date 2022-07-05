<?php

namespace App\Http\Controllers;

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Regions;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use Buzz\Client\Curl;
use AmazonPHP\SellingPartner\Exception\ApiException;
use AmazonPHP\SellingPartner\Configuration;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Illuminate\Support\Facades\DB;

/**
 * Inventory and order items importing to database using Amazon SP API
 */
class SpapiController extends Controller
{
    /**
     * __construct
     *
     * @param  Psr17Factory $factory
     * @return void
     */
    public function __construct(Psr17Factory $factory)
    {
        $this->token = env('TOKEN');
        $this->factory = $factory;
        $this->client = new Curl($factory);
        $this->configuration = Configuration::forIAMUser(
            env('LWACLIENTID'),
            env('LWACLIENTIDSECRET'),
            env('AWSACCESSKEY'),
            env('AWSSECRETKEY')
        );
        $this->logger = new Logger('name');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/sp-api-php.log', Logger::EMERGENCY));
    }

    /**
     * Import inventory items to database
     *
     * @param  string $date_time
     * @return string
     */
    public function getInventoryItems($date_time = null)
    {
        $sdk = SellingPartnerSDK::create($this->client, $this->factory, $this->factory, $this->configuration, $this->logger);
        $accessToken = $sdk->oAuth()->exchangeRefreshToken($this->token);

        // Check if there is a next token in DB
        $next_token_db = DB::table('tokens')->first();
        $next_token_db = $next_token_db != null ? $next_token_db->inventory_token : null;

        try {
            $inventory_items = $sdk->fbaInventory()->getInventorySummaries(
                $accessToken,
                Regions::NORTH_AMERICA,
                'Marketplace',
                Marketplace::US()->id(),
                [Marketplace::US()->id()],
                'false',
                $date_time,
                [''],
                $next_token_db
            );
            $next_token_res = $inventory_items->getPagination() != null ? $inventory_items->getPagination()->getNextToken() : null;
            DB::table('tokens')
                ->updateOrInsert(
                    ['id' => 1],
                    ['inventory_token' => $next_token_res]
                );

            $inventory_items = $inventory_items->getPayload()->getInventorySummaries();
            $count = 0;

            foreach ($inventory_items as $inventory_item) {
                $inventory_item_data = [
                    'asin' => $inventory_item->getAsin(),
                    'fn_sku' => $inventory_item->getFnSku(),
                    'seller_sku' => $inventory_item->getSellerSku(),
                    'condition' => $inventory_item->getCondition(),
                    'inventory_details' => $inventory_item->getInventoryDetails(),
                    'product_name' => $inventory_item->getProductName(),
                    'total_quantity' => $inventory_item->getTotalQuantity(),
                    'last_updated_time' => $inventory_item->getLastUpdatedTime()
                ];
                $ukey = ['asin'];
                $cols_to_update = [
                    'asin','asin','fn_sku','seller_sku','condition','inventory_details','product_name','total_quantity','last_updated_time'
                ];
                $res = DB::table('inventory_items')->upsert([$inventory_item_data], $ukey, $cols_to_update);
                $res === 1 ? $count++ : null;
            }
            return response('Import done, ' . $count . ' records were imported.');
        } catch (ApiException $exception) {
            dump($exception->getMessage());
            dd($exception->getResponseBody());
        }
    }

    /**
     * Import order items to database
     *
     * @param  int $order_id
     * @return void
     */
    public function getOrderItems($order_id)
    {
        $sdk = SellingPartnerSDK::create($this->client, $this->factory, $this->factory, $this->configuration, $this->logger);
        $accessToken = $sdk->oAuth()->exchangeRefreshToken($this->token);

        // Retry if a rate limit is exceeded
        for ($try = 0; $try < 5; $try++) {
            try {
                $order_items = $sdk->orders()->getOrderItems(
                    $accessToken,
                    Regions::NORTH_AMERICA,
                    $order_id
                );
                $order_items = $order_items->getPayload()->getOrderItems();
                foreach ($order_items as $order_item) {
                    // Declare variables for order item data sub-objects and check if they are not null
                    $points_granted = $order_item->getPointsGranted() != null ? $order_item->getPointsGranted()->getPointsMonetaryValue() : null;
                    $shipping_price = $order_item->getShippingPrice() != null ? $order_item->getShippingPrice()->getAmount() : null;
                    $shipping_tax = $order_item->getShippingTax() != null ? $order_item->getShippingTax()->getAmount() : null;
                    $shipping_discount = $order_item->getShippingDiscount() != null ? $order_item->getShippingDiscount()->getAmount() : null;
                    $shipping_discount_tax = $order_item->getShippingDiscountTax() != null ? $order_item->getShippingDiscountTax()->getAmount() : null;
                    $cod_fee = $order_item->getCodFee() != null ? $order_item->getCodFee()->getAmount() : null;
                    $cod_fee_discount = $order_item->getCodFeeDiscount() != null ? $order_item->getCodFeeDiscount()->getAmount() : null;
                    $tax_collection_model = $order_item->getTaxCollection() != null ? $order_item->getTaxCollection()->getModel() : null;
                    $tax_collection_model = $order_item->getTaxCollection() != null ? $order_item->getTaxCollection()->getModel() : null;
                    $tax_collection_reasponsible_party  = $order_item->getTaxCollection() != null ? $order_item->getTaxCollection()->getResponsibleParty() : null;
                    $item_price = $order_item->getItemPrice() != null ? $order_item->getItemPrice()->getAmount() : null;
                    $currency_code = $order_item->getItemPrice() != null ? $order_item->getItemPrice()->getCurrencyCode() : null;
                    $item_tax = $order_item->getItemTax() != null ? $order_item->getItemTax()->getAmount() : null;
                    $promotion_discount = $order_item->getPromotionDiscount() != null ? $order_item->getPromotionDiscount()->getAmount() : null;
                    $promotion_discount_tax = $order_item->getPromotionDiscountTax() != null ? $order_item->getPromotionDiscountTax()->getAmount() : null;

                    $order_item_data = [
                        'amazon_order_id' => $order_id,
                        'asin' => $order_item->getAsin(),
                        'seller_sku' => $order_item->getSellerSku(),
                        'order_item_id' => $order_item->getOrderItemId(),
                        'title' => $order_item->getTitle(),
                        'quantity_ordered' => $order_item->getQuantityOrdered(),
                        'quantity_shipped' => $order_item->getQuantityShipped(),
                        'number_of_items' => $order_item->getProductInfo()->getNumberOfItems(),
                        'points_granted' => $points_granted,
                        'item_price' => $item_price,
                        'currency_code' => $currency_code,
                        'shipping_price' => $shipping_price,
                        'item_tax' => $item_tax,
                        'shipping_tax' => $shipping_tax,
                        'shipping_discount' => $shipping_discount,
                        'shipping_discount_tax' => $shipping_discount_tax,
                        'promotion_discount' => $promotion_discount,
                        'promotion_discount_tax' => $promotion_discount_tax,
                        'promotion_ids' => json_encode($order_item->getPromotionIds()),
                        'cod_fee' => $cod_fee,
                        'cod_fee_discount' => $cod_fee_discount,
                        'is_gift' => $order_item->getIsGift(),
                        'condition_note' => $order_item->getConditionNote(),
                        'condition_id' => $order_item->getConditionId(),
                        'condition_subtype_id' => $order_item->getConditionSubtypeId(),
                        'scheduled_delivery_start_date' => $this->convertDate($order_item->getScheduledDeliveryStartDate()),
                        'scheduled_delivery_end_date' => $this->convertDate($order_item->getScheduledDeliveryEndDate()),
                        'price_designation' => $order_item->getPriceDesignation(),
                        'tax_collection_model' => $tax_collection_model,
                        'tax_collection_reasponsible_party' => $tax_collection_reasponsible_party,
                        'serial_number_required' => $order_item->getSerialNumberRequired(),
                        'is_transparency' => $order_item->getIsTransparency(),
                        'ioss_number' => $order_item->getIossNumber(),
                        'store_chain_store_id' => $order_item->getStoreChainStoreId(),
                        'deemed_reseller_category' => $order_item->getDeemedResellerCategory(),
                        'buyer_info' => $order_item->getBuyerInfo()
                    ];
                    $ukey = ['asin'];
                    $cols_to_update = [
                        'amazon_order_id','asin','seller_sku','order_item_id','title','quantity_ordered','quantity_shipped','number_of_items','points_granted','item_price','currency_code','shipping_price','item_tax','shipping_tax','shipping_discount','shipping_discount_tax','promotion_discount','promotion_discount_tax','promotion_ids','cod_fee','cod_fee_discount','is_gift','condition_note','condition_id','condition_subtype_id','scheduled_delivery_start_date','scheduled_delivery_end_date','price_designation','tax_collection_model','tax_collection_reasponsible_party','serial_number_required','is_transparency','ioss_number','store_chain_store_id','deemed_reseller_category','buyer_info'
                    ];
                    $res = DB::table('order_items')->upsert([$order_item_data], $ukey, $cols_to_update);
                    sleep(2);
                }
            } catch (ApiException $exception) {
                // Dump only if it was a final try
                if ($try == 4) {
                    dump($exception->getMessage());
                    dump($exception->getResponseBody());
                }
                sleep(2);
                continue;
            }
            break;
        }
    }

    /**
     * Get order enitites
     *
     * @param  mixed $date
     * @return string
     */
    public function getOrders($date = null)
    {
        // Check if a date is in a proper format
        if ($date != null) {
            $validate = preg_match('/\d\d\d\d-\d\d-\d\d/', $date, $validate_result);
            if (empty($validate)) {
                return response('The date must be in YYYY-MM-DD format');
            }
        }

        // Check if there is a next token in DB
        $next_token_db = DB::table('tokens')->first();
        $next_token_db = $next_token_db != null ? $next_token_db->orders_token : null;

        $sdk = SellingPartnerSDK::create($this->client, $this->factory, $this->factory, $this->configuration, $this->logger);
        $accessToken = $sdk->oAuth()->exchangeRefreshToken($this->token);

        try {
            $orders = $sdk->orders()->getOrders(
                $accessToken,
                Regions::NORTH_AMERICA,
                [Marketplace::US()->id()],
                $date,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $next_token_db
            );
        } catch (ApiException $exception) {
            dump($exception->getMessage());
            dd($exception->getResponseBody());
        }

        // Check if there is a next token in response
        $next_token_res = $orders->getPayload()->getNextToken();
        DB::table('tokens')
            ->updateOrInsert(
                ['id' => 1],
                ['orders_token' => $next_token_res]
            );

        $orders = $orders->getPayload()->getOrders();
        $count = 0;

        foreach ($orders as $order) {
            // Declare variables for order's data sub-objects and check if they are not null
            $amount = $currency_code = $city = $state = $postal_code = $street = $address = $country = $order_id = '';
            $total = $order->getOrderTotal();
            if ($total != null) {
                $amount = $total->getAmount();
                $currency_code = $total->getCurrencyCode();
            }

            $address = $order->getShippingAddress();
            if ($address != null) {
                $city = $address->getCity();
                $state = $address->getStateOrRegion();
                $postal_code = $address->getPostalCode();
                $street = $address->getAddressLine1() . ' ' . $address->getAddressLine2() . ' ' . $address->getAddressLine3();
                $country = $address->getCountryCode();
            }

            // Fetch order items
            $order_id = $order->getAmazonOrderId();
            $order_items = $this->getOrderItems($order_id);

            $data = [
                'amazon_order_id' => $order_id,
                'seller_order_id' => $order->getSellerOrderId(),
                'purchase_date' => $this->convertDate($order->getPurchaseDate()),
                'last_update_date' => $this->convertDate($order->getLastUpdateDate()),
                'order_status' => $order->getOrderStatus(),
                'fulfillment_channel' => $order->getFulfillmentChannel(),
                'sales_channel' => $order->getSalesChannel(),
                'order_channel' => $order->getOrderChannel(),
                'ship_service_level' => $order->getShipServiceLevel(),
                'order_total' => $amount,
                'currency_code' => $currency_code,
                'number_of_items_shipped' => $order->getNumberOfItemsShipped(),
                'number_of_items_unshipped' => $order->getNumberOfItemsUnshipped(),
                'payment_execution_detail' => $order->getPaymentExecutionDetail(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_method_details' => json_encode($order->getPaymentMethodDetails()),
                'marketplace_id' => $order->getMarketplaceId(),
                'shipment_service_level_category' => $order->getShipmentServiceLevelCategory(),
                'easy_ship_shipment_status' => $order->getEasyShipShipmentStatus(),
                'cba_displayable_shipping_label' => $order->getCbaDisplayableShippingLabel(),
                'order_type' => $order->getOrderType(),
                'earliest_ship_date' => $this->convertDate($order->getEarliestShipDate()),
                'latest_ship_date' => $this->convertDate($order->getLatestShipDate()),
                'earliest_delivery_date' => $this->convertDate($order->getEarliestDeliveryDate()),
                'latest_delivery_date' => $this->convertDate($order->getLatestDeliveryDate()),
                'is_business_order' => $order->getIsBusinessOrder(),
                'is_prime' => $order->getIsPrime(),
                'is_premium_order' => $order->getIsPremiumOrder(),
                'is_global_express_enabled' => $order->getIsGlobalExpressEnabled(),
                'replaced_order_id' => $order->getReplacedOrderId(),
                'is_replacement_order' => $order->getIsReplacementOrder(),
                'promise_response_due_date' => $this->convertDate($order->getPromiseResponseDueDate()),
                'is_estimated_ship_date_set' => $order->getIsEstimatedShipDateSet(),
                'is_sold_by_ab' => $order->getIsSoldByAb(),
                'default_ship_from_location_address' => $order->getDefaultShipFromLocationAddress(),
                'buyer_invoice_preference' => $order->getBuyerInvoicePreference(),
                'buyer_tax_information' => $order->getBuyerTaxInformation(),
                'fulfillment_instruction' => $order->getFulfillmentInstruction(),
                'is_ispu' => $order->getIsIspu(),
                'marketplace_tax_info' => $order->getMarketplaceTaxInfo(),
                'seller_display_name' => $order->getSellerDisplayName(),
                'buyer_info' => (string)$order->getBuyerInfo(),
                'automated_shipping_settings' => $order->getAutomatedShippingSettings(),
                'city' => $city,
                'state' => $state,
                'postal_code' => $postal_code,
                'street' => $street,
                'country' => $country
            ];
            $ukey = [
                'amazon_order_id'
            ];
            $cols_to_update = [
                'amazon_order_id','seller_order_id','purchase_date','last_update_date','order_status','fulfillment_channel','sales_channel','order_channel','ship_service_level','order_total','currency_code','number_of_items_shipped','number_of_items_unshipped','payment_execution_detail','payment_method','payment_method_details','marketplace_id','shipment_service_level_category','easy_ship_shipment_status','cba_displayable_shipping_label','order_type','earliest_ship_date','latest_ship_date','earliest_delivery_date','latest_delivery_date','is_business_order','is_prime','is_premium_order','is_global_express_enabled','replaced_order_id','is_replacement_order','promise_response_due_date','is_estimated_ship_date_set','is_sold_by_ab','default_ship_from_location_address','buyer_invoice_preference','buyer_tax_information','fulfillment_instruction','is_ispu','marketplace_tax_info','seller_display_name','buyer_info','automated_shipping_settings','city','state', 'postal_code','street','country'
            ];
            $res = DB::table('orders')->upsert([$data], $ukey, $cols_to_update);
            $res === 1 ? $count++ : null;
        }
        return response('Import done, ' . $count . ' records were imported.');
    }

    /**
     * Convert a timezone datetime to a simple datetime for MySQL compatibility
     *
     * @param  mixed $datetime
     * @return string
     */
    public function convertDate($datetime)
    {
        if ($datetime != '') {
            $datetime = str_replace(['T','Z'], [' ', ''], $datetime);
        } else {
            $datetime = null;
        }
        return $datetime;
    }
}
