<?php
namespace MageSuite\ProductTileWarmup\Model\Config\Source;

class StoreView implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $storeCollection;

    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection
    ) {
        $this->storeCollection = $storeCollection;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        $options = [];
        $storesCollection = $this->storeCollection;

        foreach ($storesCollection as $store) {
            $options[] = [
                'label' => $store->getName(),
                'value' => $store->getStoreId()
            ];
        }

        return $options;
    }
}
