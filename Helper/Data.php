<?php
/**
 * MagoArab Order Status Permissions
 *
 * @category  MagoArab
 * @package   MagoArab_OrderStatusPermissions
 */
declare(strict_types=1);

namespace MagoArab\OrderStatusPermissions\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_ENABLED = 'sales_order_status_permissions/general/enabled';
    const XML_PATH_LOG_ACTIONS = 'sales_order_status_permissions/general/log_actions';
    const XML_PATH_HIDE_FORBIDDEN_ORDERS = 'sales_order_status_permissions/advanced/hide_forbidden_orders';
    const XML_PATH_INCLUDE_ADMIN_USERS = 'sales_order_status_permissions/advanced/include_admin_users';

    /**
     * Check if module is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if action logging is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isLoggingEnabled($storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_LOG_ACTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if forbidden orders should be hidden completely
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function hideForbiddenOrders($storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_HIDE_FORBIDDEN_ORDERS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if permissions should be applied to admin users as well
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function includeAdminUsers($storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_INCLUDE_ADMIN_USERS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}