<?php

namespace MageSuite\ProductTileWarmup\Controller\Warmup;

class LoginPost implements
    \Magento\Framework\App\Action\HttpPostActionInterface,
    \Magento\Framework\App\CsrfAwareActionInterface
{
    const EMAIL_VALIDATION_REGEXP = '/^s([0-9]+)-cg([0-9]+)\+warmup@cache-warmup\.(.+)$/';

    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement;
    protected \Magento\Customer\Model\Url $customerHelperData;
    protected \Magento\Framework\Stdlib\Cookie\PhpCookieManager $cookieManager;
    protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory;
    protected \Magento\Framework\App\Action\Context $context;
    protected \Magento\Framework\App\Request\Http $request;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Model\Url $customerHelperData,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Stdlib\Cookie\PhpCookieManager $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->customerSession = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerHelperData = $customerHelperData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $jsonResult = $this->resultJsonFactory->create();

        if ($this->customerSession->isLoggedIn()) {
            $jsonResult->setData(['result' => 'already_logged_in']);

            return $jsonResult;
        }

        if (!$this->request->isPost()) {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => 'request_must_be_post']);

            return $jsonResult;
        }

        $login = $this->request->getPost('login');

        if (empty($login['username']) || empty($login['password'])) {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => 'login_credentials_not_passed']);

            return $jsonResult;
        }

        $email = $login['username'];

        if (!$this->isEmailFromWarmupAccount($email)) {
            $jsonResult->setHttpResponseCode(400);
            $jsonResult->setData(['error' => sprintf('Provided email %s is not valid', $email)]);

            return $jsonResult;
        }

        try {
            $customer = $this->customerAccountManagement->authenticate($email, $login['password']);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);

            if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                $metadata->setPath('/');

                $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
            }

            $jsonResult->setData(['result' => 'logged_in_successfully']);

            return $jsonResult;
        } catch (\Exception $e) {
            $jsonResult->setHttpResponseCode(500);
            $jsonResult->setData(['error' => $e->getMessage()]);

            return $jsonResult;
        }
    }

    public function createCsrfValidationException(\Magento\Framework\App\RequestInterface $request): ?\Magento\Framework\App\Request\InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ?bool
    {
        return true;
    }

    public function isEmailFromWarmupAccount($email): bool
    {
        return preg_match(self::EMAIL_VALIDATION_REGEXP, $email);
    }
}
