<?php
declare(strict_types=1);

namespace Omnik\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Omnik\Core\Api\Data\SellerInterface;
use Omnik\Core\Model\ResourceModel\Seller as ResourceModel;

class Seller extends AbstractModel implements SellerInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getOmnikId()
    {
        return $this->getData(SellerInterface::OMNIK_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOmnikId($value)
    {
        $this->setData(SellerInterface::OMNIK_ID, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getActive()
    {
        return $this->getData(SellerInterface::ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setActive($value)
    {
        $this->setData(SellerInterface::ACTIVE, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHubSessionId()
    {
        return $this->getData(SellerInterface::HUB_SESSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setHubSessionId($value)
    {
        $this->setData(SellerInterface::HUB_SESSION_ID, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCompanyName()
    {
        return $this->getData(SellerInterface::COMPANY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCompanyName($value)
    {
        $this->setData(SellerInterface::COMPANY_NAME, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFantasyName()
    {
        return $this->getData(SellerInterface::FANTASY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFantasyName($value)
    {
        $this->setData(SellerInterface::FANTASY_NAME, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCnae()
    {
        return $this->getData(SellerInterface::CNAE);
    }

    /**
     * @inheritDoc
     */
    public function setCnae($value)
    {
        $this->setData(SellerInterface::CNAE, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStateRegistration()
    {
        return $this->getData(SellerInterface::STATE_REGISTRATION);
    }

    /**
     * @inheritDoc
     */
    public function setStateRegistration($value)
    {
        $this->setData(SellerInterface::STATE_REGISTRATION, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMunicipalRegistration()
    {
        return $this->getData(SellerInterface::MUNICIPAL_REGISTRATION);
    }

    /**
     * @inheritDoc
     */
    public function setMunicipalRegistration($value)
    {
        $this->setData(SellerInterface::MUNICIPAL_REGISTRATION, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTaxRegime()
    {
        return $this->getData(SellerInterface::TAX_REGIME);
    }

    /**
     * @inheritDoc
     */
    public function setTaxRegime($value)
    {
        $this->setData(SellerInterface::TAX_REGIME, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDateAccessionNationalSimple()
    {
        return $this->getData(SellerInterface::DATE_ACCESSION_NATIONAL_SIMPLE);
    }

    /**
     * @inheritDoc
     */
    public function setDateAccessionNationalSimple($value)
    {
        $this->setData(SellerInterface::DATE_ACCESSION_NATIONAL_SIMPLE, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHolding()
    {
        return $this->getData(SellerInterface::HOLDING);
    }

    /**
     * @inheritDoc
     */
    public function setHolding($value)
    {
        $this->setData(SellerInterface::HOLDING, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMatrix()
    {
        return $this->getData(SellerInterface::MATRIX);
    }

    /**
     * @inheritDoc
     */
    public function setMatrix($value)
    {
        $this->setData(SellerInterface::MATRIX, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBranch()
    {
        return $this->getData(SellerInterface::BRANCH);
    }

    /**
     * @inheritDoc
     */
    public function setBranch($value)
    {
        $this->setData(SellerInterface::BRANCH, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getZipcodeRange()
    {
        return $this->getData(SellerInterface::ZIPCODE_RANGE);
    }

    /**
     * @inheritDoc
     */
    public function setZipcodeRange($value)
    {
        $this->setData(SellerInterface::ZIPCODE_RANGE, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(SellerInterface::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($value)
    {
        $this->setData(SellerInterface::CREATED_AT, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(SellerInterface::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($value)
    {
        $this->setData(SellerInterface::UPDATED_AT, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getDistributorFreight(): string
    {
        return $this->getData(SellerInterface::DISTRIBUTOR_FREIGHT);
    }

    /**
     * @param string $value
     * @return SellerInterface
     */
    public function setDistributorFreight(string $value): SellerInterface
    {
        return $this->setData(SellerInterface::DISTRIBUTOR_FREIGHT, $value);
    }

    /**
     * @param int $storeId
     * @return SellerInterface
     */
    public function setStoreId(int $storeId): SellerInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->getData(self::STORE_ID);
    }
}
