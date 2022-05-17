<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
 
/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        // Add fields to quote_payment table
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_payment'),
            'buyer_tazapay_account_uuid',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'Buyer TazaPay Account UUID',
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_payment'),
            'escrow_txn_no',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'TazaPay Escrow Txn no',
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_payment'),
            'tazapay_create_payment_redirect_url',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'TazaPay Create Payment Redirect Url',
            ]
        );
        // Add fields to sales_order_payment table
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'buyer_tazapay_account_uuid',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'Buyer TazaPay Account UUID',
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'escrow_txn_no',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'TazaPay Escrow Txn no',
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_payment'),
            'tazapay_create_payment_redirect_url',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'TazaPay Create Payment Redirect Url',
            ]
        );

        /**
         * Create table 'tz_tazapay_tazapayusers'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('tz_tazapay_tazapayusers')
        )->addColumn(
            'tazapayusers_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'account_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'TazaPay Account UUID'
        )->addColumn(
            'user_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'User Type Buyer or Seller'
        )->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Buyer or Seller Email'
        )->addColumn(
            'first_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'First Name'
        )->addColumn(
            'last_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Last Name'
        )->addColumn(
            'contact_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country Dial Code'
        )->addColumn(
            'contact_number',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Mobile Number'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country Code'
        )->addColumn(
            'ind_bus_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Can be Business or Individual'
        )->addColumn(
            'Created',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
            ],
            'Created At'
        )->setComment('tz_tazapay_tazapayusers Table');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
