<?php
/**
 * MagoArab Order Status Permissions
 *
 * @category  MagoArab
 * @package   MagoArab_OrderStatusPermissions
 */
declare(strict_types=1);

namespace MagoArab\OrderStatusPermissions\Plugin\Magento\Framework\Acl\AclResource;

use Magento\Framework\Acl\AclResource\Provider as AclResourceProvider;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection as StatusCollection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;

class Provider
{
    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;
    
    /**
     * @var array
     */
    private $processedStatuses = [];

    /**
     * @param StatusCollectionFactory $statusCollectionFactory
     */
    public function __construct(
        StatusCollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Add order statuses to ACL tree
     *
     * @param AclResourceProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetAclResources(
        AclResourceProvider $subject,
        array $result
    ): array {
        try {
            // Reset processed statuses for each call
            $this->processedStatuses = [];
            
            /** @var StatusCollection $statusCollection */
            $statusCollection = $this->statusCollectionFactory->create();
            $statusCollection->joinStates();
            
            // Get distinct statuses to avoid duplicates
            $distinctStatuses = [];
            foreach ($statusCollection as $status) {
                $statusId = $status->getStatus();
                if (!isset($distinctStatuses[$statusId])) {
                    $distinctStatuses[$statusId] = $status;
                }
            }
            
            // Process all resources to find our node
            $processedResult = $this->processResources($result, $distinctStatuses);
            
            return $processedResult;
        } catch (\Exception $e) {
            // Log the exception if needed
            return $result; // Return original result in case of error
        }
    }
    
    /**
     * Process ACL resources to find and update our custom node
     *
     * @param array $resources
     * @param array $statusCollection
     * @return array
     */
    private function processResources(array $resources, array $distinctStatuses): array
    {
        foreach ($resources as $index => $resource) {
            // Ensure each resource has an ID
            if (!isset($resource['id'])) {
                continue;
            }
            
            if ($resource['id'] === 'MagoArab_OrderStatusPermissions::order_status_management') {
                // Found our node, now add children
                if (!isset($resources[$index]['children'])) {
                    $resources[$index]['children'] = [];
                }
                
                // Check for existing statuses to avoid duplicates
                $existingStatusIds = [];
                if (!empty($resources[$index]['children'])) {
                    foreach ($resources[$index]['children'] as $child) {
                        if (isset($child['id']) && strpos($child['id'], 'MagoArab_OrderStatusPermissions::status_') === 0) {
                            $statusId = substr($child['id'], strlen('MagoArab_OrderStatusPermissions::status_'));
                            $existingStatusIds[$statusId] = true;
                        }
                    }
                }
                
                // Add dynamic order statuses as children, avoiding duplicates
                foreach ($distinctStatuses as $statusId => $status) {
                    // Skip if already processed in this run or exists in the tree
                    if (isset($this->processedStatuses[$statusId]) || isset($existingStatusIds[$statusId])) {
                        continue;
                    }
                    
                    $stateCode = $status->getState();
                    // Only add statuses that are assigned to states
                    if ($stateCode) {
                        $resources[$index]['children'][] = [
                            'id' => 'MagoArab_OrderStatusPermissions::status_' . $statusId,
                            'title' => $status->getLabel(),
                            'sortOrder' => 10,
                            'disabled' => false
                        ];
                        
                        // Mark as processed
                        $this->processedStatuses[$statusId] = true;
                    }
                }
            } elseif (isset($resource['children']) && is_array($resource['children'])) {
                // Process children recursively
                $resources[$index]['children'] = $this->processResources($resource['children'], $distinctStatuses);
            }
        }
        
        return $resources;
    }
}