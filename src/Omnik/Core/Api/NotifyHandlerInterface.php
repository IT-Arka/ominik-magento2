<?php

declare(strict_types=1);

namespace Omnik\Core\Api;

interface NotifyHandlerInterface
{
    //events
    public const UPDATE = 'UPDATE';
    public const INSERT = 'INSERT';
    public const NEW = 'NEW';
    public const DELETE = 'DELETE';
    public const CANCELED = 'CANCELED';
    public const PARTIALLYCANCELED = 'PARTIALLYCANCELED';
    public const APPROVED = 'APPROVED';
    public const NOT_APPROVED = 'NOT_APPROVED';
    public const DELIVERED = 'DELIVERED';
    public const INVOICED = 'INVOICED';
    public const SHIP = 'SENT';
    public const SHIPPING_LABEL = 'SHIPPING_LABEL';

    //resources
    public const RESOURCE_SHOPPER = 'SHOPPER';
    public const RESOURCE_SHOPPER_RESTRICTION = 'SHOPPER_RESTRICTION';
    public const RESOURCE_SUPPLIER_RESTRICTION = 'SUPPLIER_RESTRICTION';
    public const RESOURCE_VENDOR = 'VENDOR';
    public const RESOURCE_VENDOR_SHOPPER = 'VENDOR_SHOPPER';
    public const RESOURCE_ORDER = 'ORDER';
    public const RESOURCE_BRAND = 'BRAND';
    public const RESOURCE_EMBALAGEM = 'EMBALAGEM';
    public const RESOURCE_SELLER_DISTRIBUTOR_FREIGHT = 'DISTRIBUTOR_FREIGHT';
    public const RESOURCE_SELLER_ZIPCODE_RANGE = 'SELLER_ZIPCODE_RANGE';
    public const RESOURCE_INVENTORY = 'INVENTORY';
    public const RESOURCE_PRICE = 'PRICE';
    public const RESOURCE_SELLER = 'SELLER';
    public const RESOURCE_SHOPPER_CLASSIFICATION = 'SHOPPER_CLASSIFICATION';
    public const RESOURCE_PRODUCT = 'PRODUCT';

    //status orders
    public const STATUS = [
        'APPROVED' => [
            'on_analysis' => 'Analysis'
        ],
        'DELIVERED' => [
            'delivery' => 'Delivery'
        ],
        'INVOICED' => [
            'invoice' => 'Invoice'
        ],
        'SENT' => [
            'sent' => 'Ship'
        ],
        self::PARTIALLYCANCELED => [
            'partially_canceled' => 'Partially Canceled'
        ],
        self::CANCELED => [
            'canceled' => 'Canceled'
        ],
        self::NOT_APPROVED => [
            'canceled' => 'Canceled'
        ]
    ];

    public const QTY_LIMIT_REGISTERS = 50;

    /**
     * @param array $registers
     * @return void
     */
    public function execute(array $registers): void;
}
