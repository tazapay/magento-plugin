<?php
namespace Tz\TazaPay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('tz_tazapay_tazapayusers'),
                'business_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'Business Name',
                    'after' => 'ind_bus_type'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('tz_tazapay_tazapayusers'),
                'partners_customer_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'Partners Customer ID',
                    'after' => 'business_name'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote_payment'),
                'tazapay_payer_email',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'TazaPay Payer E-Mail'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_payment'),
                'tazapay_payer_email',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'TazaPay Payer E-Mail'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('tz_tazapay_tazapayusers'),
                'tazapay_environment',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'Environment',
                    'after' => 'business_name'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $installer->getConnection()->dropTable('tz_tazapay_tazapayusers');
        }
        $installer->endSetup();
    }
}