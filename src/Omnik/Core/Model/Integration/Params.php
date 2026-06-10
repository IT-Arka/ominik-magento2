<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration;

use Omnik\Core\Api\RegionRepositoryInterface;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Helper\Config as IntegrationHelper;
use Omnik\Core\Helper\Telephone;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Model\ShippingAmount;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class Params
{
    public const TIME_CONFIG_DATE = "T00:00:00.000000";

    public const GETNET_BOLETO = 'getnet_paymentmagento_boleto';
    public const GETNET_CARD = 'getnet_paymentmagento_cc';


    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Json $json
     * @param RegionRepositoryInterface $regionRepositoryInterface
     * @param Telephone $telephone
     * @param IntegrationHelper $integrationHelper
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ShippingAmount $shippingAmount
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ProductRepositoryInterface  $productRepositoryInterface,
        private readonly Json                        $json,
        private readonly RegionRepositoryInterface   $regionRepositoryInterface,
        private readonly Telephone                   $telephone,
        private readonly IntegrationHelper           $integrationHelper,
        private readonly Logger                      $logger,
        private readonly StoreManagerInterface       $storeManager,
        private readonly ShippingAmount              $shippingAmount,
        private readonly OrderFactory                $orderFactory,
        private readonly OrderRepositoryInterface    $orderRepository
    ) {

    }

    /**
     * @param Order|OrderInterface $order
     * @return string
     */
    public function createParameters(Order|OrderInterface $order): string
    {
        try {
            $storeId = (int)$order->getStoreId();
            $idx     = $this->integrationHelper->getStreetIndexes($storeId);
            $params  = [];

            $customer = $this->getCustomer($order->getCustomerId());
            $address  = $order->getShippingAddress();

            $params["createDate"] = date('Y-m-d', strtotime($order->getCreatedAt())) . self::TIME_CONFIG_DATE;
            $params["lastUpdate"] = date('Y-m-d', strtotime($order->getUpdatedAt())) . self::TIME_CONFIG_DATE;
            $params["tenant"] = $this->getOrderTenant($order);
            $params["orderData"]["orderDate"] = date('Y-m-d', strtotime($order->getCreatedAt())) . self::TIME_CONFIG_DATE;

            $parentOrder = $this->getParentOrder($order);
            $params["marketplaceData"]["marketPlaceId"] = $order->getIncrementId();
            $params["marketplaceData"]["siteId"] = $parentOrder->getIncrementId();
            $params["marketplaceData"]["marketplace"] = $this->integrationHelper->getTenantMarketplace($storeId);

            $params["customerData"]["name"] = $order->getCustomerName();
            $params["customerData"]["document"] = (string)$this->getDocumentCustomer($order);
            $params["customerData"]["documentType"] = ConfigInterface::TYPE_DOCUMENT_CPF;
            $params["customerData"]["customerType"] = ConfigInterface::CUSTOMER_TYPE_PF;
            $params["customerData"]["email"] = $order->getCustomerEmail();

            $street = $address->getStreet();
            $params["customerData"]["addressData"]["zipcode"]      = str_replace("-", "", $address->getPostcode());
            $params["customerData"]["addressData"]["address"]      = $street[$idx['address']] ?? '';
            $params["customerData"]["addressData"]["number"]       = $street[$idx['number']] ?? '';
            $params["customerData"]["addressData"]["neighborhood"] = $street[$idx['neighborhood']] ?? '';
            $params["customerData"]["addressData"]["complement"]   = $street[$idx['complement']] ?? '';
            $params["customerData"]["addressData"]["city"]         = $address->getCity();
            unset($street);

            $region = $this->regionRepositoryInterface->getById((int)$address->getRegionId());

            $params["customerData"]["addressData"]["state"]       = $region->getDefaultName();
            $params["customerData"]["addressData"]["stateAcronym"] = $region->getCode();
            $params["customerData"]["addressData"]["country"]     = $region->getCountryId();

            $telephone = $this->telephone->getTelephoneFormattedIntegration($this->getCustomerTelephone($customer) ?? '');

            $params["customerData"]["phones"][0]["type"]   = ConfigInterface::TELEPHONE_TYPE_NORMAL;
            $params["customerData"]["phones"][0]["ddi"]    = ConfigInterface::DDI;
            $params["customerData"]["phones"][0]["ddd"]    = $telephone["ddd"] ?? '';
            $params["customerData"]["phones"][0]["number"] = $telephone["number"] ?? '';
            $params["customerData"]["phones"][0]["local"]  = ConfigInterface::TELEPHONE_LOCAL_CELULAR;
            $params["freightData"]["chargedValue"] = $order->getShippingAmount();

            $params["deliveryData"]["deliveryDate"] = null;

            if ($order->getSplitOrderType() != null) {
                $deliveryData = $this->getOmnikFreightRateBody($order);
            } else {
                $deliveryData = $this->getDeliveryAddress($order);
            }

            $params["deliveryData"]["deliveryMethodId"]   = $deliveryData['deliveryMethodId'] ?? '';
            $params["deliveryData"]["deliveryMethodName"] = $deliveryData['description'] ?? '';
            $params["deliveryData"]["shippingMethod"]     = $deliveryData['logisticProviderName'] ?? '';
            $params["deliveryData"]["quotationId"]        = $deliveryData['quotationId'] ?? '';

            $billingAddress = $order->getBillingAddress();
            $street = $billingAddress->getStreet();
            $params["deliveryData"]["addressData"]["zipcode"]      = str_replace("-", "", $billingAddress->getPostcode());
            $params["deliveryData"]["addressData"]["address"]      = $street[$idx['address']] ?? '';
            $params["deliveryData"]["addressData"]["number"]       = $street[$idx['number']] ?? '';
            $params["deliveryData"]["addressData"]["neighborhood"] = $street[$idx['neighborhood']] ?? '';
            $params["deliveryData"]["addressData"]["complement"]   = $street[$idx['complement']] ?? '';
            $params["deliveryData"]["addressData"]["city"]         = $billingAddress->getCity();

            $orderRegion = $this->regionRepositoryInterface->getById((int)$billingAddress->getRegionId());

            $params["deliveryData"]["addressData"]["state"]        = $orderRegion->getDefaultName();
            $params["deliveryData"]["addressData"]["stateAcronym"] = $orderRegion->getCode();
            $params["deliveryData"]["addressData"]["country"]      = $orderRegion->getCountryId();

            $params["paymentData"]["addressData"]["zipcode"]      = str_replace("-", "", $billingAddress->getPostcode());
            $params["paymentData"]["addressData"]["address"]      = $street[$idx['address']] ?? '';
            $params["paymentData"]["addressData"]["number"]       = $street[$idx['number']] ?? '';
            $params["paymentData"]["addressData"]["neighborhood"] = $street[$idx['neighborhood']] ?? '';
            $params["paymentData"]["addressData"]["complement"]   = $street[$idx['complement']] ?? '';
            $params["paymentData"]["addressData"]["city"]         = $billingAddress->getCity();
            $params["paymentData"]["addressData"]["state"]        = $orderRegion->getDefaultName();
            $params["paymentData"]["addressData"]["stateAcronym"] = $orderRegion->getCode();
            $params["paymentData"]["addressData"]["country"]      = $orderRegion->getCountryId();
            unset($street);

            $items = $order->getAllVisibleItems();

            $paymentCode = $this->getPaymentMethod($order);
            $params["paymentData"]["formsPayments"][0]["type"]  = $paymentCode;
            $params["paymentData"]["formsPayments"][0]["value"] = $order->getGrandTotal();

            $payment = $this->getParentPayment($order);
            $params["paymentData"]["formsPayments"][0]["paymentId"] = $payment->getAdditionalInformation('payment_id');
            $params["paymentData"]["formsPayments"][0]["formPaymentId"] =
                $paymentCode == self::GETNET_BOLETO ? $payment->getLastTransId() : $payment->getAdditionalInformation('payment_id');
            $params["paymentData"]["formsPayments"][0]["status"] = '';
            $params["paymentData"]["formsPayments"][0]["sequence"] = $paymentCode == self::GETNET_CARD ?
                $payment->getAdditionalInformation('acquirer_transaction_id') : '';
            $params["paymentData"]["formsPayments"][0]["amountPlots"] =
                $paymentCode == self::GETNET_CARD ? $payment->getAdditionalInformation('cc_installments') : '';
            $params["paymentData"]["formsPayments"][0]["card"] =
                $paymentCode == self::GETNET_CARD ? $payment->getAdditionalInformation('cc_type') : '';
            $params["paymentData"]["formsPayments"][0]["operation"] = $payment->getAdditionalInformation('acquirer_transaction_id');
            $params["paymentData"]["formsPayments"][0]["nsu"] = $payment->getAdditionalInformation('terminal_nsu');

            $params["deliveryData"]["estimatedDeliveryDate"] = $this->getEstimatedDeliveryDate(
                $deliveryData['deliveryEstimateBusinessDays'],
                $order->getCreatedAt(),
                $paymentCode,
                $storeId
            );
            $params["orderValuesData"]["value"]      = $order->getGrandTotal();
            $params["orderValuesData"]["discount"]   = $order->getDiscountAmount();
            $params["orderValuesData"]["interest"]   = 0;
            $params["orderValuesData"]["netValue"]   = $order->getGrandTotal();
            $params["orderValuesData"]["grossValue"] = $order->getSubtotal();

            $itemCount   = count($items);
            $freightItem = $itemCount > 0 ? round((float)$order->getShippingAmount() / $itemCount, 2) : 0.0;

            $skuIdAttr = $this->integrationHelper->getAttrSkuId($storeId);
            $i = 0;
            foreach ($items as $item) {
                $product = $this->productRepositoryInterface->get($item->getSku());
                $skuId   = $product->getCustomAttribute($skuIdAttr)?->getValue();
                if (empty($skuId)) {
                    throw new \RuntimeException(
                        sprintf('Produto "%s" sem atributo "%s" (SKU ID Omnik) preenchido.', $item->getSku(), $skuIdAttr)
                    );
                }
                $params["items"][$i]["skuData"]["id"]               = $skuId;
                $params["items"][$i]["priceData"]["unitPrice"]       = $item->getPrice();
                $params["items"][$i]["priceData"]["discountUnit"]    = $item->getDiscountAmount();
                $params["items"][$i]["quantityData"]["quantity"]     = (int)$item->getQtyOrdered();
                $params["items"][$i]["freightData"]["chargedValue"]  = $freightItem;
                $i++;
            }

            $paramsSerialize = $this->json->serialize($params);
            $this->logger->info($paramsSerialize);

            return $paramsSerialize;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return "";
    }

    /**
     * @param Order|OrderInterface $order
     * @return array
     */
    public function createParametersForUpdate(Order|OrderInterface $order)
    {
        $params = [];
        try {
            $params["date"] = date('Y-m-d', strtotime($order->getUpdatedAt())) . self::TIME_CONFIG_DATE;
            return $params;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return [];
    }

    /**
     * @param Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOmnikFreightRateBody(Order $order)
    {
        $parentOrder = $this->orderFactory->create()->load($order->getSplitOrderParentId());
        return $this->shippingAmount->getOminikFreightRateBody($parentOrder, $order);
    }

    /**
     * @param Order $order
     * @return false|float|\Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     */
    public function getParentPayment(Order $order)
    {
        if ($order->getSplitOrderType() != SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
            return $order->getPayment();
        }
        $parentOrder = $this->orderFactory->create()->load($order->getSplitOrderParentId());
        return $parentOrder->getPayment();
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function getParentOrder(Order $order)
    {
        if ($order->getSplitOrderType() != SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
            return $order;
        }
        return $this->orderFactory->create()->load($order->getSplitOrderParentId());
    }

    /**
     * @param $order
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getOrderTenant($order)
    {
        $storeId   = (int)$order->getStoreId();
        $attrCode  = $this->integrationHelper->getAttrTenant($storeId);
        $item      = current($order->getItems());
        $product   = $this->productRepositoryInterface->get($item->getSku());
        $tenantVal = $product->getCustomAttribute($attrCode)?->getValue();
        if (empty($tenantVal)) {
            throw new \RuntimeException(
                sprintf('Produto "%s" sem atributo "%s" (Tenant) preenchido.', $item->getSku(), $attrCode)
            );
        }
        return $tenantVal;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomer($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * @param $order
     * @return mixed|string
     */
    private function getPaymentMethod($order)
    {
        try {
            if ($order->getData(SplitOrderInterface::SPLIT_ORDER_TYPE) !== SplitOrderInterface::SPLIT_ORDER_TYPE_CHILD) {
                return $order->getPayment()->getMethodInstance()->getCode();
            }
            $parentOrderId = $order->getData(SplitOrderInterface::SPLIT_ORDER_PARENT_ID);
            $parentOrder = $this->orderRepository->get($parentOrderId);

            if (!$parentOrder) {
                return $order->getPayment()->getMethodInstance()->getCode();
            }

            return $parentOrder->getPayment()->getMethodInstance()->getCode();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        return "";
    }

    /**
     * @param $deliveryDays
     * @param $orderDate
     * @param $paymentCode
     * @param int $storeId
     * @return string|null
     */
    private function getEstimatedDeliveryDate($deliveryDays, $orderDate, $paymentCode, int $storeId = 0)
    {
        if (!$deliveryDays) {
            return null;
        }

        $days = (int)$deliveryDays;
        if ($paymentCode === self::GETNET_BOLETO) {
            $days += $this->integrationHelper->getBoletoExtraDays($storeId);
        }

        $date = new \DateTime($orderDate);
        $date = $this->_addBusinessDays($date, $days);

        return $date->format('Y-m-d') . self::TIME_CONFIG_DATE;
    }

    /**
     * @param \DateTime $date
     * @param int $days
     * @return \DateTime
     */
    private function _addBusinessDays(\DateTime $date, int $days): \DateTime
    {
        $added = 0;
        while ($added < $days) {
            $date->modify('+1 day');
            // 1=Mon ... 5=Fri, 6=Sat, 7=Sun
            if ((int)$date->format('N') < 6) {
                $added++;
            }
        }
        return $date;
    }

    /**
     * @param $customer
     * @return string
     */
    private function getCustomerTelephone($customer): string
    {
        $telephone = '';
        $addresses = $customer->getAddresses();
        foreach ($addresses as $address) {
            $telephone = $address->getTelephone();
            break;
        }

        return $telephone;
    }

    /**
     * @param mixed $order
     * @return array{deliveryEstimateBusinessDays: string, deliveryMethodId: mixed, description: mixed, quotationId: mixed}
     */
    private function getDeliveryAddress($order): array
    {
        // $order = $this->orderRepository->get($order->getId());
        $shippingMethod = $order->getShippingMethod();
        $shippingMethodCode = $order->getShippingMethod(true)->getMethod();
        $shippingDescription = $order->getShippingDescription();

        return [
            'deliveryMethodId' => $shippingMethodCode,
            'description' => $shippingDescription,
            'deliveryEstimateBusinessDays' => '',
            'quotationId' => $shippingMethod
        ];
    }

    /**
     * @param mixed $order
     * @return string
     */
    private function getDocumentCustomer($order): string
    {
        $addresses = $order->getAddresses();
        foreach ($addresses as $address) {
            if ($address->getAddressType() == 'billing') {
                if ($address->getVatId() == null) {
                    return (string)$order->getCustomerTaxvat();
                }
                return (string)$address->getVatId();
            }
        }
        return '';
    }
}
