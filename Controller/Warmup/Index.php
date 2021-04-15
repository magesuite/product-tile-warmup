<?php

namespace MageSuite\ProductTileWarmup\Controller\Warmup;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->context = $context;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
