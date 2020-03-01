<?php

class OrderLine
{
    public $ID;
    public $ShopifyProductID;
    public $UnitPrice;
    public $Label;
    public $Quantity;
    public $OrderID;

    function fillWithShopifyObject($obj)
    {
        if(self::filledProperty($obj, 'product_id'))
            $this->ShopifyProductID = $obj->product_id;
        if(self::filledProperty($obj, 'quantity'))
            $this->Quantity = $obj->quantity;
        if(self::filledProperty($obj, 'title'))
            $this->Label = $obj->title;
        if(self::filledProperty($obj, 'price'))
            $this->UnitPrice = $obj->price;
        if(self::filledProperty($obj, 'total_discount'))
            $this->UnitPrice -= $obj->total_discount;
    }

    static function filledProperty($obj, $prop)
    {
        return property_exists($obj, $prop) && $obj->$prop !== null;
    }
}