CREATE TABLE Admin (
  ID       int(10) NOT NULL AUTO_INCREMENT, 
  Email    varchar(255) NOT NULL UNIQUE, 
  Password varchar(255) NOT NULL, 
  PRIMARY KEY (ID));
CREATE TABLE Customer (
  ID               int(10) NOT NULL AUTO_INCREMENT, 
  FirstName        varchar(255), 
  LastName         varchar(255), 
  Email            varchar(255) NOT NULL UNIQUE, 
  ShopifyID        bigint(22) NOT NULL UNIQUE, 
  AcceptsMarketing tinyint(1) DEFAULT 1 NOT NULL, 
  LastUpdate       datetime DEFAULT NOW() NOT NULL, 
  PRIMARY KEY (ID));
CREATE TABLE `Order` (
  ID                          int(10) NOT NULL AUTO_INCREMENT, 
  ShopifyID                   int(22) NOT NULL UNIQUE, 
  PaymentDateTime             timestamp DEFAULT NOW() NOT NULL, 
  CommissionPercentage        decimal(2, 2), 
  BonusCommissionPercentage   decimal(2, 2), 
  PayoutDateTime              timestamp NULL, 
  CustomerWithdrawalLimitDate timestamp NULL, 
  Amount                      decimal(8, 2) NOT NULL, 
  ShippingDateTime            datetime NULL, 
  Status                      varchar(64) DEFAULT 'created' NOT NULL, 
  PartnerID                   int(10), 
  PaymentRequestID            int(10), 
  CustomerID                  int(10), 
  PRIMARY KEY (ID));
CREATE TABLE OrderLine (
  ID               int(10) NOT NULL AUTO_INCREMENT, 
  ShopifyProductID int(10), 
  UnitPrice        decimal(8, 2) NOT NULL, 
  Label            varchar(512) NOT NULL, 
  Quantity         int(10) NOT NULL, 
  OrderID          int(10) NOT NULL, 
  PRIMARY KEY (ID));
CREATE TABLE `Partner` (
  ID                   int(10) NOT NULL AUTO_INCREMENT, 
  FirstName            varchar(255) NOT NULL, 
  LastName             varchar(255) NOT NULL, 
  Email                varchar(255) NOT NULL UNIQUE, 
  Password             varchar(255) NOT NULL, 
  PayPalEmail          varchar(255), 
  PartnerCode          varchar(32) NOT NULL UNIQUE, 
  CommissionPercentage decimal(2, 2) DEFAULT 0.15 NOT NULL, 
  PaymentThreshold     int(10) DEFAULT 50 NOT NULL, 
  PRIMARY KEY (ID));
CREATE TABLE PaymentRequest (
  ID              int(10) NOT NULL AUTO_INCREMENT, 
  DateTime        datetime DEFAULT NOW() NOT NULL, 
  PaymentDateTime timestamp NULL, 
  PartnerID       int(10) NOT NULL, 
  PRIMARY KEY (ID));
CREATE TABLE Visit (
  ID            int(10) NOT NULL AUTO_INCREMENT, 
  Country       varchar(255), 
  City          varchar(255), 
  VisitDateTime timestamp DEFAULT NOW() NOT NULL, 
  ShopifyID     int(10), 
  ProductLabel  varchar(512), 
  PartnerID     int(10) NOT NULL, 
  PRIMARY KEY (ID));
ALTER TABLE `Order` ADD CONSTRAINT Order_To_Partner FOREIGN KEY (PartnerID) REFERENCES `Partner` (ID);
ALTER TABLE Visit ADD CONSTRAINT Visit_To_Partner FOREIGN KEY (PartnerID) REFERENCES `Partner` (ID);
ALTER TABLE PaymentRequest ADD CONSTRAINT PaymentRequest_To_Partner FOREIGN KEY (PartnerID) REFERENCES `Partner` (ID);
ALTER TABLE `Order` ADD CONSTRAINT Order_To_PaymentRequest FOREIGN KEY (PaymentRequestID) REFERENCES PaymentRequest (ID);
ALTER TABLE `Order` ADD CONSTRAINT Order_To_Customer FOREIGN KEY (CustomerID) REFERENCES Customer (ID);
ALTER TABLE OrderLine ADD CONSTRAINT OrderLine_To_Order FOREIGN KEY (OrderID) REFERENCES `Order` (ID);
