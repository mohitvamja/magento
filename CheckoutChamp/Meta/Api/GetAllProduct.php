<?php
namespace CheckoutChamp\Meta\Api;
interface GetAllProduct
{
    /**
     * @api
     * @return array
     */
    public function GetProductData(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = NULL);
}