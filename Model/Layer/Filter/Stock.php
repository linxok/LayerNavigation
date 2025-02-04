<?php
namespace MyCompany\LayerNavigation\Model\Layer\Filter;

class Stock extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    const IN_STOCK_COLLECTION_FLAG = 'stock_filter_applied';

    protected $_activeFilter = false;
    protected $_requestVar = 'in-stock';
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        array $data = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $data);
    }

    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $filter = $request->getParam($this->getRequestVar(), null);
        if (is_null($filter)) {
            return $this;
        }
        $this->_activeFilter = true;
        $filter = (int)(bool)$filter;
        $collection = $this->getLayer()->getProductCollection();
        $collection->setFlag(self::IN_STOCK_COLLECTION_FLAG, true);
        $collection->getSelect()->where('stock_status_index.stock_status = ?', $filter);
        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->getLabel($filter), $filter)
        );
        return $this;
    }

    public function getName()
    {
        return __("Stock");
    }

    protected function _getItemsData()
    {
        if ($this->getLayer()->getProductCollection()->getFlag(self::IN_STOCK_COLLECTION_FLAG)) {
            return [];
        }

        $data = [];
        foreach ($this->getStatuses() as $status) {
            $data[] = [
                'label' => $this->getLabel($status),
                'value' => $status,
                'count' => $this->getProductsCount($status)
            ];
        }

        return $data;
    }

    public function getStatuses()
    {
        return [
            \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK,
            \Magento\CatalogInventory\Model\Stock::STOCK_OUT_OF_STOCK
        ];
    }

    public function getLabels()
    {
        return [
            \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK => __('In Stock'),
            \Magento\CatalogInventory\Model\Stock::STOCK_OUT_OF_STOCK => __('Out of stock'),
        ];
    }

    public function getLabel($value)
    {
        $labels = $this->getLabels();
        if (isset($labels[$value])) {
            return $labels[$value];
        }
        return '';
    }

    public function getProductsCount($value)
    {
        $collection = $this->getLayer()->getProductCollection();
        $select = clone $collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->reset(\Zend_Db_Select::ORDER);
        $select->reset(\Zend_Db_Select::LIMIT_COUNT);
        $select->reset(\Zend_Db_Select::LIMIT_OFFSET);
        $select->where('stock_status_index.stock_status = ?', $value);
        $select->columns(
            [
                'count' => new \Zend_Db_Expr("COUNT(e.entity_id)")
            ]
        );
        return $collection->getConnection()->fetchOne($select);
    }
}
