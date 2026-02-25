<?php

declare(strict_types=1);

namespace Omnik\Core\Api\Data;

interface SellerInterface
{
    public const TABLE_NAME = 'omnik_seller';
    public const ID = 'entity_id';
    public const OMNIK_ID = 'omnik_id';
    public const ACTIVE = 'active';
    public const HUB_SESSION_ID = 'hub_session_id';
    public const COMPANY_NAME = 'company_name';
    public const FANTASY_NAME = 'fantasy_name';
    public const CNAE = 'cnae';
    public const STATE_REGISTRATION = 'state_registration';
    public const MUNICIPAL_REGISTRATION = 'municipal_registration';
    public const TAX_REGIME = 'tax_regime';
    public const DATE_ACCESSION_NATIONAL_SIMPLE = 'date_accession_national_simple';
    public const HOLDING = 'holding';
    public const MATRIX = 'matrix';
    public const BRANCH = 'branch';
    public const ZIPCODE_RANGE = 'zipcode_range';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DISTRIBUTOR_FREIGHT = 'distributor_freight';
    public const STORE_ID = 'store_id';

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $value
     * @return $this
     */
    public function setId($value);

    /**
     * @return string
     */
    public function getOmnikId();

    /**
     * @param string $value
     * @return $this
     */
    public function setOmnikId($value);

    /**
     * @return int
     */
    public function getActive();

    /**
     * @param int $value
     * @return $this
     */
    public function setActive($value);

    /**
     * @return string
     */
    public function getHubSessionId();

    /**
     * @param string $value
     * @return $this
     */
    public function setHubSessionId($value);

    /**
     * @return string
     */
    public function getCompanyName();

    /**
     * @param string $value
     * @return $this
     */
    public function setCompanyName($value);

    /**
     * @return string
     */
    public function getFantasyName();

    /**
     * @param string $value
     * @return $this
     */
    public function setFantasyName($value);

    /**
     * @return string
     */
    public function getCnae();

    /**
     * @param string $value
     * @return $this
     */
    public function setCnae($value);

    /**
     * @return string
     */
    public function getStateRegistration();

    /**
     * @param string $value
     * @return $this
     */
    public function setStateRegistration($value);

    /**
     * @return string
     */
    public function getMunicipalRegistration();

    /**
     * @param string $value
     * @return $this
     */
    public function setMunicipalRegistration($value);

    /**
     * @return string
     */
    public function getTaxRegime();

    /**
     * @param string $value
     * @return $this
     */
    public function setTaxRegime($value);

    /**
     * @return string
     */
    public function getDateAccessionNationalSimple();

    /**
     * @param string $value
     * @return $this
     */
    public function setDateAccessionNationalSimple($value);

    /**
     * @return string
     */
    public function getHolding();

    /**
     * @param string $value
     * @return $this
     */
    public function setHolding($value);

    /**
     * @return string
     */
    public function getMatrix();

    /**
     * @param string $value
     * @return $this
     */
    public function setMatrix($value);

    /**
     * @return string
     */
    public function getBranch();

    /**
     * @param string $value
     * @return $this
     */
    public function setBranch($value);

    /**
     * @return string
     */
    public function getZipcodeRange();

    /**
     * @param string $value
     * @return $this
     */
    public function setZipcodeRange($value);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $value
     * @return $this
     */
    public function setCreatedAt($value);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $value
     * @return $this
     */
    public function setUpdatedAt($value);

    /**
     * @return string
     */
    public function getDistributorFreight(): string;

    /**
     * @param string $value
     * @return SellerInterface
     */
    public function setDistributorFreight(string $value): SellerInterface;

    /**
     * @param int $storeId
     * @return SellerInterface
     */
    public function setStoreId(int $storeId): SellerInterface;

    /**
     * @return int
     */
    public function getStoreId(): int;
}
