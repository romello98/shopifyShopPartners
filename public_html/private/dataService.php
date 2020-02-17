<?php

require_once __DIR__ . '/model/Partner.class.php';
require_once __DIR__ . '/model/Admin.class.php';
require_once __DIR__ . '/model/Order.class.php';
require_once __DIR__ . '/model/PaymentRequest.class.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/security.php';

define('ERR_LOGIN_INCORRECT', 'Le login/mot de passe entré est incorrect.');
define('ERR_LOGIN_INFO', 'Les informations envoyées lors de la connexion sont erronées.');

date_default_timezone_set('Europe/Brussels');

class DataService
{
    private const SERVER_NAME = 'localhost';
    private const USER_NAME = 'id12511142_admin';
    private const PASSWORD = 'Romelimelo98!';
    private const DATABASE = 'id12511142_shoppartners';
    
    function __construct()
    {

    }
    
    function createConnection()
    {
        $connection = new mysqli(self::SERVER_NAME, self::USER_NAME, self::PASSWORD, self::DATABASE);
        return $connection;
    }
    
    function closeConnection($connection)
    {
        if(!empty($connection))
            $connection->close();
    }

    function savePartner($partner)
    {
        $connection = $this->createConnection();
        $query = '  UPDATE `Partner`
                    SET FirstName = ?,
                    LastName = ?,
                    `Password` = ?,
                    Email = ?,
                    PayPalEmail = ?
                    WHERE ID = ?';
        $hashedPassword = password_hash($partner->Password, PASSWORD_BCRYPT);
        $statement = $connection->prepare($query);
        $statement->bind_param('sssssi', $partner->FirstName, $partner->LastName, 
            $hashedPassword, $partner->Email, $partner->PayPalEmail, $partner->ID);
        $statement->execute();
        $updateDone = $statement->affected_rows > 0;
        $statement->close();
        $connection->close();

        return $updateDone;
    }

    
    function getPartnerByCode($code)
    {
        if(!empty($code))
        {
            $connection = $this->createConnection();
            $query = "
                SELECT ID, FirstName, LastName, Email, Password, PayPalEmail, PartnerCode, CommissionPercentage FROM Partner WHERE PartnerCode = ?";
            $statement = $connection->prepare($query);
            $statement->bind_param('s', $code);
            $statement->execute();
            $result = $statement->get_result();
            $statement->close();
            $connection->close();
            
            if($row = $result->fetch_object(Partner::class))
            {
                return $row;
            }

            return null;
        }
    }
    
    function addVisit($affiliateCode)
    {
        $row = $this->getPartnerByCode($affiliateCode);
        if(empty($row->ID))
        {
            return;
        }
        $connection = $this->createConnection();
    
        $sql = "INSERT INTO Visit (PartnerID)
        VALUES (?)";
        $statement = $connection->prepare($sql);
        $statement->bind_param('i', intval($row->ID));
        $result = $statement->execute();
        
        $statement->close();
        $connection->close();
    }
    
