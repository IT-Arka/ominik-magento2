<?php
declare(strict_types=1);

namespace Omnik\Core\Api;

/**
 * Seller CRUD interface.
 */
interface SellerRepositoryInterface
{
    const SAVE_METHOD = 'SellerRepository::save';

    /**
     * Save Seller.
     *
     * @param \Omnik\Core\Api\Data\SellerInterface $model
     * @return \Omnik\Core\Api\Data\SellerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\SellerInterface $model);

    /**
     * Update Seller.
     *
     * @param \Omnik\Core\Api\Data\SellerInterface $model
     * @param int $seller_id
     * @return \Omnik\Core\Api\Data\SellerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update(Data\SellerInterface $model, $seller_id);

    /**
     * Retrieve Seller.
     *
     * @param int $id
     * @return \Omnik\Core\Api\Data\SellerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Seller by omnik_id.
     *
     * @param int $omnik_id
     * @return \Omnik\Core\Api\Data\SellerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByOmnikId($omnik_id);

    /**
     * Retrieve Seller matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Omnik\Core\Api\Data\SearchResults\SellerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Seller.
     *
     * @param \Omnik\Core\Api\Data\SellerInterface $data
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\SellerInterface $model);

    /**
     * Delete Seller by ID.
     *
     * @param int $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);

    /**
     * Delete Seller by omnik_id.
     *
     * @param int $omnik_id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByOmnikId($omnik_id);
}
