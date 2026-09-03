<?php
// 0. Connect to MySQL server to create database
$host = 'sql312.infinityfree.com';
$username = 'if0_41400800';
$password = '7lIC2xHs71';

try {
    $pdo_server = new PDO("mysql:host=$host", $username, $password);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_server->exec("DROP DATABASE IF EXISTS salah_store");
    $pdo_server->exec("CREATE DATABASE salah_store");
    echo "Database 'salah_store' created or checked.<br>\n";
} catch (PDOException $e) {
    die("Server Connection Error: " . $e->getMessage());
}

require_once 'config/database.php';

try {
    if (!isset($pdo)) {
        die("PDO connection failed. Check config/database.php");
    }

    // 1. Create Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'banned') DEFAULT 'active',
        phone VARCHAR(20),
        address VARCHAR(255),
        address2 VARCHAR(255),
        city VARCHAR(100),
        zip VARCHAR(20),
        country VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'users' created successfully.<br>";
    
    // Add address fields to users table if they don't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
        echo "Added phone column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Phone column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255)");
        echo "Added address column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Address column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address2 VARCHAR(255)");
        echo "Added address2 column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Address2 column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN city VARCHAR(100)");
        echo "Added city column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "City column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN zip VARCHAR(20)");
        echo "Added zip column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Zip column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN country VARCHAR(100)");
        echo "Added country column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Country column note: " . $e->getMessage() . "<br>";
        }
    }

    // Forgot Password fields
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
        echo "Added reset_token column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "reset_token column note: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
        echo "Added reset_expires column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "reset_expires column note: " . $e->getMessage() . "<br>";
        }
    }

    // 2. Create Categories Table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        image VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'categories' created successfully.<br>";

    // 3. Create Sizes Table
    $sql = "CREATE TABLE IF NOT EXISTS sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'sizes' created successfully.<br>";

    // Seed Sizes if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM sizes");
    if ($stmt->fetchColumn() == 0) {
        $sizes_to_seed = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '34', '36', '37', '38', '39', '40', '41', '42', '44', 'One Size'];
        $insert_size = $pdo->prepare("INSERT INTO sizes (name) VALUES (?)");
        foreach ($sizes_to_seed as $s) {
            $insert_size->execute([$s]);
        }
        echo "Seeded sizes successfully.<br>";
    }

    // 4. Create Products Table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image1 VARCHAR(255),
        image2 VARCHAR(255),
        image3 VARCHAR(255),
        image4 VARCHAR(255),
        category VARCHAR(50),
        sizes VARCHAR(255),
        stock INT NOT NULL DEFAULT 0,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        submitted_by INT DEFAULT NULL,
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category) REFERENCES categories(slug),
        FOREIGN KEY (submitted_by) REFERENCES users(id)
    ) ENGINE=InnoDB";

    $pdo->exec($sql);
    echo "Table 'products' created successfully.<br>";
    
    // Add stock column to products table if it doesn't exist
    $check_stock = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    if ($check_stock->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0");
        echo "Column 'stock' added to 'products' table.<br>";
    }

    // Add status column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "Added status column to products table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Status column note: " . $e->getMessage() . "<br>";
        }
    }

    // Add submitted_by column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN submitted_by INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE products ADD FOREIGN KEY (submitted_by) REFERENCES users(id)");
        echo "Added submitted_by column to products table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "submitted_by column note: " . $e->getMessage() . "<br>";
        }
    }

    // Add admin_notes column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN admin_notes TEXT");
        echo "Added admin_notes column to products table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "admin_notes column note: " . $e->getMessage() . "<br>";
        }
    }

    // Add sizes column to products table if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN sizes VARCHAR(255) AFTER category");
        echo "Added sizes column to products table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Sizes column note: " . $e->getMessage() . "<br>";
        }
    }

    // 4.5 Create Product Size Stock Table
    $sql = "CREATE TABLE IF NOT EXISTS product_size_stock (
        product_id INT NOT NULL,
        size VARCHAR(20) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        PRIMARY KEY (product_id, size),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'product_size_stock' created successfully.<br>";

