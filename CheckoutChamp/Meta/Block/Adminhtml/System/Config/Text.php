<?php
namespace CheckoutChamp\Meta\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Text extends Field
{
    /**
     * Get the button Run
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $base_url = $storeManager->getStore()->getBaseUrl();
        return '<input id="checkoutchamp_meta_general_productget_api_url" name="groups[general][fields][productget_api_url][value]" data-ui-id="adminhtml-system-config-form-field-disable-0-text-groups-general-fields-productget-api-url-value" value="'.$base_url.'rest/V1/getproductdata" class="input-text admin__control-text" type="text" readonly="true">';
    }
}
?>