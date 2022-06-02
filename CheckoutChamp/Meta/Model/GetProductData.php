<?php
namespace CheckoutChamp\Meta\Model;
use CheckoutChamp\Meta\Api\GetAllProduct;
class GetProductData implements GetAllProduct
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    protected $_stockRegistry;
    /**
     * @param \Magento\Store\Model\App\Emulation              $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface      $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Helper\Image                   $imageHelper
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    ) {
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->_stockRegistry = $stockRegistry;
    }
    public function GetProductData(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = NULL)
    {
        //echo"<pre>";
        //print_r($_SERVER['HTTP_AUTHORIZATION']);
        //print_r($searchCriteria);
        //print_r($searchCriteria->getPageSize());
        //print_r($searchCriteria->getCurrentPage());
        //exit;
        $storeId = $this->storeManager->getStore()->getId();
        $product_data = array();
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $store = $this->storeManager->getStore();
        $headers = ["Content-Type" => "application/json"];
        if(!empty($searchCriteria)){
            if($searchCriteria->getPageSize() != ' ' && $searchCriteria->getCurrentPage() != ' '){
                $url = $store->getBaseUrl().'rest/V1/products?searchCriteria[pageSize]='.$searchCriteria->getPageSize().'&searchCriteria[currentPage]='.$searchCriteria->getCurrentPage();
            }else {
                $url = $store->getBaseUrl().'rest/V1/products?searchCriteria[pageSize]=50&searchCriteria[currentPage]=-1';
            }
        }else {
            $url = $store->getBaseUrl().'rest/V1/products?searchCriteria[pageSize]=50&searchCriteria[currentPage]=-1';
        }
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json','Authorization:'.$_SERVER['HTTP_AUTHORIZATION']));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        
        $i =0;
        if(!empty($response['items'])){
            foreach($response['items'] as $value){                
                if (array_key_exists('media_gallery_entries', $value)){
                    if(!empty($value['media_gallery_entries'])){
                        $j = 0;
                        foreach($value['media_gallery_entries'] as $image_value){ 
                            $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $image_value['file'];
                            $value['media_gallery_entries'][$j]['product_image_url'] = $imageUrl;                            
                            $j++;
                        }
                    }
                }
                if (array_key_exists('extension_attributes', $value)){
                    if(!empty($value['extension_attributes'])){
                        if (array_key_exists('configurable_product_options', $value['extension_attributes'])){
                            if(!empty($value['extension_attributes']['configurable_product_options'])){
                                $m =0;
                                foreach($value['extension_attributes']['configurable_product_options'] as $atribute_value){
                                    
                                    $attribute_id = $atribute_value['attribute_id'];
                                    $url1 = $store->getBaseUrl().'rest/V1/products/attributes/'.$attribute_id.'/options';
                                    $ch1 = curl_init();
                                    curl_setopt ($ch1,CURLOPT_URL,$url1);
                                    curl_setopt ($ch1, CURLOPT_HTTPHEADER, array('Content-type:application/json','Authorization:'.$_SERVER['HTTP_AUTHORIZATION']));
                                    curl_setopt ($ch1, CURLOPT_RETURNTRANSFER, 1);
                                    curl_setopt ($ch1, CURLOPT_CUSTOMREQUEST, "GET"); 
                                    $response1 = curl_exec($ch1);
                                    curl_close($ch1);
                                    $response1 = json_decode($response1, true);
                                    
                                    $attribute_array = array();
                                    foreach($response1 as $response1_values){
                                        if($response1_values['value'] !=''){
                                            
                                            $attribute_array[$response1_values['value']] = $response1_values['label'];
                                        }
                                        
                                    }
                                    
                                    $k =0;
                                    foreach($atribute_value['values'] as $key => $atribute_values){
                                        if (array_key_exists($atribute_values['value_index'], $attribute_array)) {
                                            $value['extension_attributes']['configurable_product_options'][$m]['values'][$k]['lable'] = $attribute_array[$atribute_values['value_index']];
                                        }
                                        $k++;
                                    }
                                    $m++;                                  
                                        
                                }
                            }
                        }
                    }
                }
                if (array_key_exists('sku', $value)){
                    $value['stock_item'][] = $this->getStockItem($value['id'])->getData();
                }
                $product_data['items'][$i] = $value;
                $i++;
                $product_data['currency_code'] = $this->storeManager->getStore()->getBaseCurrencyCodeLatest();        
                $product_data['currency'] = $this->storeManager->getStore()->getCode();
            }
        }else {
            $product_data = "The consumer isn't authorized to access %resources.";
        }
        //echo"<pre>";
        //print_r($product_data);
        //$product_data['currency'] = $this->storeManager->getStore()->getBaseCurrencyCode();
        header('Content-type: text/json');
        echo json_encode($product_data, JSON_PRETTY_PRINT);
        exit;
        //$this->appEmulation->stopEnvironmentEmulation();
    }

    public function getStockItem($productId)
    {
        return $this->_stockRegistry->getStockItem($productId);
    }
}