# MagoArab Order Status Permissions

## Overview
This Magento 2 extension adds dynamic order statuses to the Role Resources section in the Magento admin, allowing for granular permission control over order statuses.

## Features
- Automatically adds all dynamically created order statuses to the ACL system
- Enables admin to control permissions for viewing and managing order statuses
- Only displays order statuses that have been assigned to an Order State
- Fully compatible with Magento 2.4.x versions including 2.4.7

## Installation
### Manual Installation
1. Create the following directory structure in your Magento installation:
   `app/code/MagoArab/OrderStatusPermissions`
2. Extract the module files into this directory
3. Run the following commands from your Magento root directory:
   ```
   bin/magento module:enable MagoArab_OrderStatusPermissions
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:clean
   ```

### Installation via Composer
1. Run the following command in your Magento root directory:
   ```
   composer require magoarab/module-order-status-permissions
   ```
2. After the installation is complete, run:
   ```
   bin/magento module:enable MagoArab_OrderStatusPermissions
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:clean
   ```

## Usage
1. Navigate to **System > User Roles** in the Magento admin panel
2. Create a new role or edit an existing one
3. In the **Role Resources** tab, you will now see individual order statuses under **Sales > Operations > Order Statuses**
4. Enable or disable permissions for specific order statuses as needed
5. Save the role configuration

## Compatibility
- Magento Open Source 2.4.x
- Magento Commerce 2.4.x

## Support
For any issues or questions, please contact the developer or submit an issue on GitHub.

## License
This extension is licensed under the Open Software License (OSL 3.0) and the Academic Free License (AFL 3.0).