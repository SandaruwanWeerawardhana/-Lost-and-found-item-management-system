<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '1234');          
define('DB_NAME', 'lost_found_db');


try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

 
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` 
                DEFAULT CHARACTER SET utf8mb4 
                DEFAULT COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lost_items` (
            `id`            INT             NOT NULL AUTO_INCREMENT,
            `item_name`     VARCHAR(150)    NOT NULL,
            `category`      VARCHAR(100)    NOT NULL,
            `location_found`VARCHAR(200)    NOT NULL,
            `date_found`    DATE            NOT NULL,
            `reported_by`   VARCHAR(100)    NOT NULL,
            `contact_number` VARCHAR(16)    NOT NULL,
            `status`        ENUM('Unclaimed','Claimed','Under Review')
                            NOT NULL DEFAULT 'Under Review',
            `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");


    $count = (int) $pdo->query("SELECT COUNT(*) FROM `lost_items`")->fetchColumn();

    if ($count === 0) {
        $insertSQL = "
            INSERT INTO `lost_items`
                (item_name, category, location_found, date_found,
                 reported_by, contact_number, status)
            VALUES
                (:item_name, :category, :location_found, :date_found,
                 :reported_by, :contact_number, :status)
        ";

        $stmt = $pdo->prepare($insertSQL);

        $sampleItems = [
            [
                'item_name'      => 'Black Leather Wallet',
                'category'       => 'Accessories',
                'location_found' => 'Main Library – Reading Room',
                'date_found'     => '2026-05-01',
                'reported_by'    => 'John Doe',
                'contact_number' => '0771234567',
                'status'         => 'Unclaimed',
            ],
            [
                'item_name'      => 'Blue Water Bottle',
                'category'       => 'Drinkware',
                'location_found' => 'Cafeteria – Table 5',
                'date_found'     => '2026-05-03',
                'reported_by'    => 'Jane Smith',
                'contact_number' => '0779876543',
                'status'         => 'Unclaimed',
            ],
            [
                'item_name'      => 'iPhone 13 – Black',
                'category'       => 'Electronics',
                'location_found' => 'Parking Lot B – Near Entrance',
                'date_found'     => '2026-05-05',
                'reported_by'    => 'Alice Johnson',
                'contact_number' => '0765551234',
                'status'         => 'Under Review',
            ],
        ];

        foreach ($sampleItems as $item) {
            $stmt->execute($item);
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Alias for compatibility with search.php and other files
$conn = $pdo;
?>