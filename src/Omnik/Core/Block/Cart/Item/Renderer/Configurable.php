<?php

namespace Omnik\Core\Block\Cart\Item\Renderer;

use Omnik\Core\Model\Config\Configurable\ProductsOptions;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableData;
use Magento\Catalog\Api\ProductRepositoryInterface;


class Configurable extends Renderer
{
    /**
     * @param ProductsOptions $productsOptions
     * @param Context $context
     * @param Configuration $productConfig
     * @param Session $checkoutSession
     * @param Data $urlHelper
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param ConfigurableData $configurableData
     * @param ImageBuilder $imageBuilder
     * @param ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param Manager $moduleManager
     * @param array $data
     * @param ItemResolverInterface|null $itemResolver
     */
    public function __construct(
        private readonly ProductsOptions                 $productsOptions,
        private readonly Context                         $context,
        private readonly Configuration                   $productConfig,
        private readonly Session                         $checkoutSession,
        private readonly Data                            $urlHelper,
        private readonly InterpretationStrategyInterface $messageInterpretationStrategy,
        private readonly ConfigurableData                $configurableData,
        private readonly ProductRepositoryInterface      $productRepository,
        ImageBuilder                                     $imageBuilder,
        ManagerInterface                                 $messageManager,
        PriceCurrencyInterface                           $priceCurrency,
        Manager                                          $moduleManager,
        array                                            $data = [],
        public readonly ?ItemResolverInterface           $itemResolver = null
    ) {
        parent::__construct($context, $productConfig, $checkoutSession, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $data, $itemResolver);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getItemsByVendor()
    {
        return $this->productsOptions->separeItemsByVendor($this->checkoutSession->getQuote()->getItems());
    }

    /**
     * @param $item
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSuperAttributeData($item)
    {
        $selectedOptions = [];
        $selectedConfigOptions = $item->getProductOption()->getExtensionAttributes()->getConfigurableItemOptions();
        foreach ($selectedConfigOptions as $configOption) {
            $selectedOptions[$configOption->getOptionId()] = $configOption->getOptionValue();
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($item->getProduct()->getId());
        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeInstance */
        $productTypeInstance = $product->getTypeInstance();
        $productTypeInstance->setStoreFilter($product->getStoreId(), $product);

        $attributes = $productTypeInstance->getConfigurableAttributes($product);
        $superAttributeList = [];
        foreach ($attributes->getItems() as $_attribute) {
            $superAttributeList[$_attribute->getAttributeId()]['label'] = $_attribute->getProductAttribute()->getFrontendLabel();
            $superAttributeList[$_attribute->getAttributeId()]['value'] = __($this->getOptionValueLabel($_attribute->getOptions(), $selectedOptions));
        }

        return $superAttributeList;
    }

    /**
     * @param $options
     * @param $selectedOptions
     * @return mixed|string
     */
    public function getOptionValueLabel($options, $selectedOptions)
    {
        if (is_array($options)) {
            foreach ($options as $option) {
                foreach ($selectedOptions as $selectedOption) {
                    if ($option['value_index'] == $selectedOption) {
                        return $option['label'];
                    }
                }
            }
        }
        return '';
    }
}
