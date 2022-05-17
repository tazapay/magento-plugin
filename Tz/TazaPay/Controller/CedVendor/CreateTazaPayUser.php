<?php
/**
 * Copyright © 2021 Tz TazaPay Development Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Tz\TazaPay\Controller\CedVendor;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

class CreateTazaPayUser extends \Magento\Framework\App\Action\Action
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_json;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     * @param \Tz\TazaPay\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger = null,
        \Tz\TazaPay\Helper\Data $helper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->helper = $helper;
        $this->curlFactory = $curlFactory;
        $this->jsonHelper = $jsonHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->_countryFactory = $countryFactory;
        $this->_json = $json;
    }

    /**
     * Post user question
     *
     * @return Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        
        if (!$this->isPostRequest()) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
       
        try {
           
            $cedVendorEmail = $data['email'];
            $countryCode = $data['country_id'];
            $countryName = $this->getCountryName($countryCode);
            $dialNumber = $this->helper->getPhoneCode($countryCode);
            $telephone = $data['telephone'];
            $ind_bus_type = $data['ind_bus_type'];
            if ($ind_bus_type =="Individual") {
                $firstName = $data['firstname'];
                $lastName = $data['lastname'];
            } elseif ($ind_bus_type =="Business") {
                $businessName = $data['businessname'];
            }
            // Get environment
            $environment = $this->helper->getEnvironment();

            $tazaPayAccountUUID = "";
           
            if ($environment == "sandbox") {
                $apiKey = $this->helper->getSandboxApiKey();
                $apiSecretKey = $this->helper->getSandboxApiSecretKey();
                $apiUrl = $this->helper->getSandboxApiUrl();
            } else {
                $apiKey = $this->helper->getProductionApiKey();
                $apiSecretKey = $this->helper->getProductionApiSecretKey();
                $apiUrl = $this->helper->getProductionApiUrl();
            }
            $tazaPayUser =  $this->helper->getTazaPayUserByEmail($cedVendorEmail);
            if ($tazaPayUser['status'] == 'error') {

                /*
                *==================================*
                *          Create tazapay user
                *==================================*
                */
                $method = "POST";
                $createUserEndpoint = $this->helper->getCreateUserEndpoint();
                $salt = $this->helper->generateSalt();
                $timestamp = time();
                // Generate to_sign
                $to_sign = $method.$createUserEndpoint.$salt.$timestamp.$apiKey.$apiSecretKey;
                // Get signature
                $signature = $this->getSignature($to_sign, $apiSecretKey);
                $createUserApiUrl = $apiUrl.$createUserEndpoint;
                // Make array for passing parameter in request
                if ($ind_bus_type =="Individual") {
                    //  ind_bus_type is Individual
                    $userData = [
                        "email"=> $cedVendorEmail,
                        "first_name"=> $firstName,
                        "last_name"=> $lastName,
                        "contact_code"=> $dialNumber,
                        "contact_number"=> $telephone,
                        "country"=> $countryCode,
                        "ind_bus_type" => $ind_bus_type
                    ];
                } elseif ($ind_bus_type =="Business") {
                    // ind_bus_type is Business
                    $userData = [
                        "email"=> $cedVendorEmail,
                        "business_name"=> $businessName,
                        "contact_code"=> $dialNumber,
                        "contact_number"=> $telephone,
                        "country"=> $countryCode,
                        "ind_bus_type" => $ind_bus_type
                    ];
                }
               
                // Convert array to json
                $params = $this->getJsonEncode($userData);
                // Set header
                $setHeader = [
                    'accesskey: '.$apiKey,
                    'salt: '.$salt,
                    'signature: '.$signature,
                    'timestamp: '.$timestamp,
                    'Content-Type: application/json'
                ];
                /* Create curl factory */
                $httpAdapter = $this->curlFactory->create();
                // Initiate request
                $httpAdapter->write(
                    \Zend_Http_Client::POST, // POST method
                    $createUserApiUrl, // api url
                    '1.1', // curl http client version
                    $setHeader, // set header
                    $params // pass parameter with json format
                );
                // execute api request
                $result = $httpAdapter->read();
                // get response
                $body = \Zend_Http_Response::extractBody($result);
                $response = $this->jsonHelper->jsonDecode($body);
                $status = $response['status'];
                
                if ($status == "error" && $status !="success") {
                    $this->messageManager->addErrorMessage($response['message']);
                } elseif ($status == "success" && $status !="error") {
                    $account_id = $response['data']['account_id'];
                    $this->messageManager->addSuccessMessage(
                        __($response['message'])
                    );
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your form. Please try again later.')
            );
        }
        return $this->resultRedirectFactory->create()->setPath('tazapay/cedvendor/tazapayaccountinfo');
    }

    /**
     * Postrequest
     *
     * @return bool
     */
    private function isPostRequest()
    {
        /**
         * Post request
         *
         * @var Request $request
         */
        $request = $this->getRequest();
        return !empty($request->getPostValue());
    }

    /**
     * Get countryname
     *
     * @param mixed $countryCode
     */
    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * Generate signature
     * $hmacSHA256 is generate hmacSHA256
     * $signature is convert hmacSHA256 into base64 encode
     * in document: signature = Base64(hmacSHA256(to_sign, API-Secret))
     *
     * @param mixed $to_sign
     * @param mixed $apiSecretKey
     */
    public function getSignature($to_sign, $apiSecretKey)
    {
        $hmacSHA256 = hash_hmac('sha256', $to_sign, $apiSecretKey);
        $signature = base64_encode($hmacSHA256);
        return $signature;
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
     * Get jsondecode
     *
     * @param mixed $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data); // it's same as like json_decode
    }
}
