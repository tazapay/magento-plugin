<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Tz\TazaPay\Gateway\Config\Config;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository;
use Exception;
use Psr\Log\LoggerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'tazapay';
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
 
    /**
     * Repository
     *
     * @var Repository
     */
    private $assetRepository;
    
    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $localeResolver
     * @param Repository $assetRepository
     * @param LoggerInterface $logger
     */

    public function __construct(
        Config $config,
        UrlInterface $urlBuilder,
        ResolverInterface $localeResolver,
        Repository $assetRepository,
        LoggerInterface $logger
    ) {
    
        $this->config = $config;
        $this->_urlBuilder = $urlBuilder;
        $this->assetRepository = $assetRepository;
    }
    /**
     * Get tazapay payment configuration
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'redirectUrl'=> $this->getRedirectUrl(),
                    'createTazaPayAccountUrl'=> $this->getCreateTazaPayAccountUrl(),
                    'title' => $this->config->getTitle(),
                    'environment' => $this->config->getEnvironment(),
                    'shortDescription' => $this->config->getTxnDescriptionForEscrow(),
                    'tazapayDarkLogo' => $this->getTazapayDarkLogo()
                ]
            ]
        ];
    }

    /**
     * Get redirect url
     */
    public function getRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('tazapay/index/auth');
    }

    /**
     * Get create tazapay account url
     */
    public function getCreateTazaPayAccountUrl()
    {
        return $this->_urlBuilder->getUrl('tazapay/user/tazapayaccountinfo');
    }

    /**
     * Get tazapay logo image full path
     */
    public function getTazapayDarkLogo()
    {
        $fileId = 'Tz_TazaPay::images/tazapay-logo-dark.svg';
 
        $params = [
            'area' => 'frontend'
        ];
 
        $imageFullPath = null;
        try {
            $imageFullPath = $this->assetRepository->getUrlWithParams($fileId, $params);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
 
        return $imageFullPath;
    }
}
