<?php

class Makc_GetAjaxProducts_IndexController extends Mage_Core_Controller_Front_Action
{

    public function IndexAction()
    {
        $params = $this->_request->getParams();

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('*')
            ->setPageSize($params['gridPerPage'])
            ->setCurPage($params['page']);
        $productCollection->load();

        $items = $productCollection->getItems();

        echo json_encode($items);
    }
}