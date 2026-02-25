<?php

declare(strict_types=1);

namespace Omnik\Core\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

class File extends AbstractModifier
{

    /**
     * @var ArrayManager
     */
    protected ArrayManager $arrayManager;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager
    ) {
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function modifyMeta(array $meta)
    {
        $fieldCodes = [];
        $fieldCodes['file'] = 'attachment';
        $fieldCodes['firstImage'] = 'attachment_first_image';
        $fieldCodes['secondImage'] = 'attachment_second_image';

        foreach ($fieldCodes as $file => $fieldCode) {
            $elementPath = $this->arrayManager->findPath($fieldCode, $meta, null, 'children');
            $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX . $fieldCode, $meta, null, 'children');

            if (!$elementPath) {
                return $meta;
            }

            $mediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                [
                    'children' => [
                        $fieldCode => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'elementTmpl' => 'Omnik_Catalog/elements/' . $file,
                                        'media_url' => $mediaUrl
                                    ],
                                ],
                            ],
                        ]
                    ]
                ]
            );
        }
        return $meta;
    }
}
