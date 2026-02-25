<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Management;

use Omnik\Core\Model\Repositories\SellerRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

class Seller
{
    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SellerRepository
     */
    private SellerRepository $sellerRepository;

    /**
     * @param Json $json
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SellerRepository $sellerRepository
     */
    public function __construct(
        Json $json,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SellerRepository $sellerRepository
    ) {
        $this->json = $json;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sellerRepository = $sellerRepository;
    }

    /**
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getRangeZipcode(int $storeId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('zipcode_range', null, 'notnull')
            ->addFilter('store_id', $storeId)
            ->create();

        $listSeller = $this->sellerRepository->getList($searchCriteria);
        $rangeZipcode = [];

        if ($listSeller->getTotalCount() > 0) {
            foreach ($listSeller->getItems() as $item) {
                if (strlen($item->getZipcodeRange()) > 1) {
                    $rangeZipcode[] = $this->json->unserialize($item->getZipcodeRange());
                }
            }
        }

        return $rangeZipcode;
    }
}
