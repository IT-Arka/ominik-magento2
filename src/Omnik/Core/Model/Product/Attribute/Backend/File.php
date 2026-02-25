<?php

declare(strict_types=1);

namespace Omnik\Core\Model\Product\Attribute\Backend;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\RequestInterface;

class File extends AbstractBackend
{

    public const PATH_ATTACHMENT = 'catalog/product/attachment/';
    public const PATH_ATTACHMENT_FIRST_IMAGE = 'catalog/product/image/first/';
    public const PATH_ATTACHMENT_SECOND_IMAGE = 'catalog/product/image/second/';

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var DriverFile
     */
    private DriverFile $file;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $uploaderFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param Logger $logger
     * @param Filesystem $filesystem
     * @param DriverFile $file
     * @param UploaderFactory $uploaderFactory
     * @param RequestInterface $request
     */

    public function __construct(
        Logger $logger,
        Filesystem $filesystem,
        DriverFile $file,
        UploaderFactory $uploaderFactory,
        RequestInterface $request,
    ) {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->uploaderFactory = $uploaderFactory;
        $this->request = $request;
    }

    /**
     * @param $object
     * @return $this|File
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave($object)
    {

        $pathFolder = $this->getPathFolder();
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            $pathFolder . $object->getSku() . '/'
        );
        $delete = $object->getData($this->getAttribute()->getName() . '_delete');
        $fileName = $object->getData($this->getAttribute()->getName());

        if ($delete) {
            $object->setData($this->getAttribute()->getName(), '');
            $this->getAttribute()->getEntity()->saveAttribute($object, $this->getAttribute()->getName());
            if ($this->file->isExists($path . $fileName)) {
                $this->file->deleteFile($path . $fileName);
                $this->file->deleteDirectory($path);
            }
        }

        $files = $this->request->getFiles();

        if (empty($files['product']['tmp_name'][$this->getAttribute()->getName()])) {
            return $this;
        }

        $this->isValidFormatFiles($files);

        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->uploaderFactory->create(['fileId' => 'product['.$this->getAttribute()->getName().']']);
            $uploader->setAllowRenameFiles(true);

            if ($this->file->isFile($path . $fileName)) {
                $this->file->deleteFile($path . $fileName);
            }

            $result = $uploader->save($path);
            $object->setData($this->getAttribute()->getName(), $result['file']);
            $this->getAttribute()->getEntity()->saveAttribute($object, $this->getAttribute()->getName());
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getPathFolder(): string
    {
        if ($this->getAttribute()->getName() == "attachment") {
            return self::PATH_ATTACHMENT;
        } elseif ($this->getAttribute()->getName() == "attachment_second_image") {
            return self::PATH_ATTACHMENT_SECOND_IMAGE;
        }

        return self::PATH_ATTACHMENT_FIRST_IMAGE;
    }

    /**
     * @param array $file
     * @return void
     * @throws LocalizedException
     */
    private function isValidFormatFiles(array $file): void
    {
        if ($this->getAttribute()->getName() == "attachment") {
            if ($file['product']['type']['attachment'] !== "application/pdf") {
                throw new \Magento\Framework\Exception\LocalizedException(__("File extension must be only pdf."));
            }
        } else {
            if ($file['product']['type'][$this->getAttribute()->getName()] !== "image/jpeg"
            && $file['product']['type'][$this->getAttribute()->getName()] !== "image/png") {
                throw new \Magento\Framework\Exception\LocalizedException(__("File extension must be png or jpeg."));
            }
        }
    }

    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        return [];
    }
}
