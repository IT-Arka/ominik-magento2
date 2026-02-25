<?php

namespace Omnik\Core\Model\Carrier;

use Omnik\Core\Api\Data\OmnikFreightRatesInterface;
use Omnik\Core\Model\Config\Configurable\ProductsOptions;
use Omnik\Core\Model\Integration\Freight\GetShippingRates;
use Omnik\Core\Helper\Quote\Data as QuoteHelper;
use Omnik\Core\Model\Management\CreateProduct;
use Omnik\Core\Api\Data\OmnikFreightRatesInterfaceFactory;
use Omnik\Core\Model\Repositories\OmnikFreightRatesRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address\RateResult\Method as RateMethod;
use Psr\Log\LoggerInterface;

class Method extends AbstractCarrier implements CarrierInterface
{
    public const CONTINGENCY_METHOD = 'contingency';
    /**
     * @var string
     */
    protected $_code = 'omnik';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $_rateResultFactory
     * @param MethodFactory $_rateMethodFactory
     * @param ProductsOptions $productsOptions
     * @param GetShippingRates $getShippingRates
     * @param QuoteHelper $quoteHelper
     * @param CreateProduct $createProduct
     * @param OmnikFreightRatesInterfaceFactory $omnikFreightRatesInterfaceFactory
     * @param OmnikFreightRatesRepository $omnikFreightRatesRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        public readonly ScopeConfigInterface               $scopeConfig,
        public readonly ErrorFactory                       $rateErrorFactory,
        public readonly LoggerInterface                    $logger,
        private readonly ResultFactory                     $_rateResultFactory,
        private readonly MethodFactory                     $_rateMethodFactory,
        private readonly ProductsOptions                   $productsOptions,
        private readonly GetShippingRates                  $getShippingRates,
        private readonly QuoteHelper                       $quoteHelper,
        private readonly CreateProduct                     $createProduct,
        private readonly OmnikFreightRatesInterfaceFactory $omnikFreightRatesInterfaceFactory,
        private readonly OmnikFreightRatesRepository       $omnikFreightRatesRepository,
        private readonly SearchCriteriaBuilder             $searchCriteriaBuilder,
        private readonly Json                              $json,
        array                                              $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @param RateRequest $request
     * @return false|Result|mixed
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {

                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeBoxes += $item->getQty() * $child->getQty();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        $this->setFreeBoxes($freeBoxes);

        $item = current($request->getAllItems());
        $quoteId = $item->getQuoteId();

        $simpleItemsByVendor = $this->productsOptions->getSimpleItemsByVendor($request->getAllItems());

        if (empty($request->getDestPostcode())) {
            return false;
        }

        $requestParams = $this->quoteHelper->getRequestDataByItems($simpleItemsByVendor, $request->getDestPostcode());
        $freightQuotes = $this->getShippingRates->execute($this->json->serialize($requestParams));

        /** @var Result $result */
        $result = $this->_rateResultFactory->create();
        if (!isset($freightQuotes['content']) || empty($freightQuotes)) {
            $params = [];
            $params['carrier'] = $this->_code;
            $params['carrierTitle'] = $this->getConfigData('title');
            $params['method'] = self::CONTINGENCY_METHOD;
            $params['methodTitle'] = $this->getConfigData('title');
            $params['shippingPrice'] = $this->getConfigData('price');

            return $this->appendMethod($request, $result, $params);
        }

        if (count($simpleItemsByVendor) == 1) {
            $this->appendForUniqueVendor($freightQuotes, $request, $result, $quoteId);
        }

        if (count($simpleItemsByVendor) >= 2) {
            $this->appendForMultipleVendors($freightQuotes, $request, $result, $quoteId);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param $request
     * @param $result
     * @param $params
     * @return mixed
     */
    public function appendMethod($request, $result, $params = [])
    {
        /** @var RateMethod $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($params['carrier']);
        $method->setCarrierTitle($params['carrierTitle']);

        $method->setMethod($params['method']);
        $method->setMethodTitle($params['methodTitle']);

        $shippingPrice = $params['shippingPrice'];

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        return $result->append($method);
    }

    /**
     * @param $freightQuotes
     * @param $request
     * @param $result
     * @param $quoteId
     * @return void
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function appendForMultipleVendors($freightQuotes, $request, $result, $quoteId)
    {
        $arr = [];
        foreach ($freightQuotes['content'] as $content) {
            $fantasyName = $this->createProduct->getFantasyNameSeller(substr($content['sellerTenant'], 4));
            foreach ($content['deliveryOptions'] as $deliveryOption) {
                $deliveryOption['sellerTenant'] = $content['sellerTenant'] ?? '';
                $deliveryOption['sellerName'] = $fantasyName;
                $deliveryOption['quotationId'] = $content['quotationId'] ?? '';

                if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                    $deliveryOption['finalShippingCost'] = '0.00';
                }

                $arr[$deliveryOption['description']][] = $deliveryOption;
                $this->saveRate($quoteId, $deliveryOption);
            }
        }

        foreach ($arr as $key => $deliveryType) {
            $methodId = null;
            $finalCost = 0;

            $i = 0;
            foreach ($deliveryType as $deliveryInfo) {
                if ($i == 0) {
                    $methodId = $deliveryInfo['sellerTenant'] . "-" . $deliveryInfo['deliveryMethodId'];
                } else {
                    $methodId .= "_" . $deliveryInfo['sellerTenant'] . "-" . $deliveryInfo['deliveryMethodId'];
                }
                $finalCost = $finalCost + $deliveryInfo['finalShippingCost'];
                $i++;
            }

            $params = [];
            $params['carrier'] = $this->_code;
            $params['carrierTitle'] = $key;
            $params['method'] = $methodId;
            $params['methodTitle'] = $deliveryOption['deliveryTime'] <= 1 ? $deliveryOption['deliveryTime']." Dia útil" : $deliveryOption['deliveryTime']." Dias úteis";
            $params['shippingPrice'] = $finalCost;

            $this->appendMethod($request, $result, $params);
        }
    }

    /**
     * @param $freightQuotes
     * @param $request
     * @param $result
     * @param $quoteId
     * @return void
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function appendForUniqueVendor($freightQuotes, $request, $result, $quoteId)
    {
        $tenant = $freightQuotes['content'][0]['sellerTenant'];
        $fantasyName = $this->createProduct->getFantasyNameSeller(substr($tenant, 4));
        $deliveryOptions = $freightQuotes['content'][0]['deliveryOptions'];
        if (!empty($deliveryOptions)) {

            usort($deliveryOptions, function ($deliveryOptionA, $deliveryOptionB) {
                return strcmp($deliveryOptionA['finalShippingCost'], $deliveryOptionB['finalShippingCost']);
            });

            foreach ($deliveryOptions as $deliveryOption) {
                $deliveryOption['sellerTenant'] = $tenant;
                $deliveryOption['sellerName'] = $fantasyName;
                $deliveryOption['quotationId'] = $freightQuotes['content'][0]['quotationId'] ?? '';

                if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                    $deliveryOption['finalShippingCost'] = '0.00';
                }

                $this->saveRate($quoteId, $deliveryOption);

                $params = [];
                $params['carrier'] = $this->_code;
                $params['carrierTitle'] = $deliveryOption['description'];
                $params['method'] = $tenant . '-' . $deliveryOption['deliveryMethodId'];
                $params['methodTitle'] = $deliveryOption['deliveryTime'] <= 1 ? $deliveryOption['deliveryTime']." Dia útil" : $deliveryOption['deliveryTime']." Dias úteis";
                $params['shippingPrice'] = $deliveryOption['finalShippingCost'];

                $this->appendMethod($request, $result, $params);
            }
        }
    }

    /**
     * @param int $quoteId
     * @param array $rate
     * @return void
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     */
    public function saveRate(int $quoteId, array $rate): void
    {
        $this->deleteOldRate($rate['deliveryMethodId'], $rate['sellerTenant'], $quoteId);

        $rates = $this->omnikFreightRatesInterfaceFactory->create();
        $rates->setDeliveryMethodId($rate['deliveryMethodId']);
        $rates->setSellerTenant($rate['sellerTenant']);
        $rates->setBody($this->json->serialize($rate));
        $rates->setQuoteId($quoteId);

        $this->omnikFreightRatesRepository->save($rates);
    }

    /**
     * @param string $deliveryMethodId
     * @param string $sellerTenant
     * @param int $quoteId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteOldRate(string $deliveryMethodId, string $sellerTenant, int $quoteId): void
    {
        $search = $this->searchCriteriaBuilder;
        $search->addFilter(OmnikFreightRatesInterface::QUOTE_ID, $quoteId);
        $search->addFilter(OmnikFreightRatesInterface::DELIVERY_METHOD_ID, $deliveryMethodId);
        $search->addFilter(OmnikFreightRatesInterface::SELLER_TENANT, $sellerTenant);

        $searchResult = $this->omnikFreightRatesRepository->getList($search->create());

        if($searchResult->getItems()){
            foreach ($searchResult->getItems() as $item) {
                $this->omnikFreightRatesRepository->delete($item);
            }
        }

    }
}
