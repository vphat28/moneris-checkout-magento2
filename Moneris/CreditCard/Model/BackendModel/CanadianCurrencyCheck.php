<?php

namespace Moneris\CreditCard\Model\BackendModel;

use Magento\Framework\App\ObjectManager;

class CanadianCurrencyCheck extends \Magento\Framework\App\Config\Value
{

    /**
     * Processing object after save data
     *
     * @return $this
     */
    public function afterSave()
    {
        $value = $this->getValue();

        if (empty($value)) {
            return parent::afterSave();
        }

        $scope = $this->getScope();
        $currency = $this->_config->getValue('currency/options/base', $scope);

        if ($currency != 'CAD') {
            $objectManager = ObjectManager::getInstance();
            $messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
            $messageManager->addWarning(__('Moneris Checkout: our currency options are not Canadian Dollar.  Please be aware that MCO and its supporting Multi-Currency Options require Canadian Dollar as the currency and using other currencies will result in unexpected charge amounts.  Please change your currency to Canadian Dollars.'));
        }

        return parent::afterSave();
    }
}
