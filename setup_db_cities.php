<?php
require_once __DIR__ . '/config/database.php';

try {
    // Create table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_cities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            city VARCHAR(100) NOT NULL UNIQUE,
            price DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert sample cities (Morocco)
    $cities = [
        ['Casablanca', 50.00],
        ['Rabat', 70.00],
        ['Marrakech', 80.00],
        ['Fes', 90.00],
        ['Agadir', 100.00],
        ['Tangier', 85.00],
        [' Meknes', 75.00],
        ['Oujda', 95.00],
        ['Kenitra', 65.00],
        ['Tetouan', 90.00],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO delivery_cities (city, price) VALUES (?, ?)");
    foreach ($cities as [$city, $price]) {
        $stmt->execute([$city, $price]);
    }

    echo "Delivery cities table created/updated with sample data. Admin: http://localhost/salah/admin/cities.php";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

