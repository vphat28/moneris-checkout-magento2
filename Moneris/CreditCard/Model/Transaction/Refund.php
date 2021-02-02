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
class Refund extends Transaction
{
    protected $_requestType = self::REFUND;

    protected function calculateForeignAmount($amount, $foreign, $refundAmount)
    {
        $mul = bcmul($refundAmount, $foreign, 2);

        return bcdiv($mul, $amount, 2);
    }

    public function buildTransactionArray()
    {
        $payment = $this->getPayment();
        $order = $payment->getOrder();
        $total = $order->getGrandTotal();
        $currencyCode = $order->getOrderCurrencyCode();
        $custId = $order->getIncrementId();
        $mcp = $payment->getAdditionalInformation('mcp_info');

        if (!$payment) {
            return [];
        }

        $this->_requestType = $this->getUpdatedRequestType();
        $receiptId = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'receipt_id');
        $monerisOrderId = ($receiptId)? $receiptId : $payment->getLastTransId();
        
        $requestType = $this->_requestType;
        $paymentType = $this->getHelper()->getPaymentAdditionalInfo($this->payment, 'trans_name');
        if ($paymentType && strpos($paymentType, 'idebit_purchase') !== false) {
            $requestType = "idebit_refund";
        }

        if (!empty($mcp)) {
            $txnArray=array('type'=>'mcp_refund',
                            'txn_number'=>$payment->getCcTransId(),
                            'order_id'=>$monerisOrderId,
                            'crypt_type'=> '7',
                            'cust_id'=> $custId,
                            'mcp_version'=> self::MCP_VERSION,
                            'cardholder_amount'        => $this->mcpformatAmount($this->calculateForeignAmount($total, $mcp['cardholder_amount'], $this->amount)),
                            'cardholder_currency_code' => $this->getIso4217Code($mcp['cardholder_currency_desc']),
            );

            return $txnArray;
        }

        return [
            'type'          => $requestType,
            'order_id'      => $monerisOrderId,
            'cust_id'       => $custId,
            'mcp_version'   => Transaction::MCP_VERSION,
            'cardholder_amount' => $this->getAmount(),
            'cardholder_currency_code' => $this->getIso4217Code($currencyCode),
            'amount'        => $this->getAmount(),
            'crypt_type'    => $this->getCryptType() ? $this->getCryptType() : self::CRYPT_FIVE,
            'txn_number'    => $payment->getCcTransId()
        ];
    }
}
