<?php 

class Order
{
    public $ID;
    public $ShopifyID;
    public $PaymentDateTime;
    public $CommissionPercentage;
    public $BonusCommissionPercentage;
    public $PayoutDateTime;
    public $Status;
    public $CustomerWithdrawalLimitDate;
    public $Amount;
    public $PartnerID;

    public $_ReferralCode;
    private static $STATUSES_LABELS = [ 'created' => 'en attente', 
                                        'rejected' => 'rejeté',
                                        'canceled' => 'annulé',
                                        'eligible' => 'éligible',
                                        'awaiting_payment' => 'paiement demandé',
                                        'paid' => 'payé'];

    function fillWithShopifyObject($obj)
    {
        if(!self::filledProperty($obj, 'financial_status') || $obj->financial_status !== 'paid')
            throw new Exception('Financial status is empty.');
        if(self::filledProperty($obj, 'id'))
            $this->ShopifyID = intval($obj->id);
        else throw new Exception('Order id is null.');
        if(self::filledProperty($obj, 'subtotal_price'))
            $this->Amount = round(floatval($obj->subtotal_price), 2);
        else throw new Exception('Subtotal price is null.');
        if(self::filledProperty($obj, 'landing_site_ref'))
            $this->_ReferralCode = $obj->landing_site_ref;
        else if(self::filledProperty($obj, 'referring_site'))
        {
            $parts = parse_url($obj->referring_site);
            parse_str($parts['query'], $query);
            $this->_ReferralCode = empty($query['ref']) ? null : $query['ref'];
        }
        else if(self::filledProperty($obj, 'landing_site'))
        {
            $parts = parse_url($obj->referring_site);
            parse_str($parts['query'], $query);
            $this->_ReferralCode = empty($query['ref']) ? null : $query['ref'];
        }
    }

    function getStatusLabel()
    {
        if($this->PayoutDateTime !== null) return self::$STATUSES_LABELS['paid'];
        return self::$STATUSES_LABELS[$this->Status];
    }

    function getPartnerRevenue()
    {
        return $this->Amount * $this->CommissionPercentage;
    }

    static function filledProperty($obj, $prop)
    {
        return property_exists($obj, $prop) && $obj->$prop !== null;
    }
}