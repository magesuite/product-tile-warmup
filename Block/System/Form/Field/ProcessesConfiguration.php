<?php

namespace MageSuite\ProductTileWarmup\Block\System\Form\Field;

class ProcessesConfiguration extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $enabledColumnRenderer;
    protected $storeCodeColumnRenderer;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        $this->_elementFactory = $elementFactory;
        $this->storeManager = $storeManager;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->addColumn(
            'store_code',
            [
                'label' => __('Store'),
                'class' => 'required-entry',
                'renderer' => $this->getStoreCodeColumnRenderer()
            ]
        );

        $this->addColumn(
            'run_in_separate_process_group',
            [
                'label' => __('Run in separate process group'),
                'class' => 'required-entry',
                'renderer' => $this->getEnabledColumnRenderer()
            ]
        );

        $this->addColumn('group_id', ['label' => __('Group Id'), 'class' => 'required-entry validate-greater-than-zero']);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');

        parent::_construct();
    }

    public function getArrayRows()
    {
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement */
        $element = $this->getElement();
        $configValues = $element->getValue();

        $allStores = $this->prepareAllStoreViewsDefaultValues();

        if (!$configValues || !is_array($configValues)) {
            return $allStores;
        }

        return $this->modifyDefaultValuesToValuesFromConfiguration($allStores, $configValues);
    }

    protected function prepareAllStoreViewsDefaultValues()
    {
        $result = [];

        foreach ($this->storeManager->getStores() as $store) {
            $result[] = new \Magento\Framework\DataObject([
                'store_code' => $store->getCode(),
                'run_in_separate_process_group' => 0,
                'group_id' => 1,
                '_id' => $store->getId(),
                'option_extra_attrs' => [],
                'column_values' => [
                    $store->getId() . '_store_code' => $store->getCode(),
                    $store->getId() . '_run_in_separate_process_group' => 0,
                    $store->getId() . '_group_id' => 1,
                ]
            ]);
        }

        return $result;
    }

    function getEnabledColumnRenderer()
    {
        if (!$this->enabledColumnRenderer) {
            $this->enabledColumnRenderer = $this->getLayout()->createBlock(
                Enabled::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->enabledColumnRenderer;
    }

    protected function getStoreCodeColumnRenderer()
    {
        if (!$this->storeCodeColumnRenderer) {
            $this->storeCodeColumnRenderer = $this->getLayout()->createBlock(
                Text::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->storeCodeColumnRenderer;
    }

    protected function findStoreObjectByStoreId(array $result, int $storeId)
    {
        foreach($result as $store) {
            if ($store->getData('_id') == $storeId) {
                return $store;
            }
        }

        return null;
    }

    protected function modifyDefaultValuesToValuesFromConfiguration($allStores, $configValues)
    {
        foreach ($configValues as $storeId => $storeConfiguration) {
            if(!($store = $this->findStoreObjectByStoreId($allStores, $storeId))) {
                continue;
            }

            $columnValues = $store->getData('column_values');

            $columnValues[$store->getData('_id').'_run_in_separate_process_group'] = $storeConfiguration['run_in_separate_process_group'];
            $columnValues[$store->getData('_id').'_group_id'] = $storeConfiguration['group_id'];

            $optionExtraAttr = [];
            $optionHash = $this->getEnabledColumnRenderer()->calcOptionHash($storeConfiguration['run_in_separate_process_group']);
            $optionExtraAttr['option_'. $optionHash] = 'selected="selected"';

            $store->setData('column_values', $columnValues);
            $store->setData('option_extra_attrs', $optionExtraAttr);
        }

        return $allStores;
    }
}
