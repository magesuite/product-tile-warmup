<?php
namespace MageSuite\ProductTileWarmup\Model\Config\Source;

class CustomerGroup implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Customer\Api\GroupManagementInterface
     */
    private $customerGroupManagement;

    public function __construct(
        \Magento\Customer\Api\GroupManagementInterface $customerGroupManagement
    ) {
        $this->customerGroupManagement = $customerGroupManagement;
    }

    /**
     * {@inheritDoc}
     * @throws null All exceptions are fatal.
     */
    public function toOptionArray(): array
    {
        return array_map(
            function (\Magento\Customer\Api\Data\GroupInterface $group): array {
                return [
                    'value' => $group->getId(),
                    'label' => $group->getCode(),
                ];
            },
            array_merge(
                [$this->customerGroupManagement->getNotLoggedInGroup()],
                $this->customerGroupManagement->getLoggedInGroups()
            )
        );
    }
}
