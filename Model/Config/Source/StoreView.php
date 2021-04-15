<?php
namespace MageSuite\ProductTileWarmup\Model\Config\Source;

class StoreView implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray(): array
    {
        return array_map(
            function (\Magento\Store\Api\Data\StoreInterface $store) {
                $group = $this->storeManager->getGroup($store->getStoreGroupId());

                return [
                    'label' => sprintf(
                        '%s / %s (%s)',
                        $group->getName(),
                        $store->getName(),
                        $store->getCode(),
                    ),
                    'value' => $store->getId()
                ];
            },
            $this->storeManager->getStores()
        );
    }
}

