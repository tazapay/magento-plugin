<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLE = 'payment/tazapay/active';
    public const XML_PATH_TITLE = 'payment/tazapay/title';
    public const XML_PATH_ENVIRONMENT = 'payment/tazapay/environment';
    public const XML_PATH_TAZAPAY_SANDBOX_API_KEY = 'payment/tazapay/tazapay_sandbox_api_key';
    public const XML_PATH_TAZAPAY_SANDBOX_API_SECRET_KEY = 'payment/tazapay/tazapay_sandbox_api_secret_key';
    public const XML_PATH_TAZAPAY_PRODUCTION_API_KEY = 'payment/tazapay/tazapay_production_api_key';
    public const XML_PATH_TAZAPAY_PRODUCTION_API_SECRET_KEY = 'payment/tazapay/tazapay_production_api_secret_key';
    public const XML_PATH_CGI_URL_SANDBOX = 'payment/tazapay/cgi_url_sandbox';
    public const XML_PATH_CGI_URL_PRODUCTION = 'payment/tazapay/cgi_url_production';
    public const XML_PATH_TAZAPAY_CREATE_USER_ENDPOINT = 'payment/tazapay/tazapay_create_user_endpoint';
    public const XML_PATH_TAZAPAY_CREATE_ESCROW_ENDPOINT = 'payment/tazapay/tazapay_create_escrow_endpoint';
    public const XML_PATH_TAZAPAY_CREATE_PAYMENT_ENDPOINT = 'payment/tazapay/tazapay_create_payment_endpoint';
    public const XML_PATH_TAZAPAY_TXN_DESCRIPTION_FOR_ESCROW = 'payment/tazapay/tazapay_txn_description_for_escrow';
    public const XML_PATH_TAZAPAY_SELLER_EMAIL = 'payment/tazapay/tazapay_seller_email';
    public const XML_PATH_TAZAPAY_SELLER_IND_BUS_TYPE = 'payment/tazapay/tazapay_seller_ind_bus_type';
    public const XML_PATH_ESCROW_TXN_TYPE = 'payment/tazapay/escrow_txn_type';
    public const XML_PATH_RELEASE_MECHANISM = 'payment/tazapay/release_mechanism';
    public const XML_PATH_ESCROW_FEE_PAID_BY = 'payment/tazapay/fee_paid_by';
    public const XML_PATH_ESCROW_FEE_PERCENTAGE = 'payment/tazapay/fee_percentage';
    public const XML_PATH_SELLER_TYPE = 'payment/tazapay/seller_type';
    public const XML_PATH_MULTI_SELLER_MARKETPLACE_EXTENSION = 'payment/tazapay/multi_seller_marketplace_extension';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Serialize\Serializer\Json $json,
        EncryptorInterface $encryptor
    ) {
        $this->curl = $curl;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_currency = $currency;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->_countryFactory = $countryFactory;
        $this->_json = $json;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Get isEnabled
     *
     * @param mixed $scope
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE,
            $scope
        );
    }

    /**
     * GetTitle
     *
     * @return string
     */
    public function getTitle()
    {
        //for Store
        return $this->scopeConfig->getValue(
            self::XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GetEnvironment
     *
     * @param mixed $scope
     * @return string
     */
    public function getEnvironment($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENVIRONMENT,
            $scope
        );
    }

    /**
     * GetSandboxApiKey
     *
     * @param mixed $scope
     * @return string
     */
    public function getSandboxApiKey($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SANDBOX_API_KEY,
            $scope
        );
    }

    /**
     * GetSandboxApiSecretKey
     *
     * @param mixed $scope
     * @return string
     */
    public function getSandboxApiSecretKey($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SANDBOX_API_SECRET_KEY,
            $scope
        );
    }

    /**
     * GetProductionApiKey
     *
     * @param mixed $scope
     * @return string
     */
    public function getProductionApiKey($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_PRODUCTION_API_KEY,
            $scope
        );
    }

    /**
     * GetProductionApiSecretKey
     *
     * @param mixed $scope
     * @return string
     */
    public function getProductionApiSecretKey($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_PRODUCTION_API_SECRET_KEY,
            $scope
        );
    }

    /**
     * GetSandboxApiUrl
     *
     * @param mixed $scope
     * @return string
     */
    public function getSandboxApiUrl()
    {
        //for website
        return $this->scopeConfig->getValue(
            self::XML_PATH_CGI_URL_SANDBOX,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * GetProductionApiUrl
     *
     * @param mixed $scope
     * @return string
     */
    public function getProductionApiUrl()
    {
        //for website
        return $this->scopeConfig->getValue(
            self::XML_PATH_CGI_URL_PRODUCTION,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * GetCreateUserEndpoint
     *
     * @param mixed $scope
     * @return string
     */
    public function getCreateUserEndpoint()
    {
        //for website
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_CREATE_USER_ENDPOINT,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * GetCreateEscrowEndpoint
     *
     * @param mixed $scope
     * @return string
     */
    public function getCreateEscrowEndpoint()
    {
        //for website
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_CREATE_ESCROW_ENDPOINT,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * GetCreatePaymentEndpoint
     *
     * @param mixed $scope
     * @return string
     */
    public function getCreatePaymentEndpoint()
    {
        //for website
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_CREATE_PAYMENT_ENDPOINT,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * GetTxnDescriptionForEscrow
     *
     * @param mixed $scope
     * @return string
     */
    public function getTxnDescriptionForEscrow($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_TXN_DESCRIPTION_FOR_ESCROW,
            $scope
        );
    }

    /**
     * GetSellerName
     *
     * @param mixed $scope
     * @return string
     */
    public function getSellerName($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SELLER_NAME,
            $scope
        );
    }

    /**
     * GetSellerEmail
     *
     * @param mixed $scope
     * @return string
     */
    public function getSellerEmail($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SELLER_EMAIL,
            $scope
        );
    }

    /**
     * GetSellerCountry
     *
     * @param mixed $scope
     * @return string
     */
    public function getSellerCountry($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SELLER_COUNTRY,
            $scope
        );
    }

    /**
     * GetSellerIndBusType
     *
     * @param mixed $scope
     * @return string
     */
    public function getSellerIndBusType($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAZAPAY_SELLER_IND_BUS_TYPE,
            $scope
        );
    }

    /**
     * GetEscrowTxnType
     *
     * @param mixed $scope
     * @return string
     */
    public function getEscrowTxnType($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ESCROW_TXN_TYPE,
            $scope
        );
    }
    
    /**
     * GetReleaseMechanism
     *
     * @param mixed $scope
     * @return string
     */
    public function getReleaseMechanism($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RELEASE_MECHANISM,
            $scope
        );
    }

    /**
     * GetEscrowFeePaidBy
     *
     * @param mixed $scope
     * @return string
     */
    public function getEscrowFeePaidBy($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ESCROW_FEE_PAID_BY,
            $scope
        );
    }
    
    /**
     * GetEscrowFeePercentage
     *
     * @param mixed $scope
     * @return string
     */
    public function getEscrowFeePercentage($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ESCROW_FEE_PERCENTAGE,
            $scope
        );
    }
    
    /**
     * GetSellerType
     *
     * @param mixed $scope
     * @return string
     */
    public function getSellerType($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SELLER_TYPE,
            $scope
        );
    }

    /**
     * GetMultiSellerMarketplaceExtension
     *
     * @param mixed $scope
     * @return string
     */
    public function getMultiSellerMarketplaceExtension($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MULTI_SELLER_MARKETPLACE_EXTENSION,
            $scope
        );
    }

    /**
     * Get phone code
     *
     * @param  mixed $countryCode
     * @return string
     */
    public function getPhoneCode($countryCode)
    {
        $countryCodeArray = [
            'AD'=>'376',
            'AE'=>'971',
            'AF'=>'93',
            'AG'=>'1268',
            'AI'=>'1264',
            'AL'=>'355',
            'AM'=>'374',
            'AN'=>'599',
            'AO'=>'244',
            'AQ'=>'672',
            'AR'=>'54',
            'AS'=>'1684',
            'AT'=>'43',
            'AU'=>'61',
            'AW'=>'297',
            'AZ'=>'994',
            'BA'=>'387',
            'BB'=>'1246',
            'BD'=>'880',
            'BE'=>'32',
            'BF'=>'226',
            'BG'=>'359',
            'BH'=>'973',
            'BI'=>'257',
            'BJ'=>'229',
            'BL'=>'590',
            'BM'=>'1441',
            'BN'=>'673',
            'BO'=>'591',
            'BR'=>'55',
            'BS'=>'1242',
            'BT'=>'975',
            'BW'=>'267',
            'BY'=>'375',
            'BZ'=>'501',
            'CA'=>'1',
            'CC'=>'61',
            'CD'=>'243',
            'CF'=>'236',
            'CG'=>'242',
            'CH'=>'41',
            'CI'=>'225',
            'CK'=>'682',
            'CL'=>'56',
            'CM'=>'237',
            'CN'=>'86',
            'CO'=>'57',
            'CR'=>'506',
            'CU'=>'53',
            'CV'=>'238',
            'CX'=>'61',
            'CY'=>'357',
            'CZ'=>'420',
            'DE'=>'49',
            'DJ'=>'253',
            'DK'=>'45',
            'DM'=>'1767',
            'DO'=>'1809',
            'DZ'=>'213',
            'EC'=>'593',
            'EE'=>'372',
            'EG'=>'20',
            'ER'=>'291',
            'ES'=>'34',
            'ET'=>'251',
            'FI'=>'358',
            'FJ'=>'679',
            'FK'=>'500',
            'FM'=>'691',
            'FO'=>'298',
            'FR'=>'33',
            'GA'=>'241',
            'GB'=>'44',
            'GD'=>'1473',
            'GE'=>'995',
            'GH'=>'233',
            'GI'=>'350',
            'GL'=>'299',
            'GM'=>'220',
            'GN'=>'224',
            'GQ'=>'240',
            'GR'=>'30',
            'GT'=>'502',
            'GU'=>'1671',
            'GW'=>'245',
            'GY'=>'592',
            'HK'=>'852',
            'HN'=>'504',
            'HR'=>'385',
            'HT'=>'509',
            'HU'=>'36',
            'ID'=>'62',
            'IE'=>'353',
            'IL'=>'972',
            'IM'=>'44',
            'IN'=>'91',
            'IQ'=>'964',
            'IR'=>'98',
            'IS'=>'354',
            'IT'=>'39',
            'JM'=>'1876',
            'JO'=>'962',
            'JP'=>'81',
            'KE'=>'254',
            'KG'=>'996',
            'KH'=>'855',
            'KI'=>'686',
            'KM'=>'269',
            'KN'=>'1869',
            'KP'=>'850',
            'KR'=>'82',
            'KW'=>'965',
            'KY'=>'1345',
            'KZ'=>'7',
            'LA'=>'856',
            'LB'=>'961',
            'LC'=>'1758',
            'LI'=>'423',
            'LK'=>'94',
            'LR'=>'231',
            'LS'=>'266',
            'LT'=>'370',
            'LU'=>'352',
            'LV'=>'371',
            'LY'=>'218',
            'MA'=>'212',
            'MC'=>'377',
            'MD'=>'373',
            'ME'=>'382',
            'MF'=>'1599',
            'MG'=>'261',
            'MH'=>'692',
            'MK'=>'389',
            'ML'=>'223',
            'MM'=>'95',
            'MN'=>'976',
            'MO'=>'853',
            'MP'=>'1670',
            'MR'=>'222',
            'MS'=>'1664',
            'MT'=>'356',
            'MU'=>'230',
            'MV'=>'960',
            'MW'=>'265',
            'MX'=>'52',
            'MY'=>'60',
            'MZ'=>'258',
            'NA'=>'264',
            'NC'=>'687',
            'NE'=>'227',
            'NG'=>'234',
            'NI'=>'505',
            'NL'=>'31',
            'NO'=>'47',
            'NP'=>'977',
            'NR'=>'674',
            'NU'=>'683',
            'NZ'=>'64',
            'OM'=>'968',
            'PA'=>'507',
            'PE'=>'51',
            'PF'=>'689',
            'PG'=>'675',
            'PH'=>'63',
            'PK'=>'92',
            'PL'=>'48',
            'PM'=>'508',
            'PN'=>'870',
            'PR'=>'1',
            'PT'=>'351',
            'PW'=>'680',
            'PY'=>'595',
            'QA'=>'974',
            'RO'=>'40',
            'RS'=>'381',
            'RU'=>'7',
            'RW'=>'250',
            'SA'=>'966',
            'SB'=>'677',
            'SC'=>'248',
            'SD'=>'249',
            'SE'=>'46',
            'SG'=>'65',
            'SH'=>'290',
            'SI'=>'386',
            'SK'=>'421',
            'SL'=>'232',
            'SM'=>'378',
            'SN'=>'221',
            'SO'=>'252',
            'SR'=>'597',
            'ST'=>'239',
            'SV'=>'503',
            'SY'=>'963',
            'SZ'=>'268',
            'TC'=>'1649',
            'TD'=>'235',
            'TG'=>'228',
            'TH'=>'66',
            'TJ'=>'992',
            'TK'=>'690',
            'TL'=>'670',
            'TM'=>'993',
            'TN'=>'216',
            'TO'=>'676',
            'TR'=>'90',
            'TT'=>'1868',
            'TV'=>'688',
            'TW'=>'886',
            'TZ'=>'255',
            'UA'=>'380',
            'UG'=>'256',
            'US'=>'1',
            'UY'=>'598',
            'UZ'=>'998',
            'VA'=>'39',
            'VC'=>'1784',
            'VE'=>'58',
            'VG'=>'1284',
            'VI'=>'1340',
            'VN'=>'84',
            'VU'=>'678',
            'WF'=>'681',
            'WS'=>'685',
            'XK'=>'381',
            'YE'=>'967',
            'YT'=>'262',
            'ZA'=>'27',
            'ZM'=>'260',
            'ZW'=>'263'
        ];
        
        $phoneCode = @$countryCodeArray[$countryCode];
        return $phoneCode;
    }

    /**
     * Get country name
     *
     * @param  mixed $countryCode
     * @return string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * Generate basicAuth
     * Encode the $basic_auth format into base64 encode
     * in document: Authorization = "Basic " + base64_encode($basic_auth);
     *
     * @param mixed $apiKey
     * @param mixed $apiSecretKey
     */

    public function basicAuthorization($apiKey, $apiSecretKey)
    {
        $basic_auth = $apiKey . ':' . $apiSecretKey;
		$authorization = "Basic " . base64_encode($basic_auth);
		return $authorization;
    }

    /**
     * Get jsonencode
     *
     * @param mixed $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->_json->serialize($data); // it's same as like json_encode
    }

    /**
     * Get JsonDecode
     *
     * @param mixed $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data); // it's same as like json_decode
    }
    
    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    /**
     * Get website identifier
     *
     * @return string|int|null
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
    
    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
    
    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
    
    /**
     * Get current url for store
     *
     * @param bool|string $fromStore Include/Exclude from_store parameter from URL
     * @return string
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }
    
    /**
     * Check if store is active
     *
     * @return boolean
     */
    public function isStoreActive()
    {
        return $this->_storeManager->getStore()->isActive();
    }

    /**
     * Get default store currency code
     *
     * @return string
     */
    public function getDefaultCurrencyCode()
    {
        return $this->_storeManager->getStore()->getDefaultCurrencyCode();
    }
    
    /**
     * Get store base currency code
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }
    
    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * Baseurl
     *
     * @return string
     */
    public function getBaseurl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_WEB
        );
    }

    /**
     * Get tazapay user by email address
     *
     * @param string $email
     * @return void
     */
    public function getTazaPayUserByEmail($email)
    {
        $environment = $this->getEnvironment();
        if ($environment == "sandbox") {
            $apiKey = $this->getSandboxApiKey();
            $apiSecretKey = $this->getSandboxApiSecretKey();
            $apiUrl = $this->getSandboxApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        } else {
            $apiKey = $this->getProductionApiKey();
            $apiSecretKey = $this->getProductionApiSecretKey();
            $apiUrl = $this->getProductionApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        }
        /*      
        *==================================*
        *          Get user
        *==================================*
        */
        $method = "GET";
        $geUserEndpoint = $this->getCreateUserEndpoint()."/".$email;
        $getUserApiUrl = $apiUrl.$geUserEndpoint;
        // Get authorization
        $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
        // Set header
        $setHeader = [
            'Authorization: '.$authorization,
            'Content-Type: application/json'
        ];
        /* Create curl factory */
        $httpAdapter = $this->curlFactory->create();
        
        // Initiate request
        $httpAdapter->write(
            \Zend_Http_Client::GET, // GET method
            $getUserApiUrl, // api url
            '1.1', // curl http client version
            $setHeader
        );
        // execute api request
        $result = $httpAdapter->read();
        // get response
        $body = \Zend_Http_Response::extractBody($result);
        /* convert JSON to Array */
        $response = $this->jsonHelper->jsonDecode($body);
       
        $status = @$response['status'];
        $result = [];
        $error_message = null;
        if ($status == "error" && $status !="success") {
            $result['status'] = "error";
            $error_message = $response['message'];
            foreach ($response['errors'] as $key => $error) {
                if (isset($error['code'])) {
                    $error_message .= ", code: ".$error['code'];
                }
                if (isset($error['message'])) {
                    $error_message .= ", Message: ".$error['message'];
                }
                if (isset($error['remarks'])) {
                    $error_message .= ", Remarks: ".$error['remarks'];
                }
                
            }
            $result['message'] = $error_message;
        } elseif ($status == "success" && $status !="error") {
            $result['status'] = "success";
            $result['data'] = $response['data'];
        }
        
        return $result;
    }

    /**
     * Country config API
     *
     * @param string $sellerCountryCode
     * @return void
     */
    public function getCountryConfig($sellerCountryCode)
    {
        $environment = $this->getEnvironment();
        if ($environment == "sandbox") {
            $apiKey = $this->getSandboxApiKey();
            $apiSecretKey = $this->getSandboxApiSecretKey();
            $apiUrl = $this->getSandboxApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        } else {
            $apiKey = $this->getProductionApiKey();
            $apiSecretKey = $this->getProductionApiSecretKey();
            $apiUrl = $this->getProductionApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        }
        
        /*      
        *==================================*
        *          Country config API
        *==================================*
        */
        $method = "GET";
        $countryConfigEndpoint = "/v1/metadata/countryconfig";
        $countryConfigApiUrl = $apiUrl.$countryConfigEndpoint."?country=".$sellerCountryCode;
        // Get authorization
        $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
        // Set header
        $setHeader = [
            'Authorization: '.$authorization,
            'Content-Type: application/json'
        ];
        /* Create curl factory */
        $httpAdapter = $this->curlFactory->create();
        
        // Initiate request
        $httpAdapter->write(
            \Zend_Http_Client::GET, // GET method
            $countryConfigApiUrl, // api url
            '1.1', // curl http client version
            $setHeader
        );
        // execute api request
        $result = $httpAdapter->read();
        // get response
        $body = \Zend_Http_Response::extractBody($result);
        /* convert JSON to Array */
        $response = $this->jsonHelper->jsonDecode($body);

        $status = @$response['status'];
        $result = [];
        $error_message = null;
        if ($status == "error" && $status !="success") {
            $result['status'] = "error";
            $error_message = $response['message'];
            foreach ($response['errors'] as $key => $error) {
                if (isset($error['code'])) {
                    $error_message .= ", code: ".$error['code'];
                }
                if (isset($error['message'])) {
                    $error_message .= ", Message: ".$error['message'];
                }
                if (isset($error['remarks'])) {
                    $error_message .= ", Remarks: ".$error['remarks'];
                }
                
            }
            $result['message'] = $error_message;
        } elseif ($status == "success" && $status !="error") {
            $result['status'] = "success";
            $result['data'] = $response['data'];
        }
        
        return $result;
    }

    /**
     * Invoice currency config API
     *
     * @param string $buyerCountryCode
     * @param string $sellerCountryCode
     * @return void
     */
    public function getInvoiceCurrencyConfig($buyerCountryCode, $sellerCountryCode)
    {
        $environment = $this->getEnvironment();
        if ($environment == "sandbox") {
            $apiKey = $this->getSandboxApiKey();
            $apiSecretKey = $this->getSandboxApiSecretKey();
            $apiUrl = $this->getSandboxApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        } else {
            $apiKey = $this->getProductionApiKey();
            $apiSecretKey = $this->getProductionApiSecretKey();
            $apiUrl = $this->getProductionApiUrl();
            $currency= $this->getCurrentCurrencyCode();
        }
        
        /*      
        *==================================*
        *          Invoice currency config API
        *==================================*
        */
        $method = "GET";
        $invoiceCurrencyEndpoint = "/v1/metadata/invoicecurrency";
        $invoiceCurrencyApiUrl = $apiUrl.$invoiceCurrencyEndpoint."?buyer_country=".$buyerCountryCode."&seller_country=".$sellerCountryCode;
        // Get authorization
        $authorization = $this->basicAuthorization($apiKey, $apiSecretKey);
        // Set header
        $setHeader = [
            'Authorization: '.$authorization,
            'Content-Type: application/json'
        ];
        /* Create curl factory */
        $httpAdapter = $this->curlFactory->create();
        
        // Initiate request
        $httpAdapter->write(
            \Zend_Http_Client::GET, // GET method
            $invoiceCurrencyApiUrl, // api url
            '1.1', // curl http client version
            $setHeader
        );
        // execute api request
        $result = $httpAdapter->read();
        // get response
        $body = \Zend_Http_Response::extractBody($result);
        /* convert JSON to Array */
        $response = $this->jsonHelper->jsonDecode($body);

        $status = $response['status'];
        $result = [];
        $error_message = null;
        if ($status == "error" && $status !="success") {
            $result['status'] = "error";
            $error_message = $response['message'];
            foreach ($response['errors'] as $key => $error) {
                if (isset($error['code'])) {
                    $error_message .= ", code: ".$error['code'];
                }
                if (isset($error['message'])) {
                    $error_message .= ", Message: ".$error['message'];
                }
                if (isset($error['remarks'])) {
                    $error_message .= ", Remarks: ".$error['remarks'];
                }
                
            }
            $result['message'] = $error_message;
        } elseif ($status == "success" && $status !="error") {
            $result['status'] = "success";
            $result['data'] = $response['data'];
        }
        
        return $result;
    }
}