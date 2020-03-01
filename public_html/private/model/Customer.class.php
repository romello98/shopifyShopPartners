<?php

class Customer
{
    public $ID;
    public $ShopifyID;
    public $FirstName;
    public $LastName;
    public $Email;
    public $AcceptsMarketing;
    public $LastUpdate;

    function fillWithShopifyObject($obj)
    {
        if(self::filledProperty($obj, 'email'))
            $this->Email = $obj->email;
        if(self::filledProperty($obj, 'accepts_marketing'))
            $this->AcceptsMarketing = $obj->accepts_marketing;
        if(self::filledProperty($obj, 'first_name'))
            $this->FirstName = $obj->first_name;
        if(self::filledProperty($obj, 'last_name'))
            $this->LastName = $obj->last_name;
        if(self::filledProperty($obj, 'id'))
            $this->ShopifyID = $obj->id;
        if(self::filledProperty($obj, 'updated_at'))
            $this->LastUpdate = strtotime($obj->updated_at);
        else if(self::filledProperty($obj, 'created_at'))
            $this->LastUpdate = strtotime($obj->created_at);

    }

    static function filledProperty($obj, $prop)
    {
        return property_exists($obj, $prop) && $obj->$prop !== null;
    }
}