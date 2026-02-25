<?php

declare(strict_types=1);

namespace Omnik\Core\Controller\Adminhtml\ReintegrationOrder;

use Omnik\Core\Logger\Logger;
use Omnik\Core\Observer\IntegrationSalesOmnik;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Backend\Model\Auth\Session;

class Index implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var IntegrationSalesOmnik
     */
    private IntegrationSalesOmnik $integrationSalesOmnik;

    /**
     * @var ResultFactory
     */
    private ResultFactory $resultFactory;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    private Logger $salesLogger;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Session
     */
    private Session $backendSession;

    /**
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param IntegrationSalesOmnik $integrationSalesOmnik
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $messageManager
     * @param Logger $salesLogger
     * @param Json $json
     * @param Session $backendSession
     */
    public function __construct(
        RequestInterface         $request,
        OrderRepositoryInterface $orderRepository,
        IntegrationSalesOmnik    $integrationSalesOmnik,
        ResultFactory            $resultFactory,
        ManagerInterface         $messageManager,
        Logger                   $salesLogger,
        Json                     $json,
        Session                  $backendSession
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->integrationSalesOmnik = $integrationSalesOmnik;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->salesLogger = $salesLogger;
        $this->json = $json;
        $this->backendSession = $backendSession;
    }

    public function execute()
    {
        $orderId = $this->request->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $returnSendOrder = $this->integrationSalesOmnik->reintegrateOrder($order);

        if (isset($returnSendOrder['has_integrated'])) {
            $this->messageManager->addSuccessMessage(__("Order already integrated."));
        }

        if (isset($returnSendOrder['orderData']['id'])) {
            $this->messageManager->addSuccessMessage(__("Order reintegrated with successfully."));
        }

        if (isset($returnSendOrder['fails']) && $returnSendOrder['fails']) {
            $this->messageManager->addErrorMessage(__("Error trying reintegrate order."));
        }

        $returnSendOrderLog = $this->json->serialize($returnSendOrder);
        $this->salesLogger->info('Reintegrate Order: ' . $orderId . ' - ' . $returnSendOrderLog);

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);

        return $resultRedirect;
    }
}
