<?php

namespace MyCompany\LayerNavigation\Model\Layer\Filter;

class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Construct
     *
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     */
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\App\RequestInterface $request,

    )
    {
        parent::__construct($url, $htmlPagerBlock);
        $this->request = $request;
    }

    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        if($this->getFilter()->getRequestVar() == "cat"){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $category_url = $objectManager->create('Magento\Catalog\Model\Category')->load($this->getValue())->getUrl();
            $return = $category_url;
            $request = $this->_url->getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true));
            if(strpos($request,'?') !== false ){
                $query_string = substr($request,strpos($request,'?'));
            }
            else{
                $query_string = '';
            }
            if(!empty($query_string)){
                $return .= $query_string;
            }
            return $return;
        }
        else{
            $query = array(
                $this->getFilter()->getRequestVar()=>$this->getValue(),
                $this->_htmlPagerBlock->getPageVarName() => null // exclude current page from urls
            );

            return $this->_url->getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
        }
    }

}
