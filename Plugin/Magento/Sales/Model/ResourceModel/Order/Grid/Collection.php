<?php
/**
 * MagoArab Order Status Permissions
 *
 * @category  MagoArab
 * @package   MagoArab_OrderStatusPermissions
 */
declare(strict_types=1);

namespace MagoArab\OrderStatusPermissions\Plugin\Magento\Sales\Model\ResourceModel\Order\Grid;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use MagoArab\OrderStatusPermissions\Helper\Data as Helper;

class Collection
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var array|null
     */
    private $allowedStatusCodes = null;

    /**
     * @param AuthorizationInterface $authorization
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param Helper $helper
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StatusCollectionFactory $statusCollectionFactory,
        Helper $helper
    ) {
        $this->authorization = $authorization;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->helper = $helper;
    }

    /**
     * Filter orders by allowed statuses before loading collection
     *
     * @param OrderGridCollection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(
        OrderGridCollection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        // Skip if collection is already loaded or module is disabled
        if ($subject->isLoaded() || !$this->helper->isEnabled()) {
            return [$printQuery, $logQuery];
        }

        // Admin user has all permissions if includeAdminUsers is disabled
        if (!$this->helper->includeAdminUsers() && $this->authorization->isAllowed('Magento_Backend::all')) {
            return [$printQuery, $logQuery];
        }

        // Get allowed status codes
        $allowedStatusCodes = $this->getAllowedStatusCodes();

        // Apply filter if we have allowed statuses
        if (!empty($allowedStatusCodes)) {
            $subject->addFieldToFilter('status', ['in' => $allowedStatusCodes]);
        } else {
            // If user has no permissions for any status, return empty collection
            $subject->addFieldToFilter('entity_id', 0); // This will result in no orders shown
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Get all status codes that the current user has permission to view
     *
     * @return array
     */
    private function getAllowedStatusCodes()
    {
        // Use cached result if available
        if ($this->allowedStatusCodes !== null) {
            return $this->allowedStatusCodes;
        }

        $this->allowedStatusCodes = [];
        
        // Get all available status codes from the database
        $statusCollection = $this->statusCollectionFactory->create();
        $statusCollection->joinStates();
        
        foreach ($statusCollection as $status) {
            $statusCode = $status->getStatus();
            
            // Check if user has permission for this status
            if ($this->authorization->isAllowed('MagoArab_OrderStatusPermissions::status_' . $statusCode)) {
                $this->allowedStatusCodes[] = $statusCode;
            }
        }
        
        return $this->allowedStatusCodes;
    }
}