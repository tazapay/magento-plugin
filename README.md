# Tazapay Payments Plugin for Magento

This plugin enables your Magento powered platform to start accepting international payments via Tazapay's Escrow product.

### How do Tazapay's escrow payments work?
1. The buyer can select the product or service and make a payment like any other online checkout option (available payment methods depend on the buyer's country and the amount of money to be transferred)
2. Once the payment is complete, the funds are received and secured in a bank account under the jurisdiction of MAS (Monetary Authority of Singapore)
3. Once the product is shipped or the services rendered, the seller (or your platform) can provide a proof of order fulfillment to Tazapay for verification
4. As soon as Tazapay verifies the documents, the payment is released to the seller

### Features
1. Add an international payment method to your checkout page to enable payments from buyers from over 90+ countries
2. Low cost secured payments for buyers and sellers at best in class FX rates
3. Easily monetize your platform by enabling a platform fee: we handle the collection and settlement on your behalf!
4. Wide variety of payment methods accepted: Mastercard, VISA, Local Bank Transfers, and other local payment
5. Especially relevant for B2B as large value transactions upto $1M are supported at a low cost and with escrow protection. Fully compliant with local and international regulations, all relevant trade documents are provided.

## Installation
1. Generate your API Key and Secret by signing up here: https://app.tazapay.com/signup
2. Download package from https://github.com/tazapay/magento-plugin.git and upload it to your app/code/ directory
3. After uploading the source code, you need to run command given below:
	```bash
    php bin/magento setup:upgrade
    php bin/magento setup:static-content:deploy -f
    php bin/magento indexer:reindex
    php bin/magento cache:clean
    php bin/magento cache:flush
    php bin.magento chmod -R 777 var/* pub/* generated/*
    ```
    
4. Enable and configure Tazapay in Magento Admin under Stores/Configuration/Payment Methods/TazaPay
5. Add your 'API Key' and 'Secret' (obtained from Tazapay after completign Step 1) in the admin panel (NOTE: You can add 'sandbox' keys for test transactions and 'production' keys for real transactions; to Generate for sandbox keys, go to https://sandbox.tazapay.com/signup)
6. Get your seller ID by following the steps below: 
    1. Go to the Magento Admin panel: Stores/Configuration/Payment Methods/TazaPay
    2. On the same page, you will see the "Get Seller Id" button
    3. It will show 'Create TazaPay User Form' if the seller does not have a Tazapay account
    4. Fill all values and save
    5. When you submit the form, you will get your seller ID (UUID) > copy this UUID
    6. Paste this value in the field for 'Seller ID' in system configuration

### Requirements
- Magento 2.4.0 Stable or higher

### License
Copyright © 2021 Tazapay. All rights reserved. See LICENSE for license details.