// 4. Create Orders Table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        shipping DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_method VARCHAR(50) NOT NULL,
        shipping_first_name VARCHAR(100) NOT NULL,
        shipping_last_name VARCHAR(100) NOT NULL,
        shipping_address TEXT NOT NULL,
        shipping_city VARCHAR(100) NOT NULL,
        shipping_zip VARCHAR(20) NOT NULL,
        shipping_country VARCHAR(100) NOT NULL,
        shipping_phone VARCHAR(20) NOT NULL,
        shipping_email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'orders' created successfully.<br>";

    // Create Shipping Addresses Table
    $sql = "CREATE TABLE IF NOT EXISTS shipping_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(50) DEFAULT 'Home',
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        zip VARCHAR(20) NOT NULL,
        country VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'shipping_addresses' created successfully.<br>";

    // 5. Create Order Items Table
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        size VARCHAR(20),
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'order_items' created successfully.<br>";

    // Add size column to order_items if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN size VARCHAR(20) AFTER product_name");
        echo "Added size column to order_items table.<br>";
    } catch (PDOException $e) {
        // Column might already exist, ignore error
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Note: " . $e->getMessage() . "<br>";
        } else {
            echo "Size column already exists in order_items table.<br>";
        }
    }

    // 6. Seed Categories if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            ['name' => 'Tops', 'slug' => 'tops', 'image' => 'assets/images/top.png'],
            ['name' => 'Bottoms', 'slug' => 'bottoms', 'image' => 'assets/images/bottom.png'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'image' => 'assets/images/shoes.jpeg'],
            ['name' => 'Jackets', 'slug' => 'jackets', 'image' => 'assets/images/jacket.png'],
            ['name' => 'Hats', 'slug' => 'hats', 'image' => 'assets/images/hat.png'],
            ['name' => 'Perfumes', 'slug' => 'perfumes', 'image' => 'assets/images/parfum.png'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'image' => 'assets/images/accessories-removebg-preview.png'],
            ['name' => 'Football Jerseys', 'slug' => 'football_jerseys', 'image' => 'assets/images/fbkits.png'],
        ];
        
        $insert_sql = "INSERT INTO categories (name, slug, image) VALUES (:name, :slug, :image)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        foreach ($categories as $cat) {
            $insert_stmt->execute($cat);
        }
        echo "Seeded " . count($categories) . " categories successfully.<br>";
    } else {
        echo "Categories already exist. Skipping seed.<br>";
    }

    // Create Wishlist Table
    $sql = "CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, product_id)
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'wishlist' created successfully.<br>";

    // Create Contact Messages Table
    $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'contact_messages' created successfully.<br>";

    // Create Social Links Table
    $sql = "CREATE TABLE IF NOT EXISTS social_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(50) NOT NULL UNIQUE,
        url VARCHAR(500) NOT NULL,
        order_num INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'social_links' created successfully.<br>";

    // Seed Social Links if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM social_links");
    if ($stmt->fetchColumn() == 0) {
        $socials = [
            ['platform' => 'Instagram', 'url' => 'https://instagram.com/sznstore', 'order_num' => 1],
            ['platform' => 'Facebook', 'url' => 'https://facebook.com/sznstore', 'order_num' => 2],
            ['platform' => 'Twitter', 'url' => 'https://twitter.com/sznstore', 'order_num' => 3],
            ['platform' => 'TikTok', 'url' => 'https://tiktok.com/@sznstore', 'order_num' => 4],
            ['platform' => 'YouTube', 'url' => 'https://youtube.com/@sznstore', 'order_num' => 5]
        ];
        
        $insert_sql = "INSERT INTO social_links (platform, url, order_num) VALUES (:platform, :url, :order_num)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        foreach ($socials as $social) {
            $insert_stmt->execute($social);
        }
        echo "Seeded " . count($socials) . " social links successfully.<br>";
    } else {
        echo "Social links already exist. Skipping seed.<br>";
    }


    // 7. Check if products exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() > 0) {
        echo "Products already exist. Skipping seed.<br>";
    } else {
// 4. Seed Data
        $products = [
            [
                'name' => 'Ribbed Cashmere Sweater',
                'description' => 'Luxuriously soft cashmere sweater with a timeless ribbed texture.',
                'price' => 485.00,
                'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&auto=format&fit=crop',
                'category' => 'tops'
            ],
            [
                'name' => 'Silk Button-Down Shirt',
                'description' => 'Classic silk shirt suitable for office or evening wear.',
                'price' => 250.00,
                'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop',
                'category' => 'tops'
            ],
            [
                'name' => 'Tailored Wool Trousers',
                'description' => 'High-waisted wool trousers with a perfect straight-leg cut.',
                'price' => 320.00,
                'image' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=800&auto=format&fit=crop',
                'category' => 'bottoms'
            ],
            [
                'name' => 'Pleated Midi Skirt',
                'description' => 'Flowy midi skirt that moves beautifully with every step.',
                'price' => 180.00,
                'image' => 'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?w=800&auto=format&fit=crop',
                'category' => 'bottoms'
            ],
            [
                'name' => 'Leather Ankle Boots',
                'description' => 'Handcrafted leather boots with a comfortable block heel.',
                'price' => 450.00,
                'image' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=800&auto=format&fit=crop',
                'category' => 'shoes'
            ],
            [
                'name' => 'Minimalist Sneakers',
                'description' => 'Clean white sneakers for an effortless casual look.',
                'price' => 195.00,
                'image' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop',
                'category' => 'shoes'
            ],
            [
                'name' => 'Gold Hoop Earrings',
                'description' => 'Classic gold hoops that go with everything.',
                'price' => 120.00,
                'image' => 'https://images.unsplash.com/photo-1635767798638-3e25230163dc?w=800&auto=format&fit=crop',
                'category' => 'accessories'
            ],
            [
                'name' => 'Structure Leather Tote',
                'description' => 'Spacious tote bag made from premium vegetable-tanned leather.',
                'price' => 550.00,
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=800&auto=format&fit=crop',
                'category' => 'accessories'
            ],
            // Jackets
            [
                'name' => 'Wool Blend Overcoat',
                'description' => 'Classic wool blend overcoat for the modern gentleman.',
                'price' => 850.00,
                'image' => 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?w=800&auto=format&fit=crop',
                'category' => 'jackets'
            ],
            [
                'name' => 'Leather Biker Jacket',
                'description' => 'Premium leather biker jacket with asymmetric zip.',
                'price' => 650.00,
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=800&auto=format&fit=crop',
                'category' => 'jackets'
            ],
            // Football Jerseys
            [
                'name' => 'Real Madrid Home Jersey 2024',
                'description' => 'Official Real Madrid home jersey with player version.',
                'price' => 299.00,
                'image' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop',
                'category' => 'football_jerseys'
            ],
            [
                'name' => 'Barcelona Away Jersey 2024',
                'description' => 'Official Barcelona away jersey with authentic details.',
                'price' => 289.00,
                'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop',
                'category' => 'football_jerseys'
            ],
            // Perfumes
            [
                'name' => 'Santal 33 Le Labo',
                'description' => 'Iconic sandalwood fragrance with cedar and violet.',
                'price' => 680.00,
                'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=800&auto=format&fit=crop',
                'category' => 'perfumes'
            ],
            [
                'name' => 'Aventus Creed',
                'description' => 'Legendary masculine fragrance with pineapple and birch.',
                'price' => 950.00,
                'image' => 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800&auto=format&fit=crop',
                'category' => 'perfumes'
            ],
            // Hats
            [
                'name' => 'Wool Fedora Hat',
                'description' => 'Classic wool fedora in timeless black.',
                'price' => 150.00,
                'image' => 'https://images.unsplash.com/photo-1514327605112-b887c0e61c0a?w=800&auto=format&fit=crop',
                'category' => 'hats'
            ],
            [
                'name' => 'Leather Baseball Cap',
                'description' => 'Premium leather baseball cap with adjustable strap.',
                'price' => 120.00,
                'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&auto=format&fit=crop',
                'category' => 'hats'
            ]
        ];

        $insert_sql = "INSERT INTO products (name, description, price, image, category) VALUES (:name, :description, :price, :image, :category)";
        $insert_stmt = $pdo->prepare($insert_sql);

        foreach ($products as $product) {
            $insert_stmt->execute($product);
        }
        echo "seeded " . count($products) . " products successfully.<br>";

        // also create default admin account if none exists
        $adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($adminCheck->fetchColumn() == 0) {
            $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (first_name,last_name,email,password,role) VALUES ('Administrator','','admin@example.com',:pw,'admin')")->execute(['pw' => $adminPassword]);
            echo "default admin created (admin@example.com / Admin@123).<br>";
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