    function getVisitsByPartnerIdAndMonth($partnerID, $month, $year)
    {
        if(empty($partnerID)) return null;
        if($month == null) $month = date('n');
        if($year == null) $year = date('Y');
        
        $connection = $this->createConnection();
        $query = "SELECT PartnerID, count(*) AS `TotalVisits`, DAY(VisitDateTime) AS `Day`
            FROM Visit
            WHERE MONTH(VisitDateTime) = ? AND YEAR(VisitDateTime) = ?
            AND PartnerID = ?
            GROUP BY Day
            ORDER BY Day ASC";
        $statement = $connection->prepare($query);
        $statement->bind_param('iii', $month, $year, $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $visits = [];
        
        while($row = $result->fetch_assoc())
        {
            $visits[] = $row;
        }
        
        $connection->close();
        return $visits;
    }

    function getAllVisitsByMonth($month, $year)
    {
        if($month == null) $month = date('n');
        if($year == null) $year = date('Y');
        
        $connection = $this->createConnection();
        $query = "SELECT count(*) AS `TotalVisits`, DAY(VisitDateTime) AS `Day`
            FROM Visit
            WHERE MONTH(VisitDateTime) = ? AND YEAR(VisitDateTime) = ?
            AND PartnerID IS NOT NULL
            GROUP BY Day
            ORDER BY Day ASC";
        $statement = $connection->prepare($query);
        $statement->bind_param('ii', $month, $year);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $visits = [];
        
        while($row = $result->fetch_assoc())
        {
            $visits[] = $row;
        }
        
        $connection->close();
        return $visits;
    }

    function getSalesByPartnerIdAndMonth($partnerID, $month, $year)
    {
        if(empty($partnerID)) return null;
        if($month == null) $month = date('n');
        if($year == null) $year = date('Y');
        
        $connection = $this->createConnection();
        $query = "SELECT PartnerID, sum(Amount * COALESCE(BonusCommissionPercentage, CommissionPercentage)) AS `TotalSales`, count(*) AS `NumberOfSales`, DAY(PaymentDateTime) AS `Day`, sum(Amount) AS `Turnover`
            FROM `Order`
            WHERE CommissionPercentage IS NOT NULL AND `Status` NOT IN('rejected', 'canceled') AND MONTH(PaymentDateTime) = ? 
            AND YEAR(PaymentDateTime) = ? 
            AND PartnerID = ? 
            GROUP BY Day
            ORDER BY Day ASC";
        $statement = $connection->prepare($query);
        $statement->bind_param('iii', $month, $year, $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $sales = [];
        
        while($row = $result->fetch_assoc())
        {
            $sales[] = $row;
        }
        
        $connection->close();
        return $sales;
    }

    function getAllPartnerSales($month, $year)
    {
        if($month == null) $month = date('n');
        if($year == null) $year = date('Y');
        
        $connection = $this->createConnection();
        $query = "SELECT sum(Amount * COALESCE(BonusCommissionPercentage, CommissionPercentage)) AS `TotalSales`, count(*) AS `NumberOfSales`, DAY(PaymentDateTime) AS `Day`, sum(Amount) AS `Turnover`
            FROM `Order`
            WHERE CommissionPercentage IS NOT NULL AND `Status` NOT IN('rejected', 'canceled') AND MONTH(PaymentDateTime) = ? 
            AND YEAR(PaymentDateTime) = ?
            AND PartnerID IS NOT NULL
            GROUP BY Day
            ORDER BY Day ASC";
        $statement = $connection->prepare($query);
        $statement->bind_param('ii', $month, $year);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $sales = [];
        
        while($row = $result->fetch_assoc())
        {
            $sales[] = $row;
        }
        
        $connection->close();
        return $sales;
    }

    function getPagedSalesByPartner($partnerID, $pageSize = 10, $pageIndex = 0)
    {
        if(empty($partnerID)) return null;

        $connection = $this->createConnection();
        $query = 
        "   SELECT o.ID, o.ShopifyID, o.ShippingDateTime, o.PaymentDateTime, o.PartnerID, o.PayoutDateTime, 
            o.PaymentRequestID, o.CommissionPercentage, o.BonusCommissionPercentage, o.Amount, o.CustomerWithdrawalLimitDate, 
            IF(pr.ID IS NOT NULL, 
                IF(pr.PaymentDateTime IS NOT NULL, 
                    'paid'
                , 
                    'awaiting_payment'
                )
            ,
                IF(DATE_ADD(ShippingDateTime, INTERVAL 15 DAY) < CURRENT_TIMESTAMP AND PayoutDateTime IS NULL 
                    AND o.Status NOT IN ('rejected', 'canceled', 'paid') AND pr.PaymentDateTime IS NULL, 
                        'eligible'
                    , 
                        o.Status)
            ) 
            AS `Status`
            FROM `Order` o
            LEFT JOIN `PaymentRequest` pr
            ON pr.ID = o.PaymentRequestID 
            WHERE o.CommissionPercentage IS NOT NULL AND o.PartnerID = ?
            GROUP BY o.ID
            ORDER BY PaymentDateTime DESC 
            LIMIT " . $pageIndex * $pageSize . ", $pageSize
        ";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $sales = [];
        
        while($row = $result->fetch_object(Order::class))
        {
            $sales[] = $row;
        }
        
        $connection->close();
        return $sales;
    }

    function getUserByEmailAndPassword(string $email, string $password)
    {
        if(empty($email) || empty($password)) return ERR_LOGIN_INCORRECT;

        $connection = $this->createConnection();
        $query = '  SELECT *
                    FROM `Partner`
                    WHERE Email = ?';
        $statement = $connection->prepare($query);
        $statement->bind_param('s', $email);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        if(isset($result) && $result->num_rows > 0)
        {
            $partner = $result->fetch_object(Partner::class);
            if(password_verify($password, $partner->Password))
                return $partner;
        }
        return ERR_LOGIN_INFO;
    }

    function getAdminByEmailAndPassword(string $email, string $password)
    {
        if(empty($email) || empty($password)) return ERR_LOGIN_INCORRECT;

        $connection = $this->createConnection();
        $query = '  SELECT *
                    FROM `Admin`
                    WHERE Email = ?';
        $statement = $connection->prepare($query);
        $statement->bind_param('s', $email);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        if(isset($result) && $result->num_rows > 0)
        {
            $admin = $result->fetch_object(Admin::class);
            if(password_verify($password, $admin->Password))
                return $admin;
        }
        return ERR_LOGIN_INFO;
    }

    function otherPartnerEmailExists($partnerID, $email)
    {
        $connection = $this->createConnection();
        $query = '  SELECT *
                    FROM `Partner`
                    WHERE Email = ?
                    AND ID <> ?';
        $statement = $connection->prepare($query);
        $statement->bind_param('si', $email, $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        return $result->num_rows > 0;
    }

    function hasPaypalEmail($partnerID)
    {
        $connection = $this->createConnection();
        $query = '  SELECT *
                    FROM `Partner`
                    WHERE ID = ?';
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        return !empty($result->fetch_assoc()['PayPalEmail']);
    }

    function addOrder(Order $order)
    {
        if($order->_ReferralCode !== null)
        {
            $partner = $this->getPartnerByCode($order->_ReferralCode);
            if(isset($partner->ID))
            {
                $order->PartnerID = intval($partner->ID);
            }

            if(isset($partner->CommissionPercentage))
            {
                $order->CommissionPercentage = doubleval($partner->CommissionPercentage);
            }
        }
        
        $connection = $this->createConnection();
    
        if(!empty($order->PartnerID))
        {
            $sql = "INSERT INTO `Order` (ShopifyID, CommissionPercentage, Amount, PartnerID)
            VALUES (?, ?, ?, ?)";
            $statement = $connection->prepare($sql);
            $statement->bind_param('iddi', 
                $order->ShopifyID,
                $order->CommissionPercentage,
                $order->Amount,
                $order->PartnerID
            );
        }
        else
        {
            $sql = "INSERT INTO `Order` (ShopifyID, CommissionPercentage, Amount, PartnerID)
            VALUES (?, ?, ?, NULL)";
            $statement = $connection->prepare($sql);
            $statement->bind_param('idd', 
                $order->ShopifyID,
                $order->CommissionPercentage,
                $order->Amount
            );
        }
        $result = $statement->execute();
        $errors = $statement->error_list;
        
        $statement->close();
        $connection->close();
        return $errors;
    }

    function getEligiblePayments($userID)
    {
        if($userID == null) return;

        $orders = [];
        $connection = $this->createConnection();
        $query = "
            SELECT * 
            FROM `Order`
            WHERE `Status` NOT IN('rejected', 'canceled') AND DATE_ADD(ShippingDateTime, INTERVAL 15 DAY) < CURRENT_TIMESTAMP 
            AND PaymentRequestID IS NULL AND PartnerID = ?";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $userID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        while($order = $result->fetch_object(Order::class))
            $orders[] = $order;

        return $orders;
    }

    function getEligiblePaymentsAmount($userID)
    {
        if($userID == null) return;

        $connection = $this->createConnection();
        $query = "
            SELECT sum(Amount * COALESCE(BonusCommissionPercentage, CommissionPercentage)) AS `Revenue`, sum(Amount) AS `SalesAmount`
            FROM `Order`
            WHERE `Status` NOT IN('rejected', 'canceled')
            AND PartnerID = ?
            AND (
                DATE_ADD(ShippingDateTime, INTERVAL 15 DAY) < CURRENT_TIMESTAMP
                OR DATE_ADD(PaymentDateTime, INTERVAL 30 DAY) < CURRENT_TIMESTAMP 
            )
            AND PaymentRequestID IS NULL";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $userID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();
        return $result->fetch_object();
    }

    function getPaidPayments($userID)
    {
        if($userID == null) return;

        $orders = [];
        $connection = $this->createConnection();
        $query = 
        "   SELECT o.*, COALESCE(o.PayoutDateTime, pr.PaymentDateTime) AS `PayoutDateTime`
            FROM `Order` o
            JOIN `PaymentRequest` pr
            ON pr.ID = o.PaymentRequestID 
            WHERE (o.Status = 'paid' OR o.PayoutDateTime IS NOT NULL OR pr.PaymentDateTime IS NOT NULL) AND o.PartnerID = ?";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $userID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        while($order = $result->fetch_object(Order::class))
            $orders[] = $order;

        return $orders;
    }

    function addPaymentRequest($partnerID, $ids)
    {
        if($ids == null || sizeof($ids) == 0 || sizeof($ids) == 0) return;

        $insertedPaymentRequest = null;
        $connection = $this->createConnection();
        $connection->begin_transaction();
        $query = 
        "   INSERT INTO `PaymentRequest` (PartnerID)
            VALUES (?)";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $partnerID);
        $statement->execute();
        $success = $statement->affected_rows > 0;
        $paymentRequestID = $statement->insert_id;

        if($success)
        {
            $statement->close();
            $inClause = join(',', array_map(function($id) { return "$id"; }, $ids));
            $query = 
            "UPDATE `Order`
            SET PaymentRequestID = $paymentRequestID
            WHERE `Order`.ID IN ($inClause)";

            $statement = $connection->prepare($query);
            $statement->execute();
            $success = $statement->affected_rows > 0;

            if($success) 
            {
                $connection->commit();
                $query = 
                "   SELECT pr.*, sum(o.Amount * COALESCE(o.BonusCommissionPercentage, o.CommissionPercentage)) as `Total`
                    FROM `PaymentRequest` pr
                    LEFT JOIN `Order` o ON o.PaymentRequestID = pr.ID
                    WHERE pr.ID = ?
                    GROUP BY pr.ID";
                $statement = $connection->prepare($query);
                $statement->bind_param('i', $paymentRequestID);
                $statement->execute();
                $result = $statement->get_result();
                $insertedPaymentRequest = $result->fetch_object(PaymentRequest::class);
            }
            else {
                $connection->rollback();
                $statement->close();
                $connection->close();
                throw new Exception('Payment request added but empty.');
            }
        } 
        else $connection->rollback();

        $statement->close();
        $connection->close();
        return $insertedPaymentRequest;
    }

    function getOrdersByPaymentRequestIDAndCurrentUser($paymentRequestID)
    {
        if(!is_authenticated()) return null;

        $connection = $this->createConnection();
        $currentUser = getCurrentLoggedUser(); 
        if($currentUser == null) return null;
        $query = "SELECT o.*
            FROM `Order` o
            WHERE o.PaymentRequestID = ?
            AND o.PartnerID = ?";
        $statement = $connection->prepare($query);
        $statement->bind_param('ii', $paymentRequestID, $currentUser->ID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $orders = [];
        
        while($row = $result->fetch_object(Order::class))
        {
            $orders[] = $row;
        }
        
        $connection->close();
        return $orders;
    }

    function getPaymentRequests($partnerID, $all = false)
    {
        if($partnerID == null && !$all) return;

        $paymentRequests = [];
        $connection = $this->createConnection();
        if(!$all)
        {
            $query = 
            "   SELECT pr.*, sum(o.Amount * COALESCE(o.BonusCommissionPercentage, o.CommissionPercentage)) as `Total`
                FROM `PaymentRequest` pr
                LEFT JOIN `Order` o ON o.PaymentRequestID = pr.ID
                WHERE pr.PartnerID = ?
                GROUP BY pr.ID
                ORDER BY pr.DateTime DESC";
            $statement = $connection->prepare($query);
            $statement->bind_param('i', $partnerID);
        }
        else
        {
            $query = 
            "   SELECT pr.*, sum(o.Amount * COALESCE(o.BonusCommissionPercentage, o.CommissionPercentage)) as `Total`
                FROM `PaymentRequest` pr
                LEFT JOIN `Order` o ON o.PaymentRequestID = pr.ID
                GROUP BY pr.ID
                ORDER BY pr.DateTime DESC";
                $statement = $connection->prepare($query);
        }
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        while($paymentRequest = $result->fetch_object(PaymentRequest::class))
            $paymentRequests[] = $paymentRequest;

        return $paymentRequests;
    }

    function makePayment($paymentRequestID)
    {
        if($paymentRequestID == null) return null;
        
        $connection = $this->createConnection();
        $connection->begin_transaction();
        $query = 
        "   UPDATE `PaymentRequest`
            SET PaymentDateTime = NOW()
            WHERE ID = ?";
        $statement = $connection->prepare($query);
        $statement->bind_param('i', $paymentRequestID);
        $statement->execute();
        $result = $statement->affected_rows > 0;
        if($result) 
        {
            $query = 
            "   UPDATE `Order`
                SET PayoutDateTime = NOW()
                WHERE PaymentRequestID = ? ";
            $statement = $connection->prepare($query);
            $statement->bind_param('i', $paymentRequestID);
            $statement->execute();
            $result = $statement->affected_rows > 0;
            if($result) $connection->commit();
            else $connection->rollback();
        }
        else $connection->rollback();
        $statement->close();
        $connection->close();

        return $result;
    }

    function applyMonthlyBonus($partnerID, $bonus)
    {
        if($partnerID == null) return null;
        $month = intval(date('n'));
        $year = intval(date('Y'));
        if($bonus < 0 || $bonus > 0.25) $bonus = 0.25;
        
        try
        {
            $connection = $this->createConnection();
            $query = 
            "   UPDATE `Order`
                SET BonusCommissionPercentage = ?
                WHERE MONTH(PaymentDateTime) = ?
                AND YEAR(PaymentDateTime) = ?
                AND PartnerID = ?";
            $statement = $connection->prepare($query);
            $statement->bind_param('diii', $bonus, $month, $year, $partnerID);
            $statement->execute();
            $numberRowsAffected = $statement->affected_rows;
            $statement->close();
            $connection->close();
        } catch (Exception $e)
        {
            return null;
        }

        return $numberRowsAffected > 0;
    }

    function hasAlreadyHadMonthlyBonus($partnerID)
    {
        if($partnerID == null) return true;
        $month = intval(date('n'));
        $year = intval(date('Y'));
        
        $connection = $this->createConnection();
        $query = 
        "   SELECT BonusCommissionPercentage FROM `Order`
            WHERE MONTH(PaymentDateTime) = ?
            AND YEAR(PaymentDateTime) = ?
            AND PartnerID = ?
            AND BonusCommissionPercentage IS NOT NULL";
        $statement = $connection->prepare($query);
        $statement->bind_param('dii', $month, $year, $partnerID);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        return $result->num_rows > 0;
    }

    function mayBenefitFromMonthlyBonus($partnerID, $minTurnover = 500)
    {
        if($partnerID == null) return false;
        $month = intval(date('n'));
        $year = intval(date('Y'));
        
        $connection = $this->createConnection();
        $query = 
        "   SELECT SUM(Amount) `Amount`, MONTH(PaymentDateTime) M, YEAR(PaymentDateTime) Y FROM `Order`
            WHERE PartnerID = ?
            GROUP BY M, Y
            HAVING M = ? AND Y = ?";
        $statement = $connection->prepare($query);
        $statement->bind_param('iii', $partnerID, $month, $year);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        $connection->close();

        return $result->fetch_assoc()['Amount'] >= $minTurnover && !$this->hasAlreadyHadMonthlyBonus($partnerID);
    }

    function getCustomersEmails($onlyAcceptMarketing = true)
    {
        $emailsList = [];
        $connection = $this->createConnection();
        $query = 
        "   SELECT Email
            FROM `Customer` ";
        if($onlyAcceptMarketing)
            $query .= " WHERE AcceptsMarketing = TRUE";
        $result = $connection->query($query);
        $connection->close();

        while($row = $result->fetch_assoc())
            $emailsList[] = $row['Email'];

        return $emailsList;
    }

    function getPartnersEmails()
    {
        $emailsList = [];
        $connection = $this->createConnection();
        $query = 
        "   SELECT Email
            FROM `Partner` ";
        $result = $connection->query($query);
        $connection->close();

        while($row = $result->fetch_assoc())
            $emailsList[] = $row['Email'];

        return $emailsList;
    }
}

?>