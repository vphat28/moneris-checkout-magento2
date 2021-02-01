<?php
/**
 * Copyright © 2016 CollinsHarper. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Moneris\CreditCard\Model\Transaction;

use Moneris\CreditCard\Model\Transaction;
use Magento\Framework\DataObject;

/**
 * Moneres OnSite Payment Method model.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Completion extends Transaction
{
    protected $_requestType = self::COMPLETION;

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();
        $mcp     = $payment->getAdditionalInformation('mcp_info');

        $order        = $payment->getOrder();
        $currencyCode = $order->getOrderCurrencyCode();
        $custId       = $order->getIncrementId();

        if ( ! $payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId          = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId     = ($receiptId) ? $receiptId : $payment->getLastTransId();

        $request = [
            'type'                     => $this->_requestType,
            'order_id'                 => $monerisOrderId,
            'cust_id'                  => $custId,
            'comp_amount'              => $this->getAmount(),
            'mcp_version'              => Transaction::MCP_VERSION,
            'cardholder_currency_code' => $this->getIso4217Code($currencyCode),
            self::CRYPT_FIELD          => $this->getCryptType(),
            'txn_number'               => $payment->getCcTransId(),
        ];

        if ( ! empty($mcp)) {
            $txnArray = array(
                'type'                     => 'mcp_completion',
                'txn_number'               => $payment->getCcTransId(),
                'order_id'                 => $monerisOrderId,
                'crypt_type'               => '7',
                'mcp_version'              => self::MCP_VERSION,
                'cardholder_amount'        => $this->mcpformatAmount($mcp['cardholder_amount']),
                'cardholder_currency_code' => $this->getIso4217Code($mcp['cardholder_currency_desc']),
            );

            return $txnArray;
        }

        return $request;
    }
}
