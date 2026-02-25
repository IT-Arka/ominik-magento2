<?php

namespace Omnik\Core\Block\Order;

use Omnik\Core\Observer\IntegrationSalesOmnik;
use Omnik\Core\Model\Seller\ProductSeller;
use Omnik\Core\Helper\Sales\Data as Helper;
use Omnik\Core\Api\SplitOrderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Block\Order\Info as BlockInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Info extends BlockInfo
{
    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $helperPayment
     * @param AddressRenderer $rendererAddress
     * @param Helper $helper
     * @param TimezoneInterface $timezone
     * @param IntegrationSalesOmnik $integrationSalesOmnik
     * @param ProductSeller $productSeller
     * @param OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        public readonly TemplateContext        $context,
        public readonly Registry               $registry,
        public readonly PaymentHelper          $helperPayment,
        public readonly AddressRenderer        $rendererAddress,
        private readonly Helper                $helper,
        private readonly TimezoneInterface     $timezone,
        private readonly IntegrationSalesOmnik $integrationSalesOmnik,
        private readonly ProductSeller         $productSeller,
        private readonly OrderFactory          $orderFactory,
        array                                  $data = []
    ) {
        parent::__construct($context, $registry, $helperPayment, $rendererAddress, $data);
    }

    /**
     * @param mixed $date
     * @param string $format
     * @return string
     */
    public function getFormattedDate(mixed $date, string $format): string
    {
        return $this->timezone->date($date)->format($format);
    }

    /**
     * @param $order
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getChildOrders($order)
    {
        return $this->integrationSalesOmnik->getChildOrders($order);
    }

    /**
     * @param $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildOrdersHtml($order)
    {
        $html = '';
        try {
            foreach ($this->getChildOrders($order) as $child) {
                $item = current($child->getItems());
                $seller = $this->productSeller->getSellerNameBySku($item->getSku());

                $url = $this->getBaseUrl() . 'sales/order/view/order_id/' . $child->getId();
                $html .= "<p><span><a href='{$url}'>#{$child->getIncrementId()}</a> - {$seller}</span></p>";
            }
        } catch (\Exception $e) {

        }

        return $html;
    }

    /**
     * @param $order
     * @return \Magento\Sales\Model\Order
     */
    public function getParentOrder($order)
    {
        $parentId = $order->getSplitOrderParentId();
        return $this->orderFactory->create()->load($parentId);
    }

    /**
     * @param $order
     * @return string
     */
    public function getParentOrderHtml($order)
    {
        $html = '';
        $parentOrder = $this->getParentOrder($order);
        $url = $this->getBaseUrl() . 'sales/order/view/order_id/' . $parentOrder->getId();
        $html .= "<p><span><a href='{$url}'>#{$parentOrder->getIncrementId()}</a></span></p>";

        return $html;
    }

    /**
     * @param $order
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSellerNameByOrderChild($order): string
    {
        return $this->helper->getSellerNameByOrderChild($order);
    }

    /**
     * @param $order
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFreightSeller($order): string
    {
        $description = $order->getShippingDescription();
        $splitDescription = explode('-', $description);

        $method = $this->getShippingMethodChild($order, $splitDescription[1]);

        return $splitDescription[0] . ' - ' . $method;
    }

    /**
     * @param $order
     * @param $methods
     * @return string
     * @throws NoSuchEntityException
     */
    public function getShippingMethodChild($order, $methods)
    {
        $sellerName = $this->getSellerNameByOrderChild($order);

        $methods = explode('<br>', $methods);

        foreach ($methods as $method) {
            if (str_contains($method, $sellerName)) {
                return trim($method ?? '');
            }
        }

        return '';
    }

    /**
     * @param $order
     * @return int
     */
    public function getItemsValue($order)
    {
        $total = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $total += ($item->getPrice() * $item->getQtyOrdered());
        }

        return $total;
    }

    /**
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]);
    }

    /**
     * @param $order
     * @param $prefix
     * @return array|string|string[]|null
     */
    public function getInvoiceComment($order, $prefix)
    {
        $invoices = $order->getInvoiceCollection()->getItems();
        foreach ($invoices as $invoice) {
            foreach ($invoice->getCommentsCollection() as $invoiceComment) {
                if (str_contains($invoiceComment->getComment(), $prefix)) {
                    return str_replace($prefix, "", $invoiceComment->getComment());
                }
            }
        }
        return null;
    }

    /**
     * @param $order
     * @return string
     */
    public function getInvoiceHtml($order)
    {
        $html = '';

        $keyNfe = $this->getInvoiceComment($order, 'KEY NFE:');
        if ($keyNfe) {
            $html .= "<p>Chave da Nota: " . trim($keyNfe ?? '') . "</p>";
        }
        $linkXml = $this->getInvoiceComment($order, 'LINK XML:');
        if ($linkXml) {
            $html .= "<p><a href='" . trim($linkXml ?? '') . "'>Download XML</a></p>";
        }
        $linkDanfe = $this->getInvoiceComment($order, 'LINK DANFE:');
        if ($linkDanfe) {
            $html .= "<p><a href='" . trim($linkDanfe ?? '') . "'>Download DANFE</a></p>";
        }

        return $html;
    }

    /**
     * @param $method
     * @return string|void
     */
    public function getPaymentMethodTranslation($method)
    {
        if ($method === 'getnet_paymentmagento_pix') {
            return 'Pix';
        }

        if ($method === 'getnet_paymentmagento_boleto') {
            return 'Boleto Bancário';
        }

        if ($method === 'getnet_paymentmagento_cc') {
            return 'Cartão de Crédito';
        }

        return $method;
    }

}
