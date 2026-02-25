<?php

namespace Omnik\Core\Controller\Product;

use Omnik\Core\Block\Product\ListProduct\CustomCard;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;

class Item extends Action
{
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly Context                    $context,
        private readonly PageFactory                $resultPageFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder      $searchCriteriaBuilder
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|(Json&ResultInterface)|ResultInterface
     */
    public function execute()
    {
        $skus = json_decode($this->_request->getParam('sku'));
        $skusString = is_array($skus) ? implode(",", $skus) : '';

        $resultPage = $this->resultPageFactory->create();
        $html = '';

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $skusString, 'in')->create();
        $productList = $this->productRepository->getList($searchCriteria);
        if ($productList->getTotalCount()) {
            foreach ($productList->getItems() as $product) {
                $html .= $resultPage->getLayout()
                    ->createBlock(CustomCard::class)
                    ->setProduct($product)
                    ->toHtml();
            }
        }

        $data = new DataObject(['html' => $html]);
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($data);
    }
}
