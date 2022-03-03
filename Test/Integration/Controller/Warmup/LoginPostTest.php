<?php

namespace MageSuite\ProductTileWarmup\Test\Integration\Controller\Warmup;

class LoginPostTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->customerSession = $this->objectManager->create(\Magento\Customer\Model\Session::class);

        parent::setUp();
    }

    /**
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @magentoDataFixture loadWarmupCustomers
     */
    public function testLoginWithEmailNotFromWarmup(): void
    {
        $this->prepareRequest('customer@example.com', 'password');
        $this->dispatch('tile/warmup/loginPost');

        $response = json_decode((string)$this->getResponse()->getBody(), true);

        $this->assertEquals('Provided email customer@example.com is not valid', $response['error']);
        $this->assertEquals(400, $this->getResponse()->getStatusCode());
        $this->assertFalse($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @magentoDataFixture loadWarmupCustomers
     */
    public function testLoginWithIncorrectPassword(): void
    {
        $this->prepareRequest('s11-cg11+warmup@cache-warmup.magesuite.io', 'incorrect_password');
        $this->dispatch('tile/warmup/loginPost');

        $response = json_decode((string)$this->getResponse()->getBody(), true);

        $this->assertEquals('Invalid login or password.', $response['error']);
        $this->assertEquals(500, $this->getResponse()->getStatusCode());
        $this->assertFalse($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @magentoDataFixture loadWarmupCustomers
     */
    public function testLoginWithCorrectCredentials(): void
    {
        $this->prepareRequest('s11-cg11+warmup@cache-warmup.magesuite.io', 'password');
        $this->dispatch('tile/warmup/loginPost');

        $response = json_decode((string)$this->getResponse()->getBody(), true);

        $this->assertEquals('logged_in_successfully', $response['result']);
        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertTrue($this->customerSession->isLoggedIn());
    }

    public static function loadWarmupCustomers()
    {
        require __DIR__.'/../../_files/warmup_customer.php';
    }

    protected function prepareRequest(?string $email, ?string $password): void
    {
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => $email,
                'password' => $password,
            ],
        ]);
    }
}
