<?php
namespace MageSuite\ProductTileWarmup\Model\Config\Source;

use MageSuite\ProductTileWarmup\Helper\Configuration;

class CustomerGroup implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\Config\Source\Group
     */
    protected $customerGroupSource;

    public function __construct(
        \Magento\Customer\Model\Config\Source\Group $customerGroupSource
    ) {
        $this->customerGroupSource = $customerGroupSource;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray(): array
    {
        return array_merge(
            [
                [
                    'value' => Configuration::CUSTOMER_GROUP_GUEST_ID,
                    'label' => 'Guest (not signed-in)'
                ]
            ],
            array_slice($this->customerGroupSource->toOptionArray(), 1)
        );
    }
}
