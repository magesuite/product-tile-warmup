<?php

namespace MageSuite\ProductTileWarmup\Block\Product\ProductList;

class Toolbar  extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    const LIMIT = 100;

    public function setCollection($collection)
    {
        parent::setCollection($collection);

        if (!$collection->hasFlag('warmed_tiles_entity_ids_filter_set')) {
            $alreadyWarmedUpIds = $this->getAlreadyWarmedUpProductsIds();
            $collection->addFieldToFilter('entity_id', ['nin' => $alreadyWarmedUpIds]);
            $collection->setFlag('warmed_tiles_entity_ids_filter_set', true);
        }

        return $this;
    }

    public function getLimit()
    {
        return self::LIMIT;
    }

    public function isExpanded()
    {
        return true;
    }

    /**
     * ObjectManager is used directly to not override constructor
     */
    public function getAlreadyWarmedUpProductsIds() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $objectManager
            ->get(\MageSuite\ProductTileWarmup\Model\GetWarmedUpProductsIds::class)
            ->execute();
    }
}
