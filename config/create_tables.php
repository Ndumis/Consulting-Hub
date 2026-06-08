<?php
require_once 'database.php';
require_once __DIR__ . '/../includes/functions.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

try {
    // Create users table
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'employee',
        department VARCHAR(20) DEFAULT 'IT',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create clients table
    $query = "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        company VARCHAR(100),
        address TEXT,
        status VARCHAR(20) DEFAULT 'prospect',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create projects table with comments support
    $query = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        client_id INT,
        category VARCHAR(30) DEFAULT 'Web Dev',
        priority VARCHAR(20) DEFAULT 'medium',
        progress INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'pending',
        start_date DATE,
        end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create project_assignments table (for multiple employee assignment)
    $query = "CREATE TABLE IF NOT EXISTS project_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT,
        user_id INT,
        role VARCHAR(50) DEFAULT 'Developer',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(project_id, user_id),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create project_comments table (chat-like functionality)
    $query = "CREATE TABLE IF NOT EXISTS project_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT,
        user_id INT,
        comment TEXT NOT NULL,
        is_blocker TINYINT(1) DEFAULT 0,
        parent_comment_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_comment_id) REFERENCES project_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create marketing_campaigns table
    $query = "CREATE TABLE IF NOT EXISTS marketing_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        type VARCHAR(30) DEFAULT 'Social Media',
        status VARCHAR(20) DEFAULT 'planning',
        budget DECIMAL(10,2) DEFAULT 0.00,
        spent DECIMAL(10,2) DEFAULT 0.00,
        target_audience TEXT,
        start_date DATE,
        end_date DATE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create social_media_posts table
    $query = "CREATE TABLE IF NOT EXISTS social_media_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT,
        platform VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        image_url VARCHAR(255),
        scheduled_for TIMESTAMP,
        status VARCHAR(20) DEFAULT 'draft',
        engagement_stats JSON,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create email_campaigns table
    $query = "CREATE TABLE IF NOT EXISTS email_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        marketing_campaign_id INT,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        recipient_list TEXT,
        send_date TIMESTAMP,
        status VARCHAR(20) DEFAULT 'draft',
        sent_count INT DEFAULT 0,
        open_count INT DEFAULT 0,
        click_count INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (marketing_campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create employees table
    $query = "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        department VARCHAR(30) DEFAULT 'IT',
        position VARCHAR(50),
        salary DECIMAL(10,2),
        hire_date DATE,
        status VARCHAR(20) DEFAULT 'active',
        manager_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create leave_requests table
    $query = "CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT,
        leave_type VARCHAR(30) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        days_requested INT NOT NULL,
        reason TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        approved_by INT DEFAULT NULL,
        approved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create quotations table
    $query = "CREATE TABLE IF NOT EXISTS quotations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quotation_number VARCHAR(50) UNIQUE NOT NULL,
        client_id INT,
        project_id INT DEFAULT NULL,
        quotation_date DATE NOT NULL,
        valid_until DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'draft',
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        vat_rate DECIMAL(4,4) NOT NULL DEFAULT 0.1500,
        vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        converted_invoice_id INT DEFAULT NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create quotation_items table
    $query = "CREATE TABLE IF NOT EXISTS quotation_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quotation_id INT,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create invoices table
    $query = "CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        quotation_id INT DEFAULT NULL,
        client_id INT,
        project_id INT DEFAULT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'draft',
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        vat_rate DECIMAL(4,4) NOT NULL DEFAULT 0.1500,
        vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        paid_amount DECIMAL(10,2) DEFAULT 0.00,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create invoice_items table
    $query = "CREATE TABLE IF NOT EXISTS invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create purchase_orders table
    $query = "CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(50) UNIQUE NOT NULL,
        supplier_name VARCHAR(100) NOT NULL,
        supplier_email VARCHAR(100),
        supplier_phone VARCHAR(20),
        project_id INT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        order_date DATE NOT NULL,
        expected_delivery DATE,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        vat_rate DECIMAL(4,4) NOT NULL DEFAULT 0.1500,
        vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create purchase_order_items table
    $query = "CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create money_flow table
    $query = "CREATE TABLE IF NOT EXISTS money_flow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_type VARCHAR(20) NOT NULL,
        category VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        transaction_date DATE NOT NULL,
        client_id INT DEFAULT NULL,
        project_id INT DEFAULT NULL,
        invoice_id INT DEFAULT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create calendar_events table
    $query = "CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME DEFAULT NULL,
        client_id INT DEFAULT NULL,
        project_id INT DEFAULT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Create user_activities table for activity tracking
    $query = "CREATE TABLE IF NOT EXISTS user_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(100),
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        page_url VARCHAR(500),
        resource_type VARCHAR(100),
        resource_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        session_id VARCHAR(100),
        additional_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_activity_type (activity_type),
        INDEX idx_created_at (created_at),
        INDEX idx_resource (resource_type, resource_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    // Also create system_activity table for backwards compatibility
    $query = "CREATE TABLE IF NOT EXISTS system_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        department VARCHAR(50) NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_activity (user_id, created_at),
        INDEX idx_department_activity (department, created_at),
        INDEX idx_activity_type (activity_type, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($query);

    echo "All tables created successfully!\n";

    // Insert default admin user if not exists
    $query = "INSERT IGNORE INTO users (username, email, password, role, department) 
              VALUES ('admin', 'admin@company.com', ?, 'admin', 'IT')";
    $stmt = $conn->prepare($query);
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute([$hashedPassword]);

    // Insert sample data
    insertSampleData($conn);

    echo "Database setup completed with sample data!\n";

} catch (Exception $exception) {
    echo "Error: " . $exception->getMessage();
}

function insertSampleData($conn) {
    // Sample clients
    $query = "INSERT IGNORE INTO clients (id, name, email, company, status) VALUES 
              (1, 'TechCorp', 'contact@techcorp.com', 'TechCorp Solutions Inc.', 'active'),
              (2, 'GreenEnergy', 'info@greenenergy.com', 'GreenEnergy Solutions', 'active'),
              (3, 'RetailPlus', 'sales@retailplus.com', 'RetailPlus Ltd', 'prospect')";
    $conn->exec($query);

    // Sample quotations
    $query = "INSERT IGNORE INTO quotations (id, quotation_number, client_id, quotation_date, valid_until, status, subtotal, vat_rate, vat_amount, total_amount, notes, created_by) VALUES 
              (4, 'QUO-2025-TEST', 1, '2025-09-11', '2025-10-11', 'completed', 2000.00, 0.1500, 300.00, 2300.00, 'Test quotation for conversion demo', 1)";
    $conn->exec($query);

    // Sample quotation items
    $query = "INSERT IGNORE INTO quotation_items (quotation_id, description, quantity, unit_price, total_price) VALUES 
              (4, 'Software Development - Phase 1', 1.00, 1500.00, 1500.00),
              (4, 'Testing & QA Services', 1.00, 500.00, 500.00)";
    $conn->exec($query);

    // Sample invoices
    $query = "INSERT IGNORE INTO invoices (id, invoice_number, quotation_id, client_id, invoice_date, due_date, status, subtotal, vat_rate, vat_amount, total_amount, notes, created_by) VALUES 
              (2, 'INV-2025-DEMO', 4, 1, '2025-09-11', '2025-10-11', 'draft', 2000.00, 0.1500, 300.00, 2300.00, 'Demo invoice converted from QUO-2025-TEST', 1)";
    $conn->exec($query);

    // Update quotation with converted invoice ID
    $query = "UPDATE quotations SET converted_invoice_id = 2 WHERE id = 4";
    $conn->exec($query);

    // Sample invoice items
    $query = "INSERT IGNORE INTO invoice_items (invoice_id, description, quantity, unit_price, total_price) VALUES 
              (2, 'Software Development - Phase 1', 1.00, 1500.00, 1500.00),
              (2, 'Testing & QA Services', 1.00, 500.00, 500.00)";
    $conn->exec($query);

    // Sample purchase order
    $query = "INSERT IGNORE INTO purchase_orders (id, po_number, supplier_name, order_date, expected_delivery, status, subtotal, vat_rate, vat_amount, total_amount, notes, created_by) VALUES 
              (1, 'PO-2025-001', 'Office Supplies Ltd', '2025-09-11', '2025-09-18', 'approved', 1800.00, 0.1500, 270.00, 2070.00, 'Monthly office supplies and equipment for the team', 1)";
    $conn->exec($query);

    // Sample purchase order items
    $query = "INSERT IGNORE INTO purchase_order_items (purchase_order_id, description, quantity, unit_price, total_price) VALUES 
              (1, 'Ergonomic Office Chairs', 6.00, 150.00, 900.00),
              (1, 'Standing Desk Converters', 4.00, 125.00, 500.00),
              (1, 'Monitor Arms (Dual)', 5.00, 80.00, 400.00)";
    $conn->exec($query);

    echo "Sample data inserted successfully!\n";
}
?>