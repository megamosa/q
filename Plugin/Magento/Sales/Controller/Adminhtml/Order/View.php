<?php
/**
 * MagoArab Order Status Permissions
 *
 * @category  MagoArab
 * @package   MagoArab_OrderStatusPermissions
 */
declare(strict_types=1);

namespace MagoArab\OrderStatusPermissions\Plugin\Magento\Sales\Controller\Adminhtml\Order;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View as OrderViewController;
use MagoArab\OrderStatusPermissions\Helper\Data as Helper;

class View
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param AuthorizationInterface $authorization
     * @param OrderRepositoryInterface $orderRepository
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param Helper $helper
     */
    public function __construct(
        AuthorizationInterface $authorization,
        OrderRepositoryInterface $orderRepository,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        Helper $helper
    ) {
        $this->authorization = $authorization;
        $this->orderRepository = $orderRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * Check if user has permission to view order with specific status
     *
     * @param OrderViewController $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(
        OrderViewController $subject,
        callable $proceed
    ) {
        // Skip permission check if module is disabled
        if (!$this->helper->isEnabled()) {
            return $proceed();
        }

        // Admin user has all permissions if includeAdminUsers is disabled
        if (!$this->helper->includeAdminUsers() && $this->authorization->isAllowed('Magento_Backend::all')) {
            return $proceed();
        }

        $request = $subject->getRequest();
        $orderId = (int)$request->getParam('order_id');
        
        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                $statusId = $order->getStatus();
                
                // Check if user has permission to view this order status
                if (!$this->authorization->isAllowed('MagoArab_OrderStatusPermissions::status_' . $statusId)) {
                    $this->messageManager->addErrorMessage(__('You don\'t have permission to view orders with status "%1".', $statusId));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath('sales/order/index');
                }
            } catch (\Exception $e) {
                // Order not found or other issue
                $this->messageManager->addErrorMessage(__('Order not found.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('sales/order/index');
            }
        }
        
        return $proceed();
    }
}