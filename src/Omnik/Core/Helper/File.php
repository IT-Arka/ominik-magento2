<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\View;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;

class File extends AbstractHelper
{
    public const PATH = "/catalog/product/attachment/";
    public const PATH_FIRST_IMAGE = '/catalog/product/image/first/';
    public const PATH_SECOND_IMAGE = '/catalog/product/image/second/';

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @param DirectoryList $directoryList
     * @param Context $context
     */
    public function __construct(
        DirectoryList $directoryList,
        Context $context
    ) {
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    /**
     * @param ProductInterface $product
     * @return array
     * @throws FileSystemException
     */
    public function getUrlArrayFiles(ProductInterface $product): array
    {
        $files = [];
        $dataFiles = [];
        $sku = $product->getSku();
        $dir = $this->directoryList->getPath("media") . self::PATH . $sku . "/";

        if (is_dir($dir)) {
            $registers = scandir($dir);

            if (!empty($registers)) {
                foreach ($registers as $register) {
                    if ($register != "." && $register != "..") {
                        $files[] = $register;
                    }
                }
            }

            $file = $this->directoryList->getPath("media") . self::PATH . $sku . "/" . $files[0];
            $dataFiles['filePath'] = $file;
            $dataFiles['fileName'] = $files[0];
        }

        return $dataFiles;
    }

    /**
     * @param ProductInterface $product
     * @param string $type
     * @param View $block
     * @return string
     * @throws FileSystemException
     */
    public function getImage(ProductInterface $product, string $type, View $block): string
    {
        $file = "";

        if ($type == 'first') {
            $path = self::PATH_FIRST_IMAGE;
        } else {
            $path = self::PATH_SECOND_IMAGE;
        }

        $sku = $product->getSku();
        $dir = $this->directoryList->getPath("media") . $path . $sku . "/";

        if (is_dir($dir)) {
            $registers = scandir($dir);

            if (!empty($registers)) {
                foreach ($registers as $register) {
                    if ($register != "." && $register != "..") {
                        $files[] = $register;
                    }
                }
            }

            $file = $block->getBaseUrl() . "media" . $path . $sku . "/" . $files[0];
        }

        return $file;
    }

    /**
     * @param ProductInterface $product
     * @return bool
     * @throws FileSystemException
     */
    public function hasFile(ProductInterface $product): bool
    {
        $sku = $product->getSku();
        $dir = $this->directoryList->getPath("media") . self::PATH . $sku . "/";

        if (is_dir($dir)) {
            $registers = scandir($dir);

            if (!empty($registers)) {
                return true;
            }
        }

        return false;
    }
}
