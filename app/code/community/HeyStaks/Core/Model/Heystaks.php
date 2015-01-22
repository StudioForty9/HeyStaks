<?php

/**
 * Class HeyStaks_Core_Model_Heystaks
 */
class HeyStaks_Core_Model_Heystaks extends Mage_Core_Model_Abstract
{
    const ACTION_BROWSE = 'BROWSE';
    const ACTION_SELECT = 'SELECT';
    const ACTION_ADD_TO_CART = 'ADD_TO_CART';
    const ACTION_PURCHASE = 'PURCHASE';

    /**
     * @var array
     */
    protected $_config = array();

    /**
     * @var Mage_Core_Model_Session|null
     */
    protected $_session = null;

    /**
     * @var null
     */
    protected $_token = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_config['endpoint'] = Mage::getStoreConfig('heystaks/general/endpoint');
        $this->_config['applicationId'] = Mage::getStoreConfig('heystaks/authentication/application_id');
        $this->_config['secret'] = Mage::getStoreConfig('heystaks/authentication/application_secret');
        $this->_config['username'] = Mage::getStoreConfig('heystaks/authentication/admin_username');
        $this->_config['password'] = Mage::getStoreConfig('heystaks/authentication/admin_userpassword');

        $this->_session = Mage::getSingleton('core/session');
    }

    /**
     * @return mixed|null
     */
    public function getToken()
    {
        if (!$this->_token) {
            if ($token = Mage::getStoreConfig('heystaks/authentication/token')) {
                $this->_token = $token;
            } else {
                $this->fetchToken();
            }
        }

        return $this->_token;
    }

    /**
     * @return $this
     */
    public function fetchToken()
    {
        $url = Mage::getStoreConfig('heystaks/general/oauth_endpoint');
        $client = $this->_getClient($url);
        $client->setAuth(Mage::getStoreConfig('heystaks/authentication/application_id'));
        $client->setEncType('application/x-www-form-urlencoded');
        $client->setParameterPost('grant_type', 'password');
        $client->setParameterPost('username', Mage::getStoreConfig('heystaks/authentication/admin_username'));
        $client->setParameterPost('password', Mage::getStoreConfig('heystaks/authentication/admin_userpassword'));

        try {
            Mage::helper('heystaks')->log($client, 'request');
            $response = $client->request();
            Mage::helper('heystaks')->log($response, 'response');
            $response = Mage::helper('core')->jsonDecode($response->getBody());
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('heystaks')->log($e->getMessage(), 'error');
            return $this;
        }

        $this->_token = $response['access_token'];

        Mage::app()->getConfig()
            ->saveConfig('heystaks/authentication/token', $this->_token, 'stores', Mage::app()->getStore()->getId())
            ->reinit();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAnonymousUser()
    {
        $cookies = Mage::getSingleton('core/cookie')->get();
        if (!empty($cookies)) {
            if (!$this->_getCookie('heystaks_user_id')) {
                $sessionId = $this->_session->getSessionId();
                $path = '/users/';
                $client = $this->_getClient($this->_config['endpoint'] . $path);

                $params = array(
                    'application_id' => $this->_config['applicationId'],
                    'user_name' => $sessionId,
                    'user_password' => $sessionId,
                    'user_email' => $sessionId . '@heystaks.com'
                );

                $result = $this->_getResponse($client, $params);
                if ($result) {
                    Mage::helper('heystaks')->log('User did not previously exist: ' . $result['user_id'], 'user');
                    $this->_setCookie('heystaks_user_id', $result['user_id']);
                } else {
                    $generatedUserId = $sessionId . '_' . $this->_config['applicationId'];
                    Mage::helper('heystaks')->log('User did previously exist: ' . $generatedUserId, 'user');
                    $this->_setCookie('heystaks_user_id', $generatedUserId);
                }
            }

            return $this->_getCookie('heystaks_user_id');
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getIdentifiedUser($customer)
    {
        if (!$this->_getCookie('heystaks_customer_id')) {
            $existingUser = Mage::getModel('heystaks/user')->load($customer->getId(), 'customer_id');
            if (!$existingUser->getId()) {
                $path = '/users/';
                $client = $this->_getClient($this->_config['endpoint'] . $path);

                $params = array(
                    'application_id' => $this->_config['applicationId'],
                    'user_name' => sha1($customer->getId()),
                    'user_password' => sha1($customer->getId()),
                    'user_email' => $customer->getEmail()
                );

                $result = $this->_getResponse($client, $params);
                $existingUser->setCustomerId($customer->getId())
                    ->setUserId($result['user_id'])
                    ->save();
            }

            $this->_setCookie('heystaks_customer_id', $existingUser->getUserId());
        }

        return $this->_getCookie('heystaks_customer_id');
    }

    /**
     *
     */
    public function sendFeedback($action, $params = array())
    {
        $layout = Mage::app()->getLayout();
        $head = $layout->getBlock('head');
        $pageMetaParams = $head ? $head->getData() : null;

        $params = array_merge(
            $this->_getDefaultFeedbackParams($pageMetaParams),
            $params
        );

        $communities = $items = '~NONE';
        $path = '/communities/' . $communities . '/items/' . $items . '/actions/' . $action;
        $path .= '?user_id=' . $params['user_id'];

        $client = $this->_getClient($this->_config['endpoint'] . $path);

        Mage::dispatchEvent('heystaks_send_feedback_before', array('params' => $params));
        $response = $this->_getResponse($client, $params);
        Mage::dispatchEvent('heystaks_send_feedback_after', array('response' => $response));

        return $response;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function search($query)
    {
        $resultSetSize = Mage::getStoreConfig('heystaks/general/result_set_size');

        $params = array(
            'query' => $query,
            'user_id' => $this->getAnonymousUser(),
            'application_id' => $this->_config['applicationId'],
            'language' => Mage::helper('heystaks')->getStoreLanguage(),
            'size' => $resultSetSize
        );

        Mage::dispatchEvent('heystaks_search_before', array('params' => $params));

        $communities = '~ALL';
        $path = "/communities/$communities/items/?" . http_build_query($params);

        $client = $this->_getClient($this->_config['endpoint'] . $path);
        $client->setMethod('GET');
        $client->setHeaders(
            "Authorization: Bearer " . $this->getToken()
        );
        $client->setEncType('application/json');

        try {
            Mage::helper('heystaks')->log($client, 'request');
            Varien_Profiler::start('heystaks_api_search');
            $response = $client->request();
            Mage::dispatchEvent('heystaks_search_after', array('response' => $response));
            Varien_Profiler::stop('heystaks_api_search');

            Mage::helper('heystaks')->log($response->getBody(), 'response');

            $result = Mage::helper('core')->jsonDecode($response->getBody());
            $this->_setCookie('heystaks_query_id', $result['query_id']);

            if ($result['search_mode_response'] == 'OR') {
                $message = Mage::getStoreConfig('heystaks/frontend/or_message');
                if ($message) {
                    Mage::getSingleton('catalog/session')->addNotice($message);
                }
            }

            return $result['results'];
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('heystaks')->log($e->getMessage(), 'error');
        }
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection|Mage_Eav_Model_Entity_Collection_Abstract|Varien_Data_Collection
     */
    public function getResults()
    {
        $query = Mage::helper('catalogsearch')->getQuery();

        $queryText = $this->_getQueryText($query);
        if ($queryText) {

            $query->save();

            $results = $this->search($queryText);

            //Make the scores available to the template if in test mode
            if (Mage::helper('heystaks')->isTest()) {
                $scores = array();
                foreach ($results as $result) {
                    if ($result['sku']) {
                        $scores[$result['sku']] = $result['score'];
                    }
                }
                Mage::register('heystaks_scores', $scores);
            }

            if (!empty($results)) {
                $skus = array_map(array($this, '_resultCallback'), $results);

                if (!empty($skus)) {
                    Mage::dispatchEvent('heystaks_get_products_before', array('skus' => $skus));
                    $products = $this->_getProductCollection($skus);
                    Mage::dispatchEvent('heystaks_get_products_after', array('products' => $products));
                    Mage::register('heystaks_product_count', $products->count());
                    return $products;
                }
            }
        }

        Mage::helper('heystaks')->log('No Results for:' . $queryText, 'error');
        return false;
    }

    protected function _resultCallback($temp)
    {
        return $temp['sku'];
    }

    /**
     *
     */
    public function selectSearchResult()
    {
        $params['query'] = $this->_getCookie('heystaks_query');
        $params['query_id'] = $this->_getCookie('heystaks_query_id');
        $this->sendFeedback(self::ACTION_SELECT, $params);
    }

    /**
     *
     */
    public function updateHeystaksUser($customer)
    {
        $params = array(
            'application_id' => $this->_config['applicationId'],
            'source_user_id' => $this->getAnonymousUser(),
            'destination_user_id' => $this->getIdentifiedUser($customer)
        );

        $path = '/users/merge?' . http_build_query($params);

        $client = $this->_getClient($this->_config['endpoint'] . $path);
        $client->setMethod('POST');
        $client->setHeaders(
            "Authorization: Bearer " . $this->getToken()
        );
        $client->setEncType('application/json');

        try {
            $response = Mage::helper('core')->jsonDecode($client->request()->getBody());
            Mage::helper('heystaks')->log($response, 'response');
            $this->_setCookie('heystaks_user_id', $response['user_id']);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('heystaks')->log($response, 'error');
        }
    }

    /**
     * @param $products
     */
    public function indexProducts($products)
    {
        $content = array();
        foreach ($products as $product) {
            /* @var $product Mage_Catalog_Model_Product */

            $visible = $this->_getIsProductVisible($product) ? 'true' : 'false';

            $params = array('_ignore_category' => true);
            $url = $product->getUrlModel()->getUrl($product, $params);

            $data = array(
                'uri' => $url,
                'title' => $product->getName(),
                'description' => strip_tags($product->getDescription()),
                'short_description' => strip_tags($product->getShortDescription()),
                'color' => $product->getAttributeText('color'),
                'manufacturer' => $product->getAttributeText('manufacturer'),
                'sku' => $product->getSku(),
                'price' => $product->getFinalPrice(),
                'image' => Mage::getBaseUrl('media') . 'catalog/product' . $product->getSmallImage(),
                'language' => Mage::helper('heystaks')->getStoreLanguage(),
                'visible' => $visible
            );

            Mage::helper('heystaks')->log($data, 'indexing');

            $content[] = $data;
        }

        $this->_indexContent($content);
    }

    /**
     * @param $categories
     */
    public function indexCategories($categories)
    {
        if (Mage::helper('heystaks')->canIncludeInSearch('category')) {
            $content = array();
            foreach ($categories as $category) {
                $content[] = array(
                    'uri' => $category->getUrl(),
                    'title' => $category->getName(),
                    'description' => strip_tags($category->getDescription()),
                    'short_description' => strip_tags($category->getShortDescription()),
                    'image' => $category->getImage(),
                    'language' => Mage::helper('heystaks')->getStoreLanguage()
                );
            }

            $this->_indexContent($content);
        }
    }

    /**
     * @param $pages
     */
    public function indexPages($pages)
    {
        if (Mage::helper('heystaks')->canIncludeInSearch('page')) {
            $content = array();
            foreach ($pages as $page) {
                $content[] = array(
                    'uri' => Mage::getBaseUrl() . $page->getIdentifier(),
                    'title' => $page->getTitle(),
                    'description' => strip_tags($page->getContent()),
                    'short_description' => strip_tags($page->getContentHeading()),
                    'language' => Mage::helper('heystaks')->getStoreLanguage()
                );
            }

            $this->_indexContent($content);
        }
    }

    /**
     *
     */
    public function deleteProduct($product)
    {
        $params = array(
            'application_id' => $this->_config['applicationId'],
        );

        $path = '/communities/~ALL/items/sku/' . urlencode($product->getData('sku')) . '?' . http_build_query($params);

        $client = $this->_getClient($this->_config['endpoint'] . $path);
        $client->setMethod('DELETE');
        $client->setHeaders(
            "Authorization: Bearer " . $this->getToken()
        );
        $client->setHeaders(
            "Content-Type: application/json"
        );
        $client->setEncType('application/json');

        try {
            Mage::helper('heystaks')->log($client, 'request');
            $response = $client->request();
            Mage::helper('heystaks')->log($response, 'response');
            $response = Mage::helper('core')->jsonDecode($response->getBody());
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('heystaks')->log($response, 'error');
        }
    }

    /**
     * @param $content
     */
    protected function _indexContent($content)
    {
        $auth = array(
            'application_id' => $this->_config['applicationId'],
            'user_id' => $this->_config['username'] . '_' . $this->_config['applicationId']
        );

        $communities = '~NONE';
        $path = '/communities/' . $communities . '/items';
        $url = $this->_config['endpoint'] . $path . '?' . http_build_query($auth);
        $client = $this->_getClient($url);
        $this->_getResponse($client, $content);
    }

    /**
     * @param $data
     * @return array
     */
    protected function _getDefaultFeedbackParams($data)
    {
        if($product = Mage::registry('product')){
            $params = array('_ignore_category' => true);
            $uri = $product->getUrlModel()->getUrl($product, $params);
        }else{
            $uri = '';
        }

        if ($uri) {
            $uriArray = explode('?', $uri);
            $uri = $uriArray[0];
        }

        $session = Mage::getSingleton('customer/session');
        if($session->isLoggedIn()){
            $userId = $this->getIdentifiedUser($session->getCustomer());
        }else{
            $userId = $this->getAnonymousUser();
        }

        $params = array(
            'user_id' => $userId,
            'uri' => $uri,
            'application_id' => $this->_config['applicationId'],
            'language' => Mage::helper('heystaks')->getStoreLanguage()
        );

        return $params;
    }

    /**
     * @param $url
     * @return Varien_Http_Client
     */
    protected function _getClient($url)
    {
        $client = new Zend_Http_Client($url, array('adapter' => 'Zend_Http_Client_Adapter_Curl', 'timeout' => 5));
        $client->setMethod('POST');
        return $client;
    }

    /**
     * @param $client Varien_Http_Client
     * @param $data
     */
    protected function _getResponse($client, $data, $retry = false)
    {
        try {
            /* @var $helper Mage_Core_Helper_Data */
            $helper = Mage::helper('core');
            $json = $helper->jsonEncode($data);
            Mage::helper('heystaks')->log($client->getUri(), 'request');
            Mage::helper('heystaks')->log($json, 'request');
            $client->setRawData($json);
            $client->setHeaders(
                "Authorization: Bearer " . $this->getToken()
            );
            Mage::helper('heystaks')->log('Token: ' . $this->getToken(), 'request');
            $client->setEncType('application/json');
            $response = $client->request();

            if($response->getStatus() === 401 && !$retry){
                $this->fetchToken();
                $this->_getResponse($client, $data, true);
            }

            Mage::helper('heystaks')->log($response->getStatus(), 'response');
            Mage::helper('heystaks')->log($response->getBody(), 'response');

            return $helper->jsonDecode($response->getBody());
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('heystaks')->log($client, 'error');
            Mage::helper('heystaks')->log($response, 'error');
        }
    }

    /**
     * @param $query Mage_CatalogSearch_Model_Query
     * @return mixed
     */
    protected function _getQueryText($query)
    {
        $session = Mage::getSingleton('core/session');

        if ($query->getId()) {
            $query->setPopularity($query->getPopularity() + 1);
        } else {
            $query->setPopularity(1);
        }

        if (Mage::getStoreConfig('heystaks/general/use_redirects')) {
            if ($redirect = $query->getRedirect()) {
                $query->save();
                Mage::app()->getResponse()->setRedirect($redirect);
                Mage::app()->getResponse()->sendResponse();
                return;
            } else if ($synonym = $query->getSynonymFor()) {
                $queryText = $synonym;
            } else {
                $queryText = $query->getQueryText();
            }
        } else {
            $queryText = $query->getQueryText();
        }

        $this->_setCookie('heystaks_query', $queryText);
        return $queryText;
    }

    /**
     * @param $skus
     * @return Mage_Catalog_Model_Resource_Product_Collection|Varien_Data_Collection
     */
    protected function _getProductCollection($skus)
    {
        Varien_Profiler::start('heystaks_product_collection');

        //Attributes to Include
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
        $visibleAttributes = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getUsedInProductListing()) {
                $visibleAttributes[] = $attribute->getAttributeCode();
            }
        }

        //Basic Product Collection
        /* @var $products = Mage_Catalog_Model_Resource_Product_Collection */
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect($visibleAttributes)
            ->addPriceData()
            ->addStoreFilter();

        //Filter by SKUs returned from Search
        if (count($skus) > 0) {
            $ids = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('sku', array('in', $skus))
                ->getAllIds();

            $products->addFieldToFilter('entity_id', array('in' => $ids));
        }

        $products = $this->_filter($products);
        $products = $this->_sort($products, $skus);
        $products = $this->_paginate($products);
        Varien_Profiler::stop('heystaks_product_collection');
        return $products;
    }

    protected function _filter($products)
    {
        /* @var $products = Mage_Catalog_Model_Resource_Product_Collection */
        //Remove 'Out of Stock' Products if configured to do so
        if (!Mage::getStoreConfig('heystaks/frontend/include_out_of_stock')) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($products);
        }

        //Remove invisible products
        $products->addAttributeToFilter('visibility', array('nin' => Mage::getModel('catalog/product_visibility')->getVisibleInSearchIds()));

        //Filter by Layered Navigation
        $request = Mage::app()->getRequest();
        $params = $request->getParams();
        $ignore = array('q', 'dir', 'order', 'mode', 'p', 'filter', 'limit', 'cat', 'search_type');
        foreach ($params as $key => $value) {
            if ($key == 'cat') {
                $products->addCategoryFilter(
                    Mage::getModel('catalog/category')->load($value)
                );
            } elseif ($key == 'price') {
                $prices = explode('-', $value);
                $i = 0;
                foreach ($prices as $price) {
                    if ($price == '') {
                        $price = $i == 0 ? 0 : 10000000;
                    }
                    $comp = $i == 0 ? '>=' : '<=';

                    $products->getSelect()->where(
                        'final_price' . $comp . $price
                    );

                    $i++;
                }
            } elseif (!in_array($key, $ignore)) {
                $products->addFieldToFilter($key, $value);
            }
        }

        return $products;
    }

    protected function _paginate($products)
    {
        $request = Mage::app()->getRequest();
        $limit = $request->getParam('limit');
        $p = $request->getParam('p');
        if ($limit || $p) {
            if ($limit) {
                if ($limit == 'all') {
                    $products->setPageSize($products->getSize());
                } else {
                    $products->setPageSize($limit);
                }
            } else {
                $products->setPageSize($this->_getDefaultPageSize());
            }

            if ($p) {
                $products->setCurPage($p);
            } else {
                $products->setCurPage(1);
            }
        } else {
            $products->setPageSize($this->_getDefaultPageSize())
                ->setCurPage(1);
        }

        return $products;
    }

    protected function _sort($products, $skus)
    {
        $request = Mage::app()->getRequest();
        $order = $request->getParam('order');
        $dir = $request->getParam('dir');
        if (!$order || $order == 'relevance') {
            $keys = $dir == 'asc' ? array_reverse($skus) : $skus;
            $products->getSelect()->order(
                new Zend_Db_Expr('FIELD(sku, "' . implode('", "', $keys) . '")')
            );
        } else {
            if ($order) {
                $products->setOrder($order, $dir);
            }
        }

        return $products;
    }

    protected function _getDefaultPageSize()
    {
        $mode = Mage::getStoreConfig('catalog/frontend/list_mode');
        if (substr($mode, 0, 4) == 'grid') {
            $pageSize = Mage::getStoreConfig('catalog/frontend/grid_per_page');
        } else {
            $pageSize = Mage::getStoreConfig('catalog/frontend/list_per_page');
        }

        return $pageSize;
    }

    /**
     * @param $product Mage_Catalog_Model_Product
     * @return bool
     */
    protected function _getIsProductVisible($product)
    {
        if (!$product->getStatus()) {
            return false;
        }

        if (in_array($product->getVisibility(), Mage::getModel('catalog/product_visibility')->getVisibleInSearchIds())) {
            return false;
        }

        if (!Mage::getStoreConfig('heystaks/frontend/include_out_of_stock')) {
            if (!$product->isSalable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $type
     * @return mixed
     */
    protected function _getCookie($type){
        return Mage::getSingleton('core/cookie')->get($type);
    }

    /**
     * @param $type
     * @param $value
     * @return mixed
     */
    protected function _setCookie($type, $value){
        $lifetime = (int) Mage::getStoreConfig('heystaks/cookie/ttl');
        return Mage::getSingleton('core/cookie')->set($type, $value, $lifetime, '/');
    }
}