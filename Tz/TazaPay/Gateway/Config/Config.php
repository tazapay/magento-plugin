<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'tazapay/general/active';
    const TITLE = 'tazapay/general/title';
    const ENVIRONMENT = 'environment';
    const TAZAPAY_SANDBOX_API_KEY = 'tazapay_sandbox_api_key';
    const TAZAPAY_SANDBOX_API_SECRET_KEY = 'tazapay_sandbox_api_secret_key';
    const TAZAPAY_PRODUCTION_API_KEY = 'tazapay_production_api_key';
    const TAZAPAY_PRODUCTION_API_SECRET_KEY = 'tazapay_production_api_secret_key';
    const CGI_URL_SANDBOX = 'cgi_url_sandbox';
    const CGI_URL_PRODUCTION = 'cgi_url_production';
    const TAZAPAY_CREATE_USER_ENDPOINT = 'tazapay_create_user_endpoint';
    const TAZAPAY_TXN_DESCRIPTION_FOR_ESCROW = 'tazapay_txn_description_for_escrow';
    const TAZAPAY_SELLER_EMAIL = 'tazapay_seller_email';
    const ESCROW_TXN_TYPE = "escrow_txn_type";
    const RELEASE_MECHANISM = "release_mechanism";
    const ESCROW_FEE_PAID_BY = "fee_paid_by";
    const ESCROW_FEE_PERCENTAGE = "fee_percentage";
    const SELLER_TYPE = "seller_type";
    const MULTI_SELLER_MARKETPLACE_EXTENSION = "multi_seller_marketplace_extension";
    
    /**
     * Module isActive
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * GetTitle
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getValue(self::TITLE);
    }

    /**
     * GetEnvironment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getValue(self::ENVIRONMENT);
    }

    /**
     * GetSandboxApiKey
     *
     * @return string
     */
    public function getSandboxApiKey()
    {
        return $this->getValue(self::TAZAPAY_SANDBOX_API_KEY);
    }

    /**
     * GetSandboxApiSecretKey
     *
     * @return string
     */
    public function getSandboxApiSecretKey()
    {
        return $this->getValue(self::TAZAPAY_SANDBOX_API_SECRET_KEY);
    }

    /**
     * GetProductionApiKey
     *
     * @return string
     */
    public function getProductionApiKey()
    {
        return $this->getValue(self::TAZAPAY_PRODUCTION_API_KEY);
    }

    /**
     * GetProductionApiSecretKey
     *
     * @return string
     */
    public function getProductionApiSecretKey()
    {
        return $this->getValue(self::TAZAPAY_PRODUCTION_API_SECRET_KEY);
    }

    /**
     * GetSandboxApiUrl
     *
     * @return string
     */
    public function getSandboxApiUrl()
    {
        return $this->getValue(self::CGI_URL_SANDBOX);
    }

    /**
     * GetProductionApiUrl
     *
     * @return string
     */
    public function getProductionApiUrl()
    {
        return $this->getValue(self::CGI_URL_PRODUCTION);
    }

    /**
     * GetCreateUserEndpoint
     *
     * @return string
     */
    public function getCreateUserEndpoint()
    {
        return $this->getValue(self::TAZAPAY_CREATE_USER_ENDPOINT);
    }
    
    /**
     * GetTxnDescriptionForEscrow
     *
     * @return string
     */
    public function getTxnDescriptionForEscrow()
    {
        return $this->getValue(self::TAZAPAY_TXN_DESCRIPTION_FOR_ESCROW);
    }

    /**
     * GetSellerEmail
     *
     * @return string
     */
    public function getSellerEmail()
    {
        return $this->getValue(self::TAZAPAY_SELLER_EMAIL);
    }

    /**
     * GetEscrowTxnType
     *
     * @return string
     */
    public function getEscrowTxnType()
    {
        return $this->getValue(self::ESCROW_TXN_TYPE);
    }
    
    /**
     * GetReleaseMechanism
     *
     * @return string
     */
    public function getReleaseMechanism()
    {
        return $this->getValue(self::RELEASE_MECHANISM);
    }

    /**
     * GetEscrowFeePaidBy
     *
     * @return string
     */
    public function getEscrowFeePaidBy()
    {
        return $this->getValue(self::ESCROW_FEE_PAID_BY);
    }
    
    /**
     * GetEscrowFeePercentage
     *
     * @return string
     */
    public function getEscrowFeePercentage()
    {
        return $this->getValue(self::ESCROW_FEE_PERCENTAGE);
    }
    
    /**
     * Get SellerType
     *
     * @return string
     */
    public function getSellerType()
    {
        return $this->getValue(self::SELLER_TYPE);
    }

    /**
     * Get MultiSellerMarketplaceExtension
     *
     * @return string
     */
    public function getMultiSellerMarketplaceExtension()
    {
        return $this->getValue(self::MULTI_SELLER_MARKETPLACE_EXTENSION);
    }
}