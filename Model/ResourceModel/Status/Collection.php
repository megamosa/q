<?php
/**
 * MagoArab Order Status Permissions
 *
 * @category  MagoArab
 * @package   MagoArab_OrderStatusPermissions
 */
declare(strict_types=1);

namespace MagoArab\OrderStatusPermissions\Model\ResourceModel\Status;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection as StatusCollection;
use Psr\Log\LoggerInterface;

/**
 * Order Status Collection with enhanced state joining
 */
class Collection extends StatusCollection
{
    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Enhanced method to join status and state tables
     * This ensures we get all statuses that are assigned to states
     *
     * @return $this
     */
    public function joinStates()
    {
        $this->getSelect()->joinLeft(
            ['state_table' => $this->getTable('sales_order_status_state')],
            'main_table.status = state_table.status',
            ['state', 'is_default', 'visible_on_front']
        );
        
        // Only get statuses that are actually assigned to states
        $this->getSelect()->where('state_table.state IS NOT NULL');
        
        // Group by status to avoid duplicates if a status is assigned to multiple states
        $this->getSelect()->group('main_table.status');
        
        return $this;
    }
    
    /**
     * Add filter by state code
     *
     * @param string|array $state
     * @return $this
     */
    public function addStateFilter($state)
    {
        if (!$this->getFlag('state_table_joined')) {
            $this->joinStates();
            $this->setFlag('state_table_joined', true);
        }
        
        $this->addFieldToFilter('state_table.state', ['in' => (array)$state]);
        
        return $this;
    }
}