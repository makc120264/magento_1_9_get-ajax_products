<?php

class Makc_GetAjaxProducts_IndexController extends Mage_Core_Controller_Front_Action
{

    private function getProductCC($_product, $content)
    {
        $blockProduct = Mage::getModel('Mage_Catalog_Block_Product');
        $content .= '<div class="product_cc">';
        $price = round($_product->getFinalPrice());
        $removePrice = Mage::getStoreConfig('catalog/price_cart_group/price_remove', $storeId);
        if ($price == 0 || $removePrice == 1) {
            $content .= '<div class="call_for_price">' . $this->__('Price on Request') . '</div>';
        } else {
            $content .= $blockProduct->getPriceHtml($_product);
        }
        $content .= '</div>';

        return $content;
    }
    private function getContent($productCollection)
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        $_helper = Mage::helper('catalog/output');
        $imageHelper = Mage::helper('catalog/image');
        $wishlistHelper = Mage::helper('wishlist');
        $currentCategory    = Mage::getModel('catalog/layer')->getCurrentCategory();
        $currentCategoryId  = $currentCategory->getId();

        $_wishlistitemCollection = $wishlistHelper->getWishlistItemCollection();
        $_itemsInWishList = array();
        foreach ($_wishlistitemCollection as $_wishlistitem) {
            $_wishlistproduct   = $_wishlistitem->getProduct();
            $_itemsInWishList[] = $_wishlistproduct->getId();
        }

        $content = '<ul class="products-grid row js-row-height">';

        foreach ($productCollection as $_product) {

            $imgCount = 0;
            $catImg = (string)$imageHelper->init($_product, 'small_image')->resize($imgSize)->setQuality(100);

            if (strpos($catImg, 'placeholder') !== FALSE) {
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $writeConnection = $resource->getConnection('core_write');
                $tableFeed = $resource->getTableName('diamonds_feed');

                $query = 'select * FROM `' . $tableFeed . '` WHERE sku = "' . $_product->getSku() . '" AND status = "skip" AND numimages > 0';
                $readResult = $readConnection->query($query);
                if ($readResult->rowCount() > 0) {
                    $query = 'UPDATE `' . $tableFeed . '` SET status = "force" WHERE sku = "' . $_product->getSku() . '"';
                    $writeConnection->query($query);
                }
            }

            $cert1 = strtolower(trim($_product->getResource()->getAttribute('lab')->getFrontend()->getValue($_product)));
            $cert2 = strtolower(trim($_product->getResource()->getAttribute('report_2')->getFrontend()->getValue($_product)));

            $actionName = Mage::app()->getRequest()->getRouteName();

            if ($actionName != 'catalogsearch') {
                $categoryId = $currentCategory->getEntityId();
            } else {
                $categoryId = '';
            }

            $configValue = Mage::getStoreConfig('catalog/frontend/dropdown', $storeId);
            $categoryArray = explode(",", $configValue);
            $isInCategory = in_array($categoryId, $categoryArray);
            $showcatListing = Mage::getStoreConfig('catalog/frontend/category_yesno', $storeId);

            $AllCategoriesInCatalog = Mage::getModel('catalog/category')->getCollection();
//all colored diamonds categories
            $AllCategoriesInCatalog->addFieldToFilter('path', array('like' => '1/2/4/%'));
            $colorDiamondsCatIds = $AllCategoriesInCatalog->getColumnValues('entity_id');

            $productHasColoredDiamondsCat = FALSE;
            $categorys = $_product->getCategoryIds();

            $name = $_helper->productAttribute($_product, $_product->getName(), 'name');
            $name = explode(',', $name);

            foreach ($categorys as $category) {
                if (in_array($category, $colorDiamondsCatIds)) {
                    $productHasColoredDiamondsCat = TRUE;
                    break;
                }
            }

            if ($productHasColoredDiamondsCat) {
                $first = trim($name[0] . ", " . $name[1]);
                unset($name[0]);
                unset($name[1]);
                $second = implode(',', $name);
            } else {
                $first = trim($name[0]);
                unset($name[0]);
                $second = implode(',', $name);
            }

            $images = $_product->getMediaAttributes();
            $frontLabel = $images['small_image']->getFrontendLabel();

            $content .= '<li class="col-xs-6 col-sm-3 col-md-3 col-lg-3 item quickviewpro-block" data-prod-id="' . $_product->getId() . '" data-prod-pos="' . $_product->getCatIndexPosition() . '">
                            <div class="product-wrapper">
                                <a class="product-url" href="' . $_product->getProductUrl() . '"></a>
                                <div class="product-img">';
            if (in_array(42, $categorys)) {
                $getSkinUrl = Mage::getDesign()->getSkinUrl('images/icons/new-item.png');
                $content .= '<img class = "ribbon" src="' . $getSkinUrl . '" alt="New Item" />';
            }

            $content .= '<div class="product-image-list bbb">
                <ul>
                    <li>
                        <a href="' . $_product->getProductUrl() . '" title="' . strip_tags($frontLabel) . '" class="product-image">
                            <img src="' . $catImg . '" alt="' . strip_tags($frontLabel) . '" />
                        </a>
                    </li>
                </ul>
            </div>';

            if ($imgCount) {
                $content .= '<div class="product-image-nav">
                                <a href="javascript:void(0)" class="product-image-nav-prev">' . $this->__("Prev Image") . '</a>
                                <a href="javascript:void(0)" class="product-image-nav-next">' . $this->__("Next Image") . '</a>
                             </div>';
            }

            if ($cert1 == 'argyle' || $cert2 == 'argyle') {
                $imgUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'wysiwyg/certificates/argyle.png';
                $content .= '<div class="product-list-certificat-icons"><img src="' . $imgUrl . '" alt="Argyle" /></div>';
            }

            $content .= '<ul class="add-to-links">';

            if ($wishlistHelper->isAllow()) {
                if (in_array($_product->getId(), $_itemsInWishList)) {
                    $inWishlistClass = ' in-wishlist';
                } else {
                    $inWishlistClass = '';
                }
                $content .= '<li><a href="' . $wishlistHelper->getAddUrl($_product) . '" 
                                   data-remove="' . Mage::getUrl('local/product/removeWishlistItem', array('product' => $_product->getId())) . '" 
                                   class="link-wishlist' . $inWishlistClass . '">
                                        <span>' . $this->__('Add to Wishlist') . '</span></a></li>';
            }

            $content .= '</ul></div><div class="p_data clearfix">';

            if ($showcatListing) {
                if (!$isInCategory) {
                    $content = $this->getProductCC($_product, $content);
                }
            } else {
                $content = $this->getProductCC($_product, $content);
            }

            $content .= '<h2 class="product-name"><a href="' . $_product->getProductUrl() . '" title="' . strip_tags($_product->getName(), null, true) . '">';

            $content .= $first;
            $content .= '<span class="product-name-info">' . $second . '</span><span class="product-desc-long">';
            if ($currentCategoryId == '153') {
                if ($_product->getDescription()) {
                    $content .= mb_strimwidth($_product->getDescription(), 0, 25, "...", 'utf-8');
                }
            }
            $content .= '</span></a></h2></div><div class="p_more clearfix"><button class="button" onclick="window.location.href="' . $_product->getProductUrl() . '" type="submit"><span><span>' . $this->__("ASK ABOUT THIS DIAMONDS") . '</span></span></button></div></div></li>';
        }

        return $content;
    }

    public function IndexAction()
    {
        $params = $this->_request->getParams();

        $productCollection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('*')->setPageSize($params['gridPerPage'])->setCurPage($params['page']);
        $productCollection->load();

        $content = $this->getContent($productCollection);

        echo $content;
    }
}
