<?php

namespace Moneris\MonerisCheckout\Block\Payment;

use Magento\Framework\View\Element\Template;
use Moneris\MonerisCheckout\Helper\Data;

class Info extends \Magento\Payment\Block\Info
{
    /** @var Data */
    protected $helper;

    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getSpecificInformation() {
        $info = $this->getInfo();
        $specs = [];
        $cards = $this->helper->getCardTypes();

        foreach ([
            'moneris_checkout_transaction_date_time' => (string)__('Transaction Date'),
            'moneris_checkout_transaction_no' => (string)__('Transaction Number'),
            'moneris_checkout_order_no' => (string)__('Reference Number'),
            'moneris_checkout_card_type' => (string)__('Card Type'),
            ] as $key => $value) {
            if (!empty($info->getAdditionalInformation($key))) {
                $specs[$value] = $info->getAdditionalInformation($key);

                if ($key === 'moneris_checkout_card_type') {

                    if (key_exists($info->getAdditionalInformation($key), $cards)) {
                        $specs[$value] = $cards[$info->getAdditionalInformation($key)];
                    }
                }
            }
        }


        return $specs;
    }

    public function getMethod() {
        return parent::getMethod(); // TODO: Change the autogenerated stub
    }
}
