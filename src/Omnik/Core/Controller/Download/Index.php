<?php

declare(strict_types=1);

namespace Omnik\Core\Controller\Download;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Omnik\Core\Helper\File;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var FileFactory
     */
    private FileFactory $fileFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryInterface;

    /**
     * @var File
     */
    private File $file;

    /**
     * @param RequestInterface $request
     * @param FileFactory $fileFactory
     * @param LoggerInterface $logger
     * @param DirectoryList $directory
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param File $file
     */
    public function __construct(
        RequestInterface $request,
        FileFactory $fileFactory,
        LoggerInterface $logger,
        DirectoryList $directory,
        ProductRepositoryInterface $productRepositoryInterface,
        File $file
    ) {
        $this->request = $request;
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->directory = $directory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->file = $file;
    }

    public function execute()
    {
        try {

            $productId = $this->request->getParam('product_id');
            $product = $this->productRepositoryInterface->getById($productId);

            $files = $this->file->getUrlArrayFiles($product);

            return $this->fileFactory->create(
                $files['fileName'],
                [
                    'type' => 'filename',
                    'value' => $files['filePath']
                ],
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            );
        } catch (FileSystemException $fileSystemException) {
            $this->logger->info($fileSystemException->getMessage());
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}

