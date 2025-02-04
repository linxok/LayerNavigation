<?php
namespace MyCompany\LayerNavigation\Model\Plugin;

use function Aws\filter;

class FilterList
{
    const STOCK_FILTER_CLASS  = 'MyCompany\LayerNavigation\Model\Layer\Filter\Stock';

    protected $_objectManager;
    protected $_layer;
    protected $_storeManager;
    protected $_stockResource;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Status $stockResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->_stockResource = $stockResource;
        $this->_scopeConfig = $scopeConfig;
    }

    public function isEnabled()
    {
        $outOfStockEnabled = $this->_scopeConfig->isSetFlag(
            \Magento\CatalogInventory\Model\Configuration::XML_PATH_DISPLAY_PRODUCT_STOCK_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $outOfStockEnabled;
    }

    public function beforeGetFilters(
        \Magento\Catalog\Model\Layer\FilterList\Interceptor $filterList,
        \Magento\Catalog\Model\Layer $layer
    ) {
        $this->_layer = $layer;
        if ($this->isEnabled()) {
            $collection = $layer->getProductCollection();
            $websiteId = $this->_storeManager->getStore($collection->getStoreId())->getWebsiteId();
            $this->_addStockStatusToSelect($collection->getSelect(), $websiteId);
        }
        return array($layer);
    }

    public function afterGetFilters(
        \Magento\Catalog\Model\Layer\FilterList\Interceptor $filterList,
        array $filters
    ) {
        $new_filter = [];
        if ($this->isEnabled()) {
            $new_filter[] = array_shift($filters);
            $new_filter[] = $this->getStockFilter();
            foreach ($filters as $filter) {
                $new_filter[] = $filter;
            }

        }

        return $new_filter;
    }

    public function getStockFilter()
    {
        $filter = $this->_objectManager->create(
            $this->getStockFilterClass(),
            ['layer' => $this->_layer]
        );
        return $filter;
    }

    public function getStockFilterClass()
    {
        return self::STOCK_FILTER_CLASS;
    }

    protected function _addStockStatusToSelect(\Zend_Db_Select $select, $websiteId)
    {
        $from = $select->getPart(\Zend_Db_Select::FROM);
        if (!isset($from['stock_status_index'])) {
            $joinCondition = $this->_stockResource->getConnection()->quoteInto(
                'e.entity_id = stock_status_index.product_id' . ' AND stock_status_index.website_id = ?',
                $websiteId
            );

            $joinCondition .= $this->_stockResource->getConnection()->quoteInto(
                ' AND stock_status_index.stock_id = ?',
                \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID
            );
        }
        return $this;
    }

}
