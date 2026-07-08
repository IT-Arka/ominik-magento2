<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Integration;

use Omnik\Core\Api\RegionRepositoryInterface;
use Omnik\Core\Api\ConfigInterface;
use Omnik\Core\Helper\Config as IntegrationHelper;
use Omnik\Core\Helper\Telephone;
use Omnik\Core\Logger\Logger;
use Omnik\Core\Api\SplitOrderInterface;
use Omnik\Core\Helper\SplitOrder\Data as SplitHelper;
use Omnik\Core\Model\ShippingAmount;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class Params
{
    public const TIME_CONFIG_DATE = "T00:00:00.000000";

    /**
     * Formato de data/hora esperado pela Omnik (sem offset de timezone).
     */
    public const DATETIME_FORMAT = "Y-m-d\TH:i:s.u";

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
        private readonly OrderRepositoryInterface    $orderRepository,
        private readonly TimezoneInterface           $timezone,
        private readonly SplitHelper                 $splitHelper
    ) {

    }

    /**
     * Converte uma data armazenada em UTC para o fuso horário da loja,
     * preservando a hora real do evento, no formato esperado pela Omnik.
     *
     * @param string|null $date
     * @return string
     */
    private function formatDateTime(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        return $this->timezone->date(new \DateTime($date))->format(self::DATETIME_FORMAT);
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

            $params["createDate"] = $this->formatDateTime($order->getCreatedAt());
            $params["lastUpdate"] = $this->formatDateTime($order->getUpdatedAt());
            $params["tenant"] = $this->getOrderTenant($order);
            $params["orderData"]["orderDate"] = $this->formatDateTime($order->getCreatedAt());

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
            $params["freightData"]["chargedValue"] = round((float)$order->getShippingAmount(), 2);

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

            $itemCount    = count($items);
            $totalFreight = round((float)$order->getShippingAmount(), 2);
            // Frete base por item (arredondado a 2 casas). O resíduo de arredondamento é
            // somado ao ÚLTIMO item para que a soma dos itens feche EXATAMENTE com o total —
            // a Omnik valida total == soma(itens) e rejeita (HTTP 422) qualquer divergência
            // de centavos (ex.: 56.75 / 2 = 28.375 -> 28.38 + 28.38 = 56.76 != 56.75).
            $freightItem    = $itemCount > 0 ? round($totalFreight / $itemCount, 2) : 0.0;
            $allocatedSoFar = 0.0;

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

                $isLastItem        = ($i === $itemCount - 1);
                $itemFreight       = $isLastItem
                    ? round($totalFreight - $allocatedSoFar, 2)
                    : $freightItem;
                $allocatedSoFar    = round($allocatedSoFar + $itemFreight, 2);

                $params["items"][$i]["skuData"]["id"]               = $skuId;
                $params["items"][$i]["priceData"]["unitPrice"]       = $item->getPrice();
                $params["items"][$i]["priceData"]["discountUnit"]    = $item->getDiscountAmount();
                $params["items"][$i]["quantityData"]["quantity"]     = (int)$item->getQtyOrdered();
                $params["items"][$i]["freightData"]["chargedValue"]  = $itemFreight;
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
            $params["date"] = $this->formatDateTime($order->getUpdatedAt());
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
     * Resolve os dados de entrega de um pedido ÚNICO (não-split) a serem
     * enviados à Omnik.
     *
     * A Omnik/Intelipost exigem o `quotationId` e o `deliveryMethodId` REAIS
     * da cotação gerada pela Omnik — não o shipping_method sintético do
     * Magento (`omnik_<tenant>-<id>`). Esses valores ficam persistidos no
     * `body` da rate salva em `omnik_freight_rates`, indexada por
     * (quote_id, delivery_method_id, seller_tenant). Reaproveitamos a mesma
     * resolução usada pelo fluxo de split (`getOmnikRateSelected`) para manter
     * uma regra única entre pedido único e pedido filho.
     *
     * Fallback: quando não há rate Omnik aplicável (frete de contingência ou
     * método não-Omnik), mantém-se o shipping_method do Magento como antes,
     * garantindo que o envio não quebre para esses casos.
     *
     * @param Order|OrderInterface $order
     * @return array{deliveryEstimateBusinessDays: mixed, deliveryMethodId: mixed, description: mixed, quotationId: mixed, logisticProviderName?: mixed}
     */
    private function getDeliveryAddress($order): array
    {
        $shippingMethod      = (string)$order->getShippingMethod();
        $shippingMethodCode  = $order->getShippingMethod(true)->getMethod();
        $shippingDescription = $order->getShippingDescription();

        $fallback = [
            'deliveryMethodId' => $shippingMethodCode,
            'description' => $shippingDescription,
            'deliveryEstimateBusinessDays' => '',
            'quotationId' => $shippingMethod
        ];

        try {
            $item = current($order->getItems());
            if (!$item) {
                return $fallback;
            }

            $tenant = $this->splitHelper->getTenantByProductSku((string)$item->getSku());

            if (!$this->splitHelper->isOmnikShipping($shippingMethod)
                || $this->splitHelper->isContigency($shippingMethod, $tenant)) {
                return $fallback;
            }

            $rate = $this->splitHelper->getOmnikRateSelected(
                $shippingMethod,
                $tenant,
                (int)$order->getQuoteId()
            );

            if (empty($rate) || empty($rate['quotationId'])) {
                return $fallback;
            }

            return [
                'deliveryMethodId' => $rate['deliveryMethodId'] ?? $shippingMethodCode,
                'description' => $rate['description'] ?? $shippingDescription,
                'deliveryEstimateBusinessDays' => $rate['deliveryEstimateBusinessDays'] ?? '',
                'quotationId' => $rate['quotationId'],
                'logisticProviderName' => $rate['logisticProviderName'] ?? ''
            ];
        } catch (\Exception $e) {
            $this->logger->error('Omnik getDeliveryAddress: ' . $e->getMessage());
            return $fallback;
        }
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
