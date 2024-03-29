<?php
/**
 * Copyright © 2016 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moneris\CreditCard\Helper;

use Moneris\CreditCard\Model\ObjectFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Moneris\CreditCard\Model\Transaction;

/**
 * Measure Unit helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    const MONERIS_TEST_HOST = 'esqa.moneris.com';
    const MONERIS_HOST = 'www3.moneris.com';
    const MODULE_CODE = \Moneris\CreditCard\Model\Method\Payment::METHOD_CODE;
    const CONFIG_PATH = 'payment/';
    
    const XML_PATH_US_API = 'usapi';
    const XML_PATH_VBV_ENABLED = 'vbv_enabled';
    const XML_PATH_VBV_REQUIRED = 'require_vbv';
    
    const AVS_MATCH = 'avs';
    const CVD_MATCH = 'cvd';
    const SETTING_GROUP = 'moneris';
    
    private $cvdFirst = [
        '0' => 'CVD value is deliberately bypassed or is not provided by the merchant.',
        '1' => 'CVD value is present.',
        '2' => 'CVD value is on the card, but is illegible.',
        '9' => 'Cardholder states that the card has no CVD imprint.'
    ];

    private $cvdSecond = [
        'M' => 'Match',
        'N' => 'No Match',
        'P' => 'Not Processed',
        'S' => 'CVD should be on the card, but Merchant has indicated that CVD is not present',
        'U' => 'Issuer is not a CVD participant',
        'Other' => 'Invalid Response Code'
    ];

    private $avsResponse = [
        'A' => 'Address matches, ZIP does not.  Acquirer rights not implied.',
        'B' => 'Street addresses match.  Postal code not verified due to incompatible formats.  (Acquirer sent both street address and postal code.)',
        'C' => 'Street addresses not verified due to incompatible formats.  (Acquirer sent both street address and postal code.)',
        'D' => 'Street addresses and postal codes match.',
        'F' => 'Street address and postal code match.  Applies to U.K. only',
        'G' => 'Address information not verified for international transaction. Issuer is not an AVS participant, or AVS data was present in the request but issuer did not return an AVS result, or Visa performs AVS on behalf of the issuer and there was no address record on file for this account.',
        'I' => 'Address information not verified.',
        'K' => 'N/A',
        'L' => 'N/A',
        'M' => 'Street address and postal code match.',
        'N' => 'No match.  Acquirer sent postal/ZIP code only, or street address only, or both postal code and street address.  Also used when acquirer requests AVS but sends no AVS data. Neither address nor postal code matches.',
        'O' => 'N/A',
        'P' => 'Postal code match.  Acquirer sent both postal code and street address but street address not verified due to incompatible formats.',
        'R' => 'Retry: system unavailable or timed out.  Issuer ordinarily performs AVS but was unavailable.  The code R is used by Visa when issuers are unavailable.  Issuers should refrain from using this code. Retry; system unable to process.',
        'S' => 'N/A - AVS currently not supported.',
        'U' => 'Address not verified for domestic transaction.  Issuer is not an AVS participant, or AVS data was present in the request but issuer did not return an AVS result, or Visa performs AVS on behalf of the issuer and there was no address record on file for this account.',
        'W' => 'Not applicable. If present, replaced with Z by Visa. Available for U.S. issuers only.',
        'X' => 'N/A',
        'Y' => 'Street address and postal code match. For U.S. addresses, five-digit postal code and address matches.',
        'Z' => 'Postal/Zip matches; street address does not match or street address not included in request.',
    ];
    
    public $hostedResponse = [
        '475' => 'CREDIT CARD - Invalid expiration date',
        '476' => 'CREDIT CARD - Invalid transaction, rejected',
        '477' => 'CREDIT CARD - Refer Call',
        '478' => 'CREDIT CARD - Decline, Pick up card, Call',
        '479' => 'CREDIT CARD - Decline, Pick up card',
        '480' => 'CREDIT CARD - Decline, Pick up card',
        '481' => 'CREDIT CARD - Decline',
        '482' => 'CREDIT CARD - Expired Card',
        '483' => 'CREDIT CARD - Refer',
        '484' => 'CREDIT CARD - Expired card - refer',
        '485' => 'CREDIT CARD - Not authorized',
        '486' => 'CREDIT CARD - CVV Cryptographic error',
        '489' => 'CREDIT CARD - Invalid CVV',
        '490' => 'CREDIT CARD - Invalid CVV',
        '050' => 'Decline',
        '051' => 'Expired Card',
        '052' => 'PIN retries exceeded',
        '053' => 'No sharing',
        '054' => 'No security module',
        '055' => 'Invalid transaction',
        '056' => 'No Support',
        '057' => 'Lost or stolen card',
        '058' => 'Invalid status',
        '059' => 'Restricted Card',
        '060' => 'No Chequing account',
        '061' => 'No PBF',
        '062' => 'PBF update error',
        '063' => 'Invalid authorization type',
        '064' => 'Bad Track 2',
        '065' => 'Adjustment not allowed',
        '066' => 'Invalid credit card advance increment',
        '067' => 'Invalid transaction date',
        '068' => 'PTLF error',
        '069' => 'Bad message error',
        '070' => 'No IDF',
        '071' => 'Invalid route authorization',
        '072' => 'Card on National NEG file',
        '073' => 'Invalid route service (destination)',
        '074' => 'Unable to authorize',
        '075' => 'Invalid PAN length',
        '076' => 'Low funds',
        '077' => 'Pre-auth full',
        '078' => 'Duplicate transaction',
        '079' => 'Maximum online refund reached',
        '080' => 'Maximum offline refund reached',
        '081' => 'Maximum credit per refund reached',
        '082' => 'Number of times used exceeded',
        '083' => 'Maximum refund credit reached',
        '084' => 'Duplicate transaction - authorization number has already been corrected by host.',
        '085' => 'Inquiry not allowed',
        '086' => 'Over floor limit',
        '087' => 'Maximum number of refund credit by retailer',
        '088' => 'Place call',
        '089' => 'CAF status inactive or closed',
        '090' => 'Referral file full',
        '091' => 'NEG file problem',
        '092' => 'Advance less than minimum',
        '093' => 'Delinquent',
        '094' => 'Over table limit',
        '095' => 'Amount over maximum',
        '096' => 'PIN required',
        '097' => 'Mod 10 check failure',
        '098' => 'Force Post',
        '099' => 'Bad PBF',
        '100' => 'Unable to process transaction',
        '101' => 'Place call',
        '102' => 'Place call',
        '103' => 'NEG file problem',
        '104' => 'CAF problem',
        '105' => 'Card not supported',
        '106' => 'Amount over maximum',
        '107' => 'Over daily limit',
        '108' => 'CAF Problem',
        '109' => 'Advance less than minimum',
        '110' => 'Number of times used exceeded',
        '111' => 'Delinquent',
        '112' => 'Over table limit',
        '113' => 'Timeout',
        '115' => 'PTLF error',
        '121' => 'Administration file problem',
        '122' => 'Unable to validate PIN: security module down',
        '150' => 'Merchant not on file',
        '200' => 'Invalid account',
        '201' => 'Incorrect PIN',
        '202' => 'Advance less than minimum',
        '203' => 'Administrative card needed',
        '204' => 'Amount over maximum',
        '205' => 'Invalid Advance amount',
        '206' => 'CAF not found',
        '207' => 'Invalid transaction date',
        '208' => 'Invalid expiration date',
        '209' => 'Invalid transaction code',
        '210' => 'PIN key sync error',
        '212' => 'Destination not available',
        '251' => 'Error on cash amount',
        '252' => 'Debit not supported',
        '800' => 'Bad format',
        '801' => 'Bad data',
        '802' => 'Invalid Clerk ID',
        '809' => 'Bad close',
        '810' => 'System timeout',
        '811' => 'System error',
        '821' => 'Bad response length',
        '877' => 'Invalid PIN block',
        '878' => 'PIN length error',
        '880' => 'Final packet of a multi-packet transaction',
        '881' => 'Intermediate packet of a multi-packet transaction',
        '889' => 'MAC key sync error',
        '898' => 'Bad MAC value',
        '899' => 'Bad sequence number - resend transaction',
        '900' => 'Capture - PIN Tries Exceeded',
        '901' => 'Capture - Expired Card',
        '902' => 'Capture - NEG Capture',
        '903' => 'Capture - CAF Status 3',
        '904' => 'Capture - Advance < Minimum',
        '905' => 'Capture - Num Times Used',
        '906' => 'Capture - Delinquent',
        '907' => 'Capture - Over Limit Table',
        '908' => 'Capture - Amount Over Maximum',
        '909' => 'Capture - Capture',
        '960' => 'Initialization failure - merchant number mismatch',
        '961' => 'Initialization failure -pinpad mismatch',
        '963' => 'No match on Poll code',
        '964' => 'No match on Concentrator ID',
        '965' => 'Invalid software version number',
        '966' => 'Duplicate terminal name',
        '970' => 'Terminal/Clerk table full',
    ];

    /** @var \Magento\Framework\Encryption\EncryptorInterface */
    private $encryptor;

    /**
     * @var ObjectFactory
     */
    protected $objectFactory;

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Moneris\CreditCard\Logger\Logger $logger, \Moneris\CreditCard\Model\ObjectFactory $objectFactory, \Magento\Framework\Registry $registry, \Magento\Framework\App\CacheInterface $cacheManager,
                                \Magento\Framework\Encryption\EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
        parent::__construct($context, $logger, $objectFactory, $registry, $cacheManager);
    }

    public function getFormattedExpiry($month, $year)
    {
        return substr(sprintf('%04d', $year), -2) .
            sprintf('%02d', $month);
    }

    public function getCardTitle($cardType)
    {
        switch ($cardType) {
            case 'AX':
                return 'Amex';
            break;
            case 'V':
                return 'Visa';
            break;
            case 'M':
                return 'MasterCard';
            break;
        }

        return $cardType;
    }

    public function getIso4217Code($code)
    {
        $code = strtoupper($code);
        $isoCodes = array(
            'AFA' => array('Afghan Afghani', '971'),
            'AWG' => array('Aruban Florin', '533'),
            'AUD' => array('Australian Dollars', '036'),
            'ARS' => array('Argentine Pes', '032'),
            'AZN' => array('Azerbaijanian Manat', '944'),
            'BSD' => array('Bahamian Dollar', '044'),
            'BDT' => array('Bangladeshi Taka', '050'),
            'BBD' => array('Barbados Dollar', '052'),
            'BYR' => array('Belarussian Rouble', '974'),
            'BOB' => array('Bolivian Boliviano', '068'),
            'BRL' => array('Brazilian Real', '986'),
            'GBP' => array('British Pounds Sterling', '826'),
            'BGN' => array('Bulgarian Lev', '975'),
            'KHR' => array('Cambodia Riel', '116'),
            'CAD' => array('Canadian Dollars', '124'),
            'KYD' => array('Cayman Islands Dollar', '136'),
            'CLP' => array('Chilean Peso', '152'),
            'CNY' => array('Chinese Renminbi Yuan', '156'),
            'COP' => array('Colombian Peso', '170'),
            'CRC' => array('Costa Rican Colon', '188'),
            'HRK' => array('Croatia Kuna', '191'),
            'CPY' => array('Cypriot Pounds', '196'),
            'CZK' => array('Czech Koruna', '203'),
            'DKK' => array('Danish Krone', '208'),
            'DOP' => array('Dominican Republic Peso', '214'),
            'XCD' => array('East Caribbean Dollar', '951'),
            'EGP' => array('Egyptian Pound', '818'),
            'ERN' => array('Eritrean Nakfa', '232'),
            'EEK' => array('Estonia Kroon', '233'),
            'EUR' => array('Euro', '978'),
            'GEL' => array('Georgian Lari', '981'),
            'GHC' => array('Ghana Cedi', '288'),
            'GIP' => array('Gibraltar Pound', '292'),
            'GTQ' => array('Guatemala Quetzal', '320'),
            'HNL' => array('Honduras Lempira', '340'),
            'HKD' => array('Hong Kong Dollars', '344'),
            'HUF' => array('Hungary Forint', '348'),
            'ISK' => array('Icelandic Krona', '352'),
            'INR' => array('Indian Rupee', '356'),
            'IDR' => array('Indonesia Rupiah', '360'),
            'ILS' => array('Israel Shekel', '376'),
            'JMD' => array('Jamaican Dollar', '388'),
            'JPY' => array('Japanese yen', '392'),
            'KZT' => array('Kazakhstan Tenge', '368'),
            'KES' => array('Kenyan Shilling', '404'),
            'KWD' => array('Kuwaiti Dinar', '414'),
            'LVL' => array('Latvia Lat', '428'),
            'LBP' => array('Lebanese Pound', '422'),
            'LTL' => array('Lithuania Litas', '440'),
            'MOP' => array('Macau Pataca', '446'),
            'MKD' => array('Macedonian Denar', '807'),
            'MGA' => array('Malagascy Ariary', '969'),
            'MYR' => array('Malaysian Ringgit', '458'),
            'MTL' => array('Maltese Lira', '470'),
            'BAM' => array('Marka', '977'),
            'MUR' => array('Mauritius Rupee', '480'),
            'MXN' => array('Mexican Pesos', '484'),
            'MZM' => array('Mozambique Metical', '508'),
            'NPR' => array('Nepalese Rupee', '524'),
            'ANG' => array('Netherlands Antilles Guilder', '532'),
            'TWD' => array('New Taiwanese Dollars', '901'),
            'NZD' => array('New Zealand Dollars', '554'),
            'NIO' => array('Nicaragua Cordoba', '558'),
            'NGN' => array('Nigeria Naira', '566'),
            'KPW' => array('North Korean Won', '408'),
            'NOK' => array('Norwegian Krone', '578'),
            'OMR' => array('Omani Riyal', '512'),
            'PKR' => array('Pakistani Rupee', '586'),
            'PYG' => array('Paraguay Guarani', '600'),
            'PEN' => array('Peru New Sol', '604'),
            'PHP' => array('Philippine Pesos', '608'),
            'QAR' => array('Qatari Riyal', '634'),
            'RON' => array('Romanian New Leu', '946'),
            'RUB' => array('Russian Federation Ruble', '643'),
            'SAR' => array('Saudi Riyal', '682'),
            'CSD' => array('Serbian Dinar', '891'),
            'SCR' => array('Seychelles Rupee', '690'),
            'SGD' => array('Singapore Dollars', '702'),
            'SKK' => array('Slovak Koruna', '703'),
            'SIT' => array('Slovenia Tolar', '705'),
            'ZAR' => array('South African Rand', '710'),
            'KRW' => array('South Korean Won', '410'),
            'LKR' => array('Sri Lankan Rupee', '144'),
            'SRD' => array('Surinam Dollar', '968'),
            'SEK' => array('Swedish Krona', '752'),
            'CHF' => array('Swiss Francs', '756'),
            'TZS' => array('Tanzanian Shilling', '834'),
            'THB' => array('Thai Baht', '764'),
            'TTD' => array('Trinidad and Tobago Dollar', '780'),
            'TRY' => array('Turkish New Lira', '949'),
            'AED' => array('UAE Dirham', '784'),
            'USD' => array('US Dollars', '840'),
            'UGX' => array('Ugandian Shilling', '800'),
            'UAH' => array('Ukraine Hryvna', '980'),
            'UYU' => array('Uruguayan Peso', '858'),
            'UZS' => array('Uzbekistani Som', '860'),
            'VEB' => array('Venezuela Bolivar', '862'),
            'VND' => array('Vietnam Dong', '704'),
            'AMK' => array('Zambian Kwacha', '894'),
            'ZWD' => array('Zimbabwe Dollar', '716'),
        );

        if (isset($isoCodes[$code])) {
            return $isoCodes[$code][1];
        }

        return false;
    }

    /**
     * @param string $term
     * @return float|int
     */
    public function convertTermToTime($term)
    {
        switch ($term) {
            case 'yearly':
                return 60 * 60 * 24 * 30 * 365;
            case 'daily':
                return 60 * 60 * 24 ;
            case 'weekly':
                return 60 * 60 * 24 * 7;
            case 'monthly':
                return 60 * 60 * 24 * 30;
        }
    }

    public function isCCTestMode()
    {
        return (bool)$this->scopeConfig->isSetFlag('payment/moneris/chmoneriscc/testmode');
    }
    public function isTest()
    {
        return (bool)$this->scopeConfig->isSetFlag('payment/moneris/chmoneriscc/testmode');
    }

    public function getObject($class)
    {
        return $this->objectFactory->create([], $class);
    }

    public function isUsApi()
    {
        return $this->getModuleConfig(self::XML_PATH_US_API);
    }
    
    public function getVaultEnabled()
    {
        return $this->getConfigData("payment/chmoneris/enable_vault");
    }
    
    public function getIsVbvEnabled()
    {
        return $this->getModuleConfig(self::XML_PATH_VBV_ENABLED);
    }
    
    public function getIsVbvRequired()
    {
        return $this->getIsVbvEnabled() && $this->getModuleConfig(self::XML_PATH_VBV_REQUIRED);
    }

    public function getConfigPath()
    {
        return self::CONFIG_PATH . self::MODULE_CODE . '/';
    }

    public function getCheckoutSession()
    {
        return $this->getObject('Magento\Checkout\Model\Session');
    }
    
    public function getQuoteCurrency()
    {
        $currencyCode = $this->getCheckoutSession()->getQuote()->getQuoteCurrencyCode();
        if ($this->isAdmin()) {
            // Are we in credit memo?
            $_creditMemo = $this->registry->registry('current_creditmemo');
            if ($_creditMemo) {
                $currencyCode = $_creditMemo->getOrder()->getOrderCurrencyCode();
            } else {
                $session = $this->getBackendSessionQuote();
                // We will get the store ID from here
                $currencyCode = $session->getCurrencyCode();
                if (!$currencyCode) {
                    $currencyCode = $session->getOrderCurrencyCode();
                }
            }
    
            if (!$currencyCode && $this->registry->registry('current_invoice')) {
                $currencyCode = $this->registry->registry('current_invoice')->getOrder()->getOrderCurrencyCode();
            }
        } elseif (!$currencyCode) {
            $order = $this->getOrder($this->getCheckoutSession()->getLastOrderId());
            if ($order && $order->getId()) {
                $currencyCode = $order->getOrderCurrencyCode();
            }
        }
        
        return $currencyCode;
    }
    
    public function isAlternateCurrency()
    {
        return ($this->getModuleConfig('alternate_password_enabled') &&
                $this->getQuoteCurrency() == $this->getModuleConfig('alternate_password_currency')
               );
    }
    
    public function getMonerisStoreId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('payment/moneris/chmoneriscc/login'));
    }
    
    public function getMonerisApiToken()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('payment/moneris/chmoneriscc/password'));
    }
    
    public function getPaymentAction()
    {
        return $this->getModuleConfig('payment_action');
    }
    
    /**
     * Sets data in to the additional_information field of a payment.
     *
     * @param \Magento\Framework\DataObject $payment, string $key, mixed $data
     * @return $payment
     */
    public function setPaymentAdditionalInfo(\Magento\Framework\DataObject $payment, $key, $data)
    {
        $info = $payment->getAdditionalInformation();
        
        if (!is_array($info)) {
            $info = [$info];
        }
        
        $info[$key] = $data;
        $payment->setAdditionalInformation($info);
        return $payment;
    }
    
    /**
     * Gets data from the additional_information field of a payment.
     *
     * @param \Magento\Framework\DataObject $payment, string $key=null
     * @return mixed
     */
    public function getPaymentAdditionalInfo(\Magento\Framework\DataObject $payment, $key = null)
    {
        $info = $payment->getAdditionalInformation();
        if (!is_array($info)) {
            return null;
        }
        
        if (!$key) {
            return $info;
        }
        
        if (!isset($info[$key])) {
            return null;
        }
        
        return $info[$key];
    }
    
    public function getTransPart($type, $k)
    {
        if (!is_array($k) || !isset($k[0])) {
            return $k;
        }
        
        $k = strtoupper(trim($k));
        if ($type == self::AVS_MATCH) {
            return isset($this->avsResponse[$k]) ? $this->avsResponse[$k] :  "No Datas for avs information (".$k.")";
        }
        
        if ($type == self::CVD_MATCH) {
            $ret = "";
            if (isset($k[0]) && isset($this->cvdFirst[$k[0]])) {
                $ret .= $this->cvdFirst[$k[0]];
            } else {
                $ret .= "No first part of cvd: ".$k[0];
            }
        
            if (isset($this->cvdSecond[$k[1]])) {
                $ret .= ' ' . $this->cvdSecond[$k[1]];
            } else {
                $ret .= " No second part of cvd: ".$k[1];
            }
        
            return __($ret);
        }
    }
    
    public function getAvsSuccessCodes()
    {
        $codesString = $this->getModuleConfig('avssuccess');
        $codes = explode(',', $codesString);
        
        foreach ($codes as &$c) {
            $c = trim($c);
        }
        
        return $codes;
    }
        
    public function getCvdSuccessCodes()
    {
        $codesString = $this->getModuleConfig('cvdsuccess');
        $codes = explode(',', $codesString);
        
        foreach ($codes as &$c) {
            $c = trim($c);
        }
        
        return $codes;
    }
    
    public function getResponseTextOverride($code, $status = null)
    {
        $responses = $this->getResponses();
        // if a status is given, make sure it matches
        if (isset($responses[$code]) && (!$status || $responses[$code]['status'] == $status)) {
            return $responses[$code]['message'];
        }
        
        return false;
    }
    
    public function getResponses()
    {
        $responsesString = $this->getModuleConfig('responses');
        $responses = [];
        if (strlen($responsesString) <= 2) {
            return $responses;
        }
        
        $_t = explode("\n", trim($responsesString));
        foreach ($_t as $x) {
            $_d = explode(":", $x);
            if (isset($_d[0]) && count($_d) == 3) {
                $responses[trim($_d[0])] = ['status' => trim($_d[1]), 'message' => trim($_d[2])];
            }
        }
        
        return $responses;
    }
    
    public function handleError($error, $throw = false)
    {
        if (!$this->getCustomerSession()->getMonerCavvError()) {
            $this->log(__METHOD__ . __LINE__ . "Error: ". $error);
            $this->repopulateCart();
            if ($throw) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($error)
                );
            }
        }
        
        $this->getCustomerSession()->unsMonerCavvError();
    }

    /**
     * Return array of required moneris payment form fields
     * @return array
     */
    public function getPaymentData()
    {
        $quote = $this->getCheckoutSession()->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $grandTotal = $this->formatPrice($quote->getGrandTotal());
        
        $urlBuilder = ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $params = [
            'ps_store_id' => $this->getConfigData('payment/chmonerisredirect/ps_store_id', true),
            'hpp_key' => $this->getConfigData('payment/chmonerisredirect/hpp_key', true),
            'charge_total' => $grandTotal,
            'order_id' => $this->generateUniqueId($quote),
            'cust_id' => $this->getCustomerId($quote),
            //====== prepare Billing Data
            'bill_first_name' => $billingAddress->getData('firstname'),
            'bill_last_name' => $billingAddress->getData('lastname'),
            'bill_address_one' => $billingAddress->getData('street'),
            'bill_company_name' => $billingAddress->getData('company'),
            'bill_city' => $billingAddress->getData('city'),
            'bill_postal_code' => $billingAddress->getData('postcode'),
            'bill_state_or_province' => $billingAddress->getRegionCode(),
            'bill_country' => $billingAddress->getData('country_id'),
            'bill_phone' => $billingAddress->getData('telephone'),
            'bill_fax' => $billingAddress->getData('fax'),
            //====== prepare Shipping Data
            'ship_first_name' => $shippingAddress->getData('firstname'),
            'ship_last_name' => $shippingAddress->getData('lastname'),
            'ship_address_one' => $shippingAddress->getData('street'),
            'ship_company_name' => $shippingAddress->getData('company'),
            'ship_city' => $shippingAddress->getData('city'),
            'ship_postal_code' => $shippingAddress->getData('postcode'),
            'ship_state_or_province' => $shippingAddress->getRegionCode(),
            'ship_country' => $shippingAddress->getData('country_id'),
            'ship_phone' => $shippingAddress->getData('telephone'),
            'ship_fax' => $shippingAddress->getData('fax'),
            //===== prepare Optional Details
            'gst' => $this->formatPrice($shippingAddress->getData('tax_amount')),
            'shipping_cost' => $this->formatPrice($shippingAddress->getData('shipping_amount')),
            'email' => $billingAddress->getData('email'),
            'request_url' => $this->getMonerisRequestUrl()
        ];
        
        //===== prepare Item Details
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $uniqueId = $this->generateUniqueId(false, 10);
            $params["id".$uniqueId] = $item->getSku();
            $params["description".$uniqueId] = $item->getName();
            $params["quantity".$uniqueId] = $item->getQty();
            $params["price".$uniqueId] = $this->formatPrice($item->getPrice());
            $params["subtotal".$uniqueId] = $this->formatPrice($item->getRowTotal());
        }
        
        return $params;
    }
    
    public function getCustomerId($quote)
    {
        $customerId = Transaction::CUSTOMER_ID_DELIM . 'Guest';
        if ($quote->getCustomerId() && $quote->getCustomerId() !== "") {
            $customerId = Transaction::CUSTOMER_ID_DELIM . $quote->getCustomerId();
        }
    
        $billingAddress = $quote->getBillingAddress();
    
        $fullCustomerName = $billingAddress->getData('firstname');
        $fullCustomerName .= Transaction::CUSTOMER_ID_DELIM . $billingAddress->getData('lastname');
        // we can only send 50 chars
        $customerIdLength = strlen($customerId);
        $fullCustomerName = substr(
            $fullCustomerName,
            0,
            (Transaction::MAX_CHARS_CUSTOMER_ID - $customerIdLength)
        ) . $customerId;
        return $fullCustomerName;
    }
    
    public function formatPrice($price, $decimal = 2)
    {
        if (is_numeric($decimal)) {
            return sprintf('%.'.$decimal.'f', $price);
        }
        
        //default return 2 decimal places
        return sprintf('%.2f', $price);
    }

    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    public function getDecryptor()
    {
        return $this->encryptor;
    }
    
    public function getConfigData($path, $encrypt = false, $magentoStore = null)
    {
        $value = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $magentoStore);

        if (empty($value)) {
            $pathGroup = explode('/', $path);
            $newPathGroup = [];

            foreach ($pathGroup as $k => $v) {
                if ($k == 1) {
                    $newPathGroup[] = self::SETTING_GROUP;
                    $newPathGroup[] = $v;
                } else {
                    $newPathGroup[] = $v;
                }
            }
            $value = $this->scopeConfig->getValue(implode('/', $newPathGroup), \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $magentoStore);
        }
        $encryptor = ObjectManager::getInstance()->get('Magento\Framework\Encryption\Encryptor');
        if ($encrypt) {
            return $encryptor->decrypt($value);
        }
        
        return $value;
    }
    
    public function getMonerisRequestUrl()
    {
        if ($this->getConfigData('payment/chmonerisredirect/test')) {
            return 'https://esqa.moneris.com/HPPDP/index.php';
        }
        
        return 'https://www3.moneris.com/HPPDP/index.php';
    }
    
    public function generateUniqueId($quote, $length = 20)
    {
        $tail = '';
        for ($i = 0; $i < $length; $i++) {
            $tail .= rand(0, 9);
        }
        
        if (!$quote || !$quote->getId()) {
            return "{$tail}";
        }
        
        return "{$quote->getId()}-{$tail}";
    }
    
    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }
    
    /**
     * Check whether we are in the frontend area.
     *
     * @return bool
     */
    public function getIsFrontend()
    {
        // The REST API has to be considered part of the frontend, as standard checkout uses it.
        $appState = ObjectManager::getInstance()->get('Magento\Framework\App\State');
        if ($appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND
        || $appState->getAreaCode() == 'webapi_rest') {
            return true;
        }
        
        return false;
    }
}
