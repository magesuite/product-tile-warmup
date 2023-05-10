<?php

namespace MageSuite\ProductTileWarmup\Controller\Warmup;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    protected ?\Magento\Framework\View\Result\PageFactory $resultPageFactory;

    protected ?\Magento\Backend\App\Action\Context $context;

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
