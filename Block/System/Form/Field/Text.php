<?php

namespace MageSuite\ProductTileWarmup\Block\System\Form\Field;

class Text extends \Magento\Framework\View\Element\AbstractBlock
{
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }
        return '<%- store_code %>';
    }
}
