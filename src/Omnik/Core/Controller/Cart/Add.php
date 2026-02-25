<?php

namespace Omnik\Core\Controller\Cart;

use Omnik\Core\Helper\Checkout\Data as CheckoutHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Controller\Cart\Add as AddCore;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;

class Add extends AddCore
{
    /**
     * @param CheckoutHelper $checkoutHelper
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RequestQuantityProcessor|null $quantityProcessor
     */
    public function __construct(
        private readonly CheckoutHelper            $checkoutHelper,
        private readonly Context                   $context,
        private readonly ScopeConfigInterface      $scopeConfig,
        private readonly Session                   $checkoutSession,
        private readonly StoreManagerInterface     $storeManager,
        private readonly Validator                 $formKeyValidator,
        CustomerCart                               $cart,
        ProductRepositoryInterface                 $productRepository,
        private readonly ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository, $quantityProcessor);
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        $this->setSuperAttributes();
        return $this->_request;
    }

    /**
     * @return void
     */
    private function setSuperAttributes()
    {
        $params = $this->_request->getParams();
        if (isset($params['fc_super_attribute']) && is_string($params['fc_super_attribute']) && !empty($params['fc_super_attribute'])) {
            $params['super_attribute'] = $this->checkoutHelper->getSuperAttributeValues($params['fc_super_attribute']);
            $this->_request->setParams($params);
        }
    }
}
