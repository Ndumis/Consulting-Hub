<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/email_service.php';
require_once '../includes/page_tracker.php';

// Check department access
Security::requireDepartmentAccess('Finance');

$database = new Database();
$db = $database->getConnection();

// Handle AJAX requests FIRST - before any authentication redirects
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Check if user is logged in for AJAX requests
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    switch ($_GET['ajax']) {
        case 'view_quotation':
            $quotation_id = (int)$_GET['id'];
            $query = "SELECT q.*, c.name as client_name, c.company 
                      FROM quotations q 
                      LEFT JOIN clients c ON q.client_id = c.id 
                      WHERE q.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$quotation_id]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quotation) {
                // Get quotation items
                $items_query = "SELECT * FROM quotation_items WHERE quotation_id = ?";
                $items_stmt = $db->prepare($items_query);
                $items_stmt->execute([$quotation_id]);
                $quotation['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $quotation]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quotation not found']);
            }
            exit;
            
        case 'view_invoice':
            $invoice_id = (int)$_GET['id'];
            $query = "SELECT i.*, c.name as client_name, c.company 
                      FROM invoices i 
                      LEFT JOIN clients c ON i.client_id = c.id 
                      WHERE i.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice) {
                // Get invoice items
                $items_query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
                $items_stmt = $db->prepare($items_query);
                $items_stmt->execute([$invoice_id]);
                $invoice['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $invoice]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            }
            exit;
            
        case 'view_purchase_order':
            $po_id = (int)$_GET['id'];
            $query = "SELECT po.*, p.name as project_name 
                      FROM purchase_orders po 
                      LEFT JOIN projects p ON po.project_id = p.id 
                      WHERE po.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$po_id]);
            $purchase_order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($purchase_order) {
                // Get purchase order items
                $items_query = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
                $items_stmt = $db->prepare($items_query);
                $items_stmt->execute([$po_id]);
                $purchase_order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $purchase_order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Purchase order not found']);
            }
            exit;
    }
}

// Handle quotation update
if ($_POST && isset($_POST['update_quotation'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $quotation_id = (int)$_POST['quotation_id'];
    $client_id = (int)$_POST['client_id'];
    $quotation_date = Security::sanitizeInput($_POST['quotation_date']);
    $valid_until = Security::sanitizeInput($_POST['valid_until']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    try {
        $db->beginTransaction();
        
        // Admins/managers can edit any quotation; others only their own
        $role = $_SESSION['role'] ?? '';
        if (in_array($role, ['admin', 'manager'])) {
            $q2 = "SELECT * FROM quotations WHERE id = ? AND status NOT IN ('converted', 'completed')";
            $stmt = $db->prepare($q2); $stmt->execute([$quotation_id]);
        } else {
            $q2 = "SELECT * FROM quotations WHERE id = ? AND created_by = ? AND status NOT IN ('converted', 'completed')";
            $stmt = $db->prepare($q2); $stmt->execute([$quotation_id, $_SESSION['user_id']]);
        }
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Quotation not found or cannot be updated');
        }
        
        // Update quotation basic info
        $query = "UPDATE quotations SET client_id = ?, quotation_date = ?, valid_until = ?, vat_rate = ?, 
                  notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $quotation_date, $valid_until, $vat_rate, $notes, $quotation_id]);
        
        // Delete existing items
        $query = "DELETE FROM quotation_items WHERE quotation_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$quotation_id]);
        
        // Process updated quotation items
        $subtotal = 0;
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $description = Security::sanitizeInput($item['description']);
                    $quantity = floatval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $total_price = $quantity * $unit_price;
                    $subtotal += $total_price;
                    
                    $item_query = "INSERT INTO quotation_items (quotation_id, description, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->execute([$quotation_id, $description, $quantity, $unit_price, $total_price]);
                }
            }
        }
        
        // Update quotation totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE quotations SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $quotation_id]);
        
        $db->commit();
        
        // Send automated email notification
        try {
            $query = "SELECT q.*, c.email as client_email FROM quotations q 
                      LEFT JOIN clients c ON q.client_id = c.id WHERE q.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$quotation_id]);
            $quotation_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quotation_data && !empty($quotation_data['client_email'])) {
                EmailService::sendQuotationEmail($quotation_data, $quotation_data['client_email']);
            }
        } catch (Exception $email_error) {
            error_log("Email sending failed: " . $email_error->getMessage());
        }
        
        header('Location: finance.php?view=quotations&msg=' . urlencode('Quotation updated successfully.'));
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        header('Location: finance.php?view=quotations&err=' . urlencode('Update failed: ' . $e->getMessage()));
        exit();
    }
}

// Handle new quotation creation
if ($_POST && isset($_POST['create_quotation'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $client_id = (int)$_POST['client_id'];
    $quotation_date = Security::sanitizeInput($_POST['quotation_date']);
    $valid_until = Security::sanitizeInput($_POST['valid_until']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    $quotation_number = Utils::generateQuotationNumber();
    
    $query = "INSERT INTO quotations (quotation_number, client_id, quotation_date, valid_until, vat_rate, notes, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->execute([$quotation_number, $client_id, $quotation_date, $valid_until, $vat_rate, $notes, $_SESSION['user_id']]);
    
    $quotation_id = $stmt->fetchColumn();
    
    // Process quotation items
    if (!empty($_POST['items'])) {
        $subtotal = 0;
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                $description = Security::sanitizeInput($item['description']);
                $quantity = floatval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = $quantity * $unit_price;
                $subtotal += $total_price;
                
                $item_query = "INSERT INTO quotation_items (quotation_id, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([$quotation_id, $description, $quantity, $unit_price, $total_price]);
            }
        }
        
        // Update quotation totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE quotations SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $quotation_id]);
    }
    header('Location: finance.php?view=quotations&msg=' . urlencode("Quotation {$quotation_number} created successfully."));
    exit();
}

// Handle quotation to invoice conversion
if ($_POST && isset($_POST['convert_to_invoice'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $quotation_id = (int)$_POST['quotation_id'];
    
    try {
        // Begin transaction for data integrity
        $db->beginTransaction();
        
        // Get quotation details with exclusive lock
        $query = "SELECT q.*, qi.* FROM quotations q 
                  LEFT JOIN quotation_items qi ON q.id = qi.quotation_id 
                  WHERE q.id = ? AND q.status = 'accepted'
                  FOR UPDATE";
        $stmt = $db->prepare($query);
        $stmt->execute([$quotation_id]);
        $quotation_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($quotation_data)) {
            throw new Exception('Quotation not found or not in accepted status');
        }
        
        $quotation = $quotation_data[0];
        
        // Generate invoice number and set dates
        $invoice_number = Utils::generateInvoiceNumber();
        $invoice_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+30 days')); // 30 days payment terms
        
        // Create invoice from quotation
        $query = "INSERT INTO invoices (invoice_number, quotation_id, client_id, project_id, invoice_date, due_date, 
                  vat_rate, subtotal, vat_amount, total_amount, notes, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $invoice_number, $quotation_id, $quotation['client_id'], $quotation['project_id'],
            $invoice_date, $due_date, $quotation['vat_rate'], $quotation['subtotal'],
            $quotation['vat_amount'], $quotation['total_amount'], 
            "Converted from quotation: " . $quotation['quotation_number'], $_SESSION['user_id']
        ]);
        
        $invoice_id = $stmt->fetchColumn();
        if (!$invoice_id) {
            throw new Exception('Failed to create invoice');
        }
        
        // Copy quotation items to invoice items
        $item_count = 0;
        foreach ($quotation_data as $item) {
            if ($item['description']) { // Only items with description (not the main quotation record)
                $query = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total_price) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$invoice_id, $item['description'], $item['quantity'], $item['unit_price'], $item['total_price']]);
                $item_count++;
            }
        }
        
        if ($item_count === 0) {
            throw new Exception('No items found to convert');
        }
        
        // Update quotation status to completed
        $query = "UPDATE quotations SET status = 'completed', converted_invoice_id = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$invoice_id, $quotation_id]);
        
        // Record in money flow as pending income
        $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, 
                  client_id, project_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'income', 'Invoice', $quotation['total_amount'],
            "Invoice {$invoice_number} (from quotation {$quotation['quotation_number']})",
            $invoice_date, $quotation['client_id'], $quotation['project_id'], $_SESSION['user_id']
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Send automated email notification for new invoice
        try {
            $query = "SELECT i.*, c.email as client_email FROM invoices i 
                      LEFT JOIN clients c ON i.client_id = c.id WHERE i.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$invoice_id]);
            $invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice_data && !empty($invoice_data['client_email'])) {
                EmailService::sendInvoiceEmail($invoice_data, $invoice_data['client_email']);
            }
        } catch (Exception $email_error) {
            error_log("Email sending failed: " . $email_error->getMessage());
        }
        
        header('Location: finance.php?view=invoices&msg=' . urlencode("Quotation converted to Invoice {$invoice_number} successfully."));
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        header('Location: finance.php?view=quotations&err=' . urlencode('Conversion failed: ' . $e->getMessage()));
        exit();
    }
}

// Handle invoice update
if ($_POST && isset($_POST['update_invoice'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $invoice_id = (int)$_POST['invoice_id'];
    $client_id = (int)$_POST['client_id'];
    $invoice_date = Security::sanitizeInput($_POST['invoice_date']);
    $due_date = Security::sanitizeInput($_POST['due_date']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    try {
        $db->beginTransaction();
        
        // Admins/managers can edit any invoice; others only their own (paid invoices always locked)
        $role = $_SESSION['role'] ?? '';
        if (in_array($role, ['admin', 'manager'])) {
            $q2 = "SELECT * FROM invoices WHERE id = ? AND status != 'paid'";
            $stmt = $db->prepare($q2); $stmt->execute([$invoice_id]);
        } else {
            $q2 = "SELECT * FROM invoices WHERE id = ? AND created_by = ? AND status != 'paid'";
            $stmt = $db->prepare($q2); $stmt->execute([$invoice_id, $_SESSION['user_id']]);
        }
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Invoice not found or cannot be updated (paid invoices are locked)');
        }
        
        // Update invoice basic info
        $query = "UPDATE invoices SET client_id = ?, invoice_date = ?, due_date = ?, vat_rate = ?, 
                  notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $invoice_date, $due_date, $vat_rate, $notes, $invoice_id]);
        
        // Delete existing items
        $query = "DELETE FROM invoice_items WHERE invoice_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$invoice_id]);
        
        // Process updated invoice items
        $subtotal = 0;
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $description = Security::sanitizeInput($item['description']);
                    $quantity = floatval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $total_price = $quantity * $unit_price;
                    $subtotal += $total_price;
                    
                    $item_query = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->execute([$invoice_id, $description, $quantity, $unit_price, $total_price]);
                }
            }
        }
        
        // Update invoice totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE invoices SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $invoice_id]);
        
        $db->commit();
        header('Location: finance.php?view=invoices&msg=' . urlencode('Invoice updated successfully.'));
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        header('Location: finance.php?view=invoices&err=' . urlencode('Update failed: ' . $e->getMessage()));
        exit();
    }
}

// Handle new invoice creation
if ($_POST && isset($_POST['create_invoice'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $client_id = (int)$_POST['client_id'];
    $invoice_date = Security::sanitizeInput($_POST['invoice_date']);
    $due_date = Security::sanitizeInput($_POST['due_date']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    $invoice_number = Utils::generateInvoiceNumber();
    
    $query = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, vat_rate, notes, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->execute([$invoice_number, $client_id, $invoice_date, $due_date, $vat_rate, $notes, $_SESSION['user_id']]);
    
    $invoice_id = $stmt->fetchColumn();
    
    // Process invoice items
    if (!empty($_POST['items'])) {
        $subtotal = 0;
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                $description = Security::sanitizeInput($item['description']);
                $quantity = floatval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = $quantity * $unit_price;
                $subtotal += $total_price;
                
                $item_query = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([$invoice_id, $description, $quantity, $unit_price, $total_price]);
            }
        }
        
        // Update invoice totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE invoices SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $invoice_id]);
        
        // Send automated email notification for new invoice
        try {
            $query = "SELECT i.*, c.email as client_email FROM invoices i 
                      LEFT JOIN clients c ON i.client_id = c.id WHERE i.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$invoice_id]);
            $invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice_data && !empty($invoice_data['client_email'])) {
                EmailService::sendInvoiceEmail($invoice_data, $invoice_data['client_email']);
            }
        } catch (Exception $email_error) {
            error_log("Email sending failed: " . $email_error->getMessage());
        }
        
        header('Location: finance.php?view=invoices&msg=' . urlencode("Invoice {$invoice_number} created successfully."));
        exit();
    }
}

// Handle payment recording
if ($_POST && isset($_POST['record_payment'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_amount = floatval($_POST['payment_amount']);
    
    // Get current invoice
    $query = "SELECT * FROM invoices WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invoice) {
        $new_paid_amount = $invoice['paid_amount'] + $payment_amount;
        $status = ($new_paid_amount >= $invoice['total_amount']) ? 'paid' : 'partially_paid';

        $db->prepare("UPDATE invoices SET paid_amount = ?, status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$new_paid_amount, $status, $invoice_id]);

        // Record in money flow
        $db->prepare("INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, client_id, invoice_id, created_by)
                      VALUES ('income', 'Payment', ?, ?, ?, ?, ?, ?)")
           ->execute([$payment_amount, "Payment for invoice {$invoice['invoice_number']}", date('Y-m-d'), $invoice['client_id'], $invoice_id, $_SESSION['user_id']]);
    }
    header('Location: finance.php?view=invoices&msg=' . urlencode('Payment recorded successfully.'));
    exit();
}

// Handle purchase order update
if ($_POST && isset($_POST['update_purchase_order'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $po_id = (int)$_POST['po_id'];
    $supplier_name = Security::sanitizeInput($_POST['supplier_name']);
    $supplier_email = Security::sanitizeInput($_POST['supplier_email']);
    $supplier_phone = Security::sanitizeInput($_POST['supplier_phone']);
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $order_date = Security::sanitizeInput($_POST['order_date']);
    $expected_delivery = Security::sanitizeInput($_POST['expected_delivery']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    try {
        $db->beginTransaction();
        
        // Verify purchase order exists and can be updated (not completed)
        $query = "SELECT * FROM purchase_orders WHERE id = ? AND created_by = ? AND status != 'completed'";
        $stmt = $db->prepare($query);
        $stmt->execute([$po_id, $_SESSION['user_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            throw new Exception('Purchase order not found or cannot be updated (completed orders are locked)');
        }
        
        // Update purchase order basic info
        $query = "UPDATE purchase_orders SET supplier_name = ?, supplier_email = ?, supplier_phone = ?, 
                  project_id = ?, order_date = ?, expected_delivery = ?, vat_rate = ?, notes = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$supplier_name, $supplier_email, $supplier_phone, $project_id, $order_date, 
                       $expected_delivery, $vat_rate, $notes, $po_id]);
        
        // Delete existing items
        $query = "DELETE FROM purchase_order_items WHERE purchase_order_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$po_id]);
        
        // Process updated purchase order items
        $subtotal = 0;
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $description = Security::sanitizeInput($item['description']);
                    $quantity = floatval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $total_price = $quantity * $unit_price;
                    $subtotal += $total_price;
                    
                    $item_query = "INSERT INTO purchase_order_items (purchase_order_id, description, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->execute([$po_id, $description, $quantity, $unit_price, $total_price]);
                }
            }
        }
        
        // Update purchase order totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE purchase_orders SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $po_id]);
        
        $db->commit();
        header('Location: finance.php?view=purchase_orders&msg=' . urlencode('Purchase order updated successfully.'));
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        header('Location: finance.php?view=purchase_orders&err=' . urlencode('Update failed: ' . $e->getMessage()));
        exit();
    }
}

// Handle new purchase order creation
if ($_POST && isset($_POST['create_purchase_order'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $supplier_name = Security::sanitizeInput($_POST['supplier_name']);
    $supplier_email = Security::sanitizeInput($_POST['supplier_email']);
    $supplier_phone = Security::sanitizeInput($_POST['supplier_phone']);
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $order_date = Security::sanitizeInput($_POST['order_date']);
    $expected_delivery = Security::sanitizeInput($_POST['expected_delivery']);
    $notes = Security::sanitizeInput($_POST['notes']);
    $vat_rate = floatval($_POST['vat_rate']) / 100;
    
    $po_number = Utils::generatePONumber();
    
    $query = "INSERT INTO purchase_orders (po_number, supplier_name, supplier_email, supplier_phone, project_id, 
              order_date, expected_delivery, vat_rate, notes, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->execute([$po_number, $supplier_name, $supplier_email, $supplier_phone, $project_id, 
                   $order_date, $expected_delivery, $vat_rate, $notes, $_SESSION['user_id']]);
    
    $po_id = $stmt->fetchColumn();
    
    // Process purchase order items
    if (!empty($_POST['items'])) {
        $subtotal = 0;
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                $description = Security::sanitizeInput($item['description']);
                $quantity = floatval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = $quantity * $unit_price;
                $subtotal += $total_price;
                
                $item_query = "INSERT INTO purchase_order_items (purchase_order_id, description, quantity, unit_price, total_price) 
                              VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([$po_id, $description, $quantity, $unit_price, $total_price]);
            }
        }
        
        // Update purchase order totals
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;
        
        $update_query = "UPDATE purchase_orders SET subtotal = ?, vat_amount = ?, total_amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$subtotal, $vat_amount, $total_amount, $po_id]);
        
        // Record in money flow (as expense)
        $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, purchase_order_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute(['expense', 'Purchase Order', $total_amount, "Purchase order {$po_number}",
                       $order_date, $po_id, $_SESSION['user_id']]);
    }
    header('Location: finance.php?view=purchase_orders&msg=' . urlencode("Purchase order {$po_number} created successfully."));
    exit();
}

// Handle purchase order status update
if ($_POST && isset($_POST['update_po_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $po_id = (int)$_POST['po_id'];
    $allowed_po = ['pending', 'approved', 'received', 'completed', 'cancelled'];
    $status = Security::sanitizeInput($_POST['status']);
    if ($po_id && in_array($status, $allowed_po)) {
        $db->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$status, $po_id]);
    }
    header('Location: finance.php?view=purchase_orders&msg=' . urlencode('Purchase order status updated.'));
    exit();
}

// Handle quotation status update
if ($_POST && isset($_POST['update_quotation_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $quotation_id = (int)$_POST['quotation_id'];
    $allowed = ['draft', 'sent', 'accepted', 'rejected'];
    $status = Security::sanitizeInput($_POST['status']);
    if ($quotation_id && in_array($status, $allowed)) {
        $db->prepare("UPDATE quotations SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$status, $quotation_id]);
    }
    header('Location: finance.php?view=quotations&msg=' . urlencode('Quotation status updated to ' . ucfirst($status) . '.'));
    exit();
}

// Handle invoice status update
if ($_POST && isset($_POST['update_invoice_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $invoice_id = (int)$_POST['invoice_id'];
    $allowed = ['draft', 'sent', 'pending', 'overdue', 'cancelled'];
    $status = Security::sanitizeInput($_POST['status']);
    if ($invoice_id && in_array($status, $allowed)) {
        $db->prepare("UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$status, $invoice_id]);
    }
    header('Location: finance.php?view=invoices&msg=' . urlencode('Invoice status updated to ' . ucfirst($status) . '.'));
    exit();
}

// Handle new project revenue recording
if ($_POST && isset($_POST['record_project_revenue'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $project_id = (int)$_POST['project_id'];
    $client_id = (int)$_POST['client_id'];
    $revenue_type = Security::sanitizeInput($_POST['revenue_type']);
    $amount = floatval($_POST['amount']);
    $received_date = Security::sanitizeInput($_POST['received_date']);
    $payment_method = Security::sanitizeInput($_POST['payment_method']);
    $reference_number = Security::sanitizeInput($_POST['reference_number']);
    $notes = Security::sanitizeInput($_POST['notes']);
    
    $query = "INSERT INTO project_revenues (project_id, client_id, revenue_type, amount, received_date, 
              payment_method, reference_number, notes, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$project_id, $client_id, $revenue_type, $amount, $received_date, 
                   $payment_method, $reference_number, $notes, $_SESSION['user_id']]);
    
    // Record in money flow
    $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, 
              project_id, client_id, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute(['income', ucfirst($revenue_type), $amount, $notes ?: "Project revenue - {$revenue_type}", 
                   $received_date, $project_id, $client_id, $_SESSION['user_id']]);
}

// Handle update project revenue
if ($_POST && isset($_POST['update_project_revenue'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $revenue_id = (int)$_POST['revenue_id'];
    $project_id = (int)$_POST['project_id'];
    $client_id = (int)$_POST['client_id'];
    $revenue_type = Security::sanitizeInput($_POST['revenue_type']);
    $amount = floatval($_POST['amount']);
    $received_date = Security::sanitizeInput($_POST['received_date']);
    $payment_method = Security::sanitizeInput($_POST['payment_method']);
    $reference_number = Security::sanitizeInput($_POST['reference_number']);
    $notes = Security::sanitizeInput($_POST['notes']);
    
    // Get old revenue for money_flow update
    $query = "SELECT * FROM project_revenues WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$revenue_id, $_SESSION['user_id']]);
    $old_revenue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($old_revenue) {
        // Update project revenue
        $query = "UPDATE project_revenues SET project_id = ?, client_id = ?, revenue_type = ?, amount = ?, 
                  received_date = ?, payment_method = ?, reference_number = ?, notes = ?, updated_at = NOW() 
                  WHERE id = ? AND created_by = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$project_id, $client_id, $revenue_type, $amount, $received_date, 
                       $payment_method, $reference_number, $notes, $revenue_id, $_SESSION['user_id']]);
        
        // Update corresponding money_flow entry
        $query = "UPDATE money_flow
                  SET category = ?, amount = ?, description = ?, transaction_date = ?,
                      project_id = ?, client_id = ?
                  WHERE transaction_type = 'income'
                    AND project_id = ?
                    AND client_id = ?
                    AND amount = ?
                    AND DATE(transaction_date) = DATE(?)
                    AND created_by = ?
                  ORDER BY created_at DESC
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ucfirst($revenue_type),
            $amount,
            $notes ?: "Project revenue - {$revenue_type}",
            $received_date,
            $project_id,
            $client_id,
            $old_revenue['project_id'],
            $old_revenue['client_id'],
            $old_revenue['amount'],
            $old_revenue['received_date'],
            $_SESSION['user_id'],
        ]);
    }
}

// Handle delete project revenue
if ($_POST && isset($_POST['delete_project_revenue'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $revenue_id = (int)$_POST['revenue_id'];
    
    // Get project revenue record and verify ownership
    $query = "SELECT * FROM project_revenues WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$revenue_id, $_SESSION['user_id']]);
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revenue) {
        // Delete corresponding money_flow entry using CTE for precise targeting
        $query = "WITH target_row AS (
                    SELECT id FROM money_flow 
                    WHERE transaction_type = 'income' 
                      AND project_id = ? 
                      AND client_id = ? 
                      AND amount = ? 
                      AND DATE(transaction_date) = DATE(?)
                      AND created_by = ?
                    ORDER BY created_at DESC 
                    LIMIT 1
                  )
                  DELETE FROM money_flow 
                  WHERE id = (SELECT id FROM target_row)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $revenue['project_id'], 
            $revenue['client_id'], 
            $revenue['amount'], 
            $revenue['received_date'], 
            $_SESSION['user_id']
        ]);
        
        // Delete the project revenue record
        $query = "DELETE FROM project_revenues WHERE id = ? AND created_by = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$revenue_id, $_SESSION['user_id']]);
        
        Utils::logActivity($db, 'delete', "Deleted project revenue ID {$revenue_id}, amount R" . number_format($revenue['amount'], 2));
    }
    header('Location: finance.php?view=project_revenues&msg=' . urlencode('Revenue record deleted.'));
    exit();
}

// Handle manual money flow entry
if ($_POST && isset($_POST['add_money_flow'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');
    
    $transaction_type = Security::sanitizeInput($_POST['transaction_type']);
    $category = Security::sanitizeInput($_POST['category']);
    $amount = floatval($_POST['amount']);
    $description = Security::sanitizeInput($_POST['description']);
    $transaction_date = Security::sanitizeInput($_POST['transaction_date']);
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
    
    $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, 
              project_id, client_id, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_type, $category, $amount, $description, $transaction_date, 
                   $project_id, $client_id, $_SESSION['user_id']]);
}

// Handle new expense creation
if ($_POST && isset($_POST['create_expense'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $category    = Security::sanitizeInput($_POST['category']);
    $amount      = floatval($_POST['amount']);
    $expense_date = Security::sanitizeInput($_POST['expense_date']);
    $description = Security::sanitizeInput($_POST['description']);
    $project_id  = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

    $db->prepare("INSERT INTO expenses (category, description, amount, expense_date, project_id, status, submitted_by)
                  VALUES (?, ?, ?, ?, ?, 'pending', ?)")
       ->execute([$category, $description, $amount, $expense_date, $project_id, $_SESSION['user_id']]);

    // Record in money flow as a pending expense
    $db->prepare("INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, project_id, created_by)
                  VALUES ('expense', ?, ?, ?, ?, ?, ?)")
       ->execute([ucfirst(str_replace('_', ' ', $category)), $amount, "Expense: {$description}", $expense_date, $project_id, $_SESSION['user_id']]);

    header('Location: finance.php?view=expenses&msg=' . urlencode('Expense submitted for approval.'));
    exit();
}

// Handle expense status update (approve / reject)
if ($_POST && isset($_POST['update_expense_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $expense_id = (int)($_POST['expense_id'] ?? 0);
    $status     = Security::sanitizeInput($_POST['status'] ?? '');
    $allowed    = ['pending', 'approved', 'rejected'];
    if ($expense_id && in_array($status, $allowed)) {
        $db->prepare("UPDATE expenses SET status=?, approved_by=?, approved_at=NOW() WHERE id=?")
           ->execute([$status, $_SESSION['user_id'], $expense_id]);
    }
    header('Location: finance.php?view=expenses&msg=' . urlencode('Expense status updated.'));
    exit();
}


// Get current view parameter
$view = $_GET['view'] ?? 'money_flow';
$tab  = $_GET['view'] ?? 'finance';

// Flash messages passed via redirect (PRG pattern)
if (empty($success_message) && !empty($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
}
if (empty($error_message) && !empty($_GET['err'])) {
    $error_message = htmlspecialchars($_GET['err'], ENT_QUOTES, 'UTF-8');
}

// Get all data for display
$query = "SELECT q.*, c.name as client_name, c.company as client_company, u.username as created_by_name
          FROM quotations q 
          LEFT JOIN clients c ON q.client_id = c.id 
          LEFT JOIN users u ON q.created_by = u.id
          ORDER BY q.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT i.*, c.name as client_name, c.company as client_company, u.username as created_by_name,
          q.quotation_number as quotation_number
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          LEFT JOIN users u ON i.created_by = u.id
          LEFT JOIN quotations q ON i.quotation_id = q.id
          ORDER BY i.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT po.*, p.name as project_name, u.username as created_by_name
          FROM purchase_orders po 
          LEFT JOIN projects p ON po.project_id = p.id 
          LEFT JOIN users u ON po.created_by = u.id
          ORDER BY po.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT pr.*, p.name as project_name, c.name as client_name, u.username as created_by_name
          FROM project_revenues pr 
          LEFT JOIN projects p ON pr.project_id = p.id 
          LEFT JOIN clients c ON pr.client_id = c.id 
          LEFT JOIN users u ON pr.created_by = u.id
          ORDER BY pr.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$project_revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT e.*, p.name as project_name, u.username as submitted_by_name
          FROM expenses e 
          LEFT JOIN projects p ON e.project_id = p.id 
          LEFT JOIN users u ON e.submitted_by = u.id
          ORDER BY e.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT mf.*, p.name as project_name, c.name as client_name, u.username as created_by_name
          FROM money_flow mf 
          LEFT JOIN projects p ON mf.project_id = p.id 
          LEFT JOIN clients c ON mf.client_id = c.id 
          LEFT JOIN users u ON mf.created_by = u.id
          ORDER BY mf.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$money_flows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clients and projects for dropdowns
$query = "SELECT id, name, company FROM clients ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT id, name FROM projects ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate comprehensive statistics
$total_quotations = count($quotations);
$pending_quotations = count(array_filter($quotations, function($q) { return in_array($q['status'], ['draft', 'sent']); }));
$total_invoices = count($invoices);
$unpaid_invoices = count(array_filter($invoices, function($i) { return $i['status'] != 'paid'; }));
$total_purchase_orders = count($purchase_orders);
$pending_purchase_orders = count(array_filter($purchase_orders, function($po) { return $po['status'] == 'pending'; }));

// Revenue: sum of actual cash collected (paid_amount on invoices)
$total_revenue = array_sum(array_column($invoices, 'paid_amount'));

// Outstanding: balance owed on active invoices only (exclude paid & cancelled)
$outstanding_amount = array_sum(array_map(function($i) {
    return !in_array($i['status'], ['paid', 'cancelled']) ? max(0, $i['total_amount'] - $i['paid_amount']) : 0;
}, $invoices));

// Expenses: purchase orders + expenses table amounts
$po_expense_total  = array_sum(array_column($purchase_orders, 'total_amount'));
$exp_table_total   = array_sum(array_column($expenses, 'amount'));
$total_expenses    = $po_expense_total + $exp_table_total;

// Net P&L: cash collected minus total expenses incurred
$net_pl = $total_revenue - $total_expenses;

// Money flow statistics (for overview breakdown)
$total_income       = array_sum(array_map(function($mf) { return $mf['transaction_type'] === 'income'  ? $mf['amount'] : 0; }, $money_flows));
$total_expense_flow = array_sum(array_map(function($mf) { return $mf['transaction_type'] === 'expense' ? $mf['amount'] : 0; }, $money_flows));
$net_cash_flow      = $total_income - $total_expense_flow;

// Group money flows by category
$income_by_category = [];
$expense_by_category = [];
foreach ($money_flows as $mf) {
    if ($mf['transaction_type'] == 'income') {
        $income_by_category[$mf['category']] = ($income_by_category[$mf['category']] ?? 0) + $mf['amount'];
    } else {
        $expense_by_category[$mf['category']] = ($expense_by_category[$mf['category']] ?? 0) + $mf['amount'];
    }
}

// Recent activity (last 30 days)
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$recent_income = array_sum(array_map(function($mf) use ($thirty_days_ago) { 
    return ($mf['transaction_type'] == 'income' && $mf['transaction_date'] >= $thirty_days_ago) ? $mf['amount'] : 0; 
}, $money_flows));
$recent_expenses = array_sum(array_map(function($mf) use ($thirty_days_ago) { 
    return ($mf['transaction_type'] == 'expense' && $mf['transaction_date'] >= $thirty_days_ago) ? $mf['amount'] : 0; 
}, $money_flows));

// Monthly cash flow for chart — last 6 months from money_flow
$chart_raw = $db->query("
    SELECT DATE_FORMAT(transaction_date,'%b') AS lbl,
           DATE_FORMAT(transaction_date,'%Y-%m') AS mkey,
           SUM(CASE WHEN transaction_type='income'  THEN amount ELSE 0 END) AS income,
           SUM(CASE WHEN transaction_type='expense' THEN amount ELSE 0 END) AS expense
    FROM money_flow
    WHERE transaction_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH),'%Y-%m-01')
    GROUP BY mkey, lbl
    ORDER BY mkey ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chart_months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $chart_months[$key] = ['label' => date('M', strtotime("-$i months")), 'income' => 0, 'expense' => 0];
}
foreach ($chart_raw as $row) {
    if (isset($chart_months[$row['mkey']])) {
        $chart_months[$row['mkey']]['income']  = (float)$row['income'];
        $chart_months[$row['mkey']]['expense'] = (float)$row['expense'];
    }
}
$chart_labels_json  = json_encode(array_column(array_values($chart_months), 'label'));
$chart_income_json  = json_encode(array_column(array_values($chart_months), 'income'));
$chart_expense_json = json_encode(array_column(array_values($chart_months), 'expense'));

// Create overview array for display
$overview = [
    'total_revenue' => $total_revenue,
    'pending_revenue' => $outstanding_amount,
    'total_expenses' => $total_expenses,
    'pending_expenses' => $total_expense_flow,
    'outstanding_invoices' => $outstanding_amount,
    'po_total' => $total_expenses
];

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

// Prepare display data for stats (PHP arrays)
$quotations_data = [
    'count' => count(array_filter($quotations, function($q) { return in_array($q['status'], ['draft', 'sent']); })),
    'total' => array_sum(array_map(function($q) { 
        return in_array($q['status'], ['draft', 'sent']) ? $q['total_amount'] : 0; 
    }, $quotations))
];

$invoices_data = [
    'count' => count(array_filter($invoices, function($i) { return $i['status'] != 'paid'; })),
    'total' => array_sum(array_map(function($i) { 
        return $i['status'] != 'paid' ? ($i['total_amount'] - $i['paid_amount']) : 0; 
    }, $invoices))
];

// Prepare chart data for JavaScript (JSON strings)
$quotations_chart = json_encode([
    'labels' => ['Draft', 'Sent', 'Accepted', 'Rejected'],
    'data' => [
        count(array_filter($quotations, function($q) { return $q['status'] == 'draft'; })),
        count(array_filter($quotations, function($q) { return $q['status'] == 'sent'; })),
        count(array_filter($quotations, function($q) { return $q['status'] == 'accepted'; })),
        count(array_filter($quotations, function($q) { return $q['status'] == 'rejected'; }))
    ]
]);

$invoices_chart = json_encode([
    'labels' => ['Pending', 'Paid', 'Overdue'],
    'data' => [
        count(array_filter($invoices, function($i) { return $i['status'] == 'pending'; })),
        count(array_filter($invoices, function($i) { return $i['status'] == 'paid'; })),
        count(array_filter($invoices, function($i) { return $i['status'] == 'overdue'; }))
    ]
]);

// Initialize expenses variable if not already defined
if (!isset($expenses)) {
    $expenses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Department - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ── Finance Design System (Teal / Amber) ── */
        :root {
            --fin: #0d9488;
            --fin-dk: #0f766e;
            --fin-amber: #d97706;
            --fin-amber-dk: #b45309;
            --fin-grad: linear-gradient(135deg,#134e4a 0%,#0d9488 100%);
        }

        /* ── Hero ── */
        .fin-hero {
            background: var(--fin-grad);
            border-radius: 14px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .fin-hero-left { display: flex; align-items: center; gap: 18px; }
        .fin-hero-icon {
            width: 58px; height: 58px; background: rgba(255,255,255,.15);
            border-radius: 14px; display: flex; align-items: center;
            justify-content: center; font-size: 26px; flex-shrink: 0;
        }
        .fin-hero h1 { color: #fff; font-size: 1.55rem; font-weight: 700; margin: 0 0 4px; }
        .fin-hero p  { color: rgba(255,255,255,.75); font-size: 0.88rem; margin: 0; }
        .fin-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .fin-hero-actions .btn-hero {
            background: rgba(255,255,255,.18); color: #fff;
            border: 1.5px solid rgba(255,255,255,.35);
            padding: 8px 16px; border-radius: 8px; font-size: 0.82rem;
            font-weight: 600; cursor: pointer; transition: background .2s;
            white-space: nowrap;
        }
        .fin-hero-actions .btn-hero:hover { background: rgba(255,255,255,.3); }
        .fin-hero-actions .btn-hero.amber {
            background: var(--fin-amber); border-color: var(--fin-amber);
        }
        .fin-hero-actions .btn-hero.amber:hover { background: var(--fin-amber-dk); }

        /* ── Flash messages ── */
        .fin-flash {
            padding: 12px 18px; border-radius: 8px; margin-bottom: 16px;
            font-size: 0.9rem; font-weight: 500;
        }
        .fin-flash.success { background: #d1fae5; color: #065f46; border-left: 4px solid #059669; }
        .fin-flash.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* ── Stats bar ── */
        .fin-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .fin-stat {
            background: #fff; border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 16px 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .fin-stat-lbl { font-size: 0.72rem; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .fin-stat-val { font-size: 1.2rem; font-weight: 700; color: #111827; }
        .fin-stat-val.green  { color: #059669; }
        .fin-stat-val.amber  { color: #d97706; }
        .fin-stat-val.red    { color: #dc2626; }
        .fin-stat-val.teal   { color: var(--fin); }

        /* ── Tab nav ── */
        .fin-tabs {
            display: flex; gap: 0; background: #fff;
            border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 4px; margin-bottom: 20px;
            overflow-x: auto;
        }
        .fin-tab-btn {
            flex: none; padding: 9px 18px; border: none; background: transparent;
            border-radius: 7px; cursor: pointer; font-size: 0.85rem; font-weight: 600;
            color: #6b7280; transition: all .2s; white-space: nowrap;
        }
        .fin-tab-btn:hover { background: #f3f4f6; color: #111827; }
        .fin-tab-btn.active { background: var(--fin); color: #fff; }

        /* ── Tab panels ── */
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ── Controls row ── */
        .fin-controls {
            display: flex; gap: 10px; align-items: center;
            flex-wrap: wrap; margin-bottom: 16px;
        }
        .fin-controls input[type="text"], .fin-controls select {
            padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 0.85rem; color: #374151; background: #fff; outline: none;
            transition: border .2s;
        }
        .fin-controls input[type="text"] { min-width: 200px; flex: 1; max-width: 300px; }
        .fin-controls input:focus, .fin-controls select:focus { border-color: var(--fin); }
        .fin-controls-count { font-size: 0.8rem; color: #6b7280; margin-left: auto; white-space: nowrap; }

        /* ── Data tables ── */
        .fin-table-wrap {
            background: #fff; border-radius: 12px;
            border: 1px solid #e5e7eb; overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .fin-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .fin-table thead tr { background: var(--fin); }
        .fin-table thead th {
            color: #fff; font-weight: 600; font-size: 0.77rem;
            letter-spacing: .4px; padding: 11px 14px; text-align: left;
        }
        .fin-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .15s; }
        .fin-table tbody tr:last-child { border-bottom: none; }
        .fin-table tbody tr:hover { background: #f0fdfa; }
        .fin-table tbody td { padding: 12px 14px; vertical-align: middle; color: #374151; }
        .fin-table tbody td.mono { font-family: monospace; font-weight: 600; color: #111827; }
        .fin-table tbody td.amt { font-weight: 700; color: #059669; }
        .fin-table tbody td.amt-red { font-weight: 700; color: #dc2626; }

        .fin-no-results {
            display: none; text-align: center; padding: 40px 20px;
            color: #6b7280; font-size: 0.9rem;
        }

        /* ── Status badges ── */
        .fbadge {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: 0.73rem; font-weight: 600; text-transform: capitalize;
        }
        .fbadge-draft    { background: #f3f4f6; color: #374151; }
        .fbadge-sent     { background: #eff6ff; color: #1d4ed8; }
        .fbadge-accepted { background: #d1fae5; color: #065f46; }
        .fbadge-rejected { background: #fee2e2; color: #991b1b; }
        .fbadge-completed{ background: #e0e7ff; color: #3730a3; }
        .fbadge-pending  { background: #fef3c7; color: #92400e; }
        .fbadge-overdue  { background: #fee2e2; color: #991b1b; }
        .fbadge-paid     { background: #d1fae5; color: #065f46; }
        .fbadge-partially_paid { background: #fef3c7; color: #92400e; }
        .fbadge-approved { background: #d1fae5; color: #065f46; }
        .fbadge-received { background: #e0e7ff; color: #3730a3; }
        .fbadge-cancelled{ background: #f3f4f6; color: #6b7280; }

        /* ── Action buttons in table ── */
        .fin-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 6px; border: none; cursor: pointer;
            font-size: 0.78rem; font-weight: 600; transition: all .15s;
        }
        .fin-btn-teal   { background: #ccfbf1; color: #0f766e; }
        .fin-btn-teal:hover   { background: #0d9488; color: #fff; }
        .fin-btn-blue   { background: #dbeafe; color: #1e40af; }
        .fin-btn-blue:hover   { background: #2563eb; color: #fff; }
        .fin-btn-amber  { background: #fef3c7; color: #92400e; }
        .fin-btn-amber:hover  { background: #d97706; color: #fff; }
        .fin-btn-green  { background: #d1fae5; color: #065f46; }
        .fin-btn-green:hover  { background: #059669; color: #fff; }
        .fin-btn-red    { background: #fee2e2; color: #991b1b; }
        .fin-btn-red:hover    { background: #dc2626; color: #fff; }
        .fin-btn-gray   { background: #f3f4f6; color: #374151; }
        .fin-btn-gray:hover   { background: #6b7280; color: #fff; }
        .fin-btn select { background: transparent; border: none; color: inherit; font-size: 0.78rem; font-weight: 600; cursor: pointer; }

        /* ── Overview tab ── */
        .fin-overview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }
        .fin-cat-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .fin-cat-title {
            font-size: 0.9rem; font-weight: 700; color: #111827;
            border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; margin-bottom: 12px;
        }
        .fin-cat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 6px 0; border-bottom: 1px solid #f9fafb; font-size: 0.85rem;
        }
        .fin-cat-row:last-child { border-bottom: none; }
        .fin-cat-row .income-val { color: #059669; font-weight: 600; }
        .fin-cat-row .expense-val { color: #dc2626; font-weight: 600; }
        .fin-cat-total {
            margin-top: 10px; padding-top: 10px; border-top: 2px solid #e5e7eb;
            display: flex; justify-content: space-between;
            font-size: 0.85rem; font-weight: 700;
        }
        .fin-chart-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
            grid-column: 1 / -1;
        }
        .fin-chart-card h3 { font-size: 0.95rem; font-weight: 700; color: #111827; margin-bottom: 14px; }
        .fin-chart-card canvas { max-height: 280px; }

        /* ── Modal inner styles (keep working) ── */
        .modal {
            display: none; position: fixed; z-index: 1000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.45);
        }
        .modal-content {
            background: #fff; margin: 3% auto; padding: 2rem;
            border-radius: 12px; width: 90%; max-width: 900px;
            max-height: 90vh; overflow-y: auto;
        }
        .close { color: #aaa; float: right; font-size: 26px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .form-grid {
            display: grid; grid-template-columns: repeat(auto-fit,minmax(230px,1fr));
            gap: 14px; margin-bottom: 14px;
        }
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.87rem; color: #374151; }
        input, select, textarea {
            width: 100%; padding: 8px 11px; border: 1px solid #d1d5db;
            border-radius: 7px; font-size: 0.9rem; transition: border .2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--fin);
            box-shadow: 0 0 0 3px rgba(13,148,136,.1);
        }
        textarea { height: 72px; resize: vertical; }
        .btn {
            background: var(--fin); color: white; padding: 8px 16px;
            border: none; border-radius: 7px; cursor: pointer;
            font-size: 0.88rem; font-weight: 600; transition: background .2s;
            text-decoration: none; display: inline-block;
        }
        .btn:hover { background: var(--fin-dk); }
        .btn-small { padding: 5px 11px; font-size: 0.82rem; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #374151; }
        .btn-success { background: #059669; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-info { background: #0ea5e9; }
        .btn-info:hover { background: #0284c7; }
        .btn-primary { background: var(--fin); }
        .btn-primary:hover { background: var(--fin-dk); }
        .btn-warning { background: var(--fin-amber); color: #fff; }
        .btn-warning:hover { background: var(--fin-amber-dk); }
        .item-row {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px; align-items: center; margin-bottom: 8px;
            padding: 8px; background: #f9fafb; border-radius: 7px;
        }
        .remove-item-btn {
            background: #fee2e2; color: #991b1b; border: none;
            border-radius: 6px; padding: 5px 9px; cursor: pointer; font-size: 0.78rem; font-weight: 600;
        }
        .remove-item-btn:hover { background: #dc2626; color: #fff; }
        .total-display { font-weight: 700; color: var(--fin); text-align: right; }
        /* Keep existing status-badge class working for JS-built view modals */
        .status-badge {
            display: inline-block; padding: 2px 10px; border-radius: 12px;
            font-size: 0.78rem; font-weight: 600; text-transform: capitalize;
        }
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-sent { background: #eff6ff; color: #1d4ed8; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-converted { background: #e0e7ff; color: #3730a3; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-received { background: #e0e7ff; color: #3730a3; }
        .status-partially_paid { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        .amount.income { color: #059669; font-weight: 700; }
        .amount.expense { color: #dc2626; font-weight: 700; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 4px; }

        @media (max-width: 900px) {
            .fin-stats { grid-template-columns: repeat(3,1fr); }
            .fin-overview-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .fin-stats { grid-template-columns: repeat(2,1fr); }
            .fin-hero { flex-direction: column; align-items: flex-start; }
            .fin-table { font-size: 0.8rem; }
        }
    </style>
</head>
<body>
<?php $can_write = in_array($_SESSION['role'] ?? '', ['admin', 'manager']); ?>

    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if (!empty($success_message)): ?>
        <div class="fin-flash success">&#10003; <?php echo Security::escapeHTML($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="fin-flash error">&#10005; <?php echo Security::escapeHTML($error_message); ?></div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="fin-hero">
            <div class="fin-hero-left">
                <div class="fin-hero-icon">&#128176;</div>
                <div>
                    <h1>Finance Department</h1>
                    <p>Quotations &middot; Invoices &middot; Purchase Orders &middot; Cash Flow</p>
                </div>
            </div>
            <?php if ($can_write): ?>
            <div class="fin-hero-actions">
                <button class="btn-hero" onclick="resetQuotationForm(); document.getElementById('quotationModal').style.display='block'">+ Quotation</button>
                <button class="btn-hero" onclick="resetInvoiceForm(); document.getElementById('invoiceModal').style.display='block'">+ Invoice</button>
                <button class="btn-hero" onclick="document.getElementById('purchaseOrderModal').style.display='block'">+ Purchase Order</button>
                <button class="btn-hero amber" onclick="resetRevenueForm(); document.getElementById('projectRevenueModal').style.display='block'">+ Revenue</button>
                <button class="btn-hero" onclick="document.getElementById('expenseModal').style.display='block'">+ Expense</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats bar -->
        <div class="fin-stats">
            <div class="fin-stat">
                <div class="fin-stat-lbl">Collected Revenue</div>
                <div class="fin-stat-val green">R <?php echo number_format($total_revenue, 2); ?></div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">Cash received from invoices</div>
            </div>
            <div class="fin-stat">
                <div class="fin-stat-lbl">Outstanding Receivables</div>
                <div class="fin-stat-val amber">R <?php echo number_format($outstanding_amount, 2); ?></div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">Active unpaid invoices</div>
            </div>
            <div class="fin-stat">
                <div class="fin-stat-lbl">Total Expenses</div>
                <div class="fin-stat-val red">R <?php echo number_format($total_expenses, 2); ?></div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">POs R<?php echo number_format($po_expense_total,2); ?> + Claims R<?php echo number_format($exp_table_total,2); ?></div>
            </div>
            <div class="fin-stat">
                <div class="fin-stat-lbl">Net P&amp;L</div>
                <div class="fin-stat-val <?php echo $net_pl >= 0 ? 'green' : 'red'; ?>">R <?php echo number_format(abs($net_pl), 2); ?><?php echo $net_pl < 0 ? ' loss' : ''; ?></div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">Collected &minus; Expenses</div>
            </div>
            <div class="fin-stat">
                <div class="fin-stat-lbl">Unpaid Invoices</div>
                <div class="fin-stat-val red"><?php echo (int)$unpaid_invoices; ?></div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">Awaiting payment</div>
            </div>
        </div>

        <!-- Tab navigation -->
        <div class="fin-tabs">
            <button class="fin-tab-btn tab-btn <?php echo $view === 'money_flow' ? 'active' : ''; ?>" onclick="showTab('money_flow')">Overview</button>
            <button class="fin-tab-btn tab-btn <?php echo $view === 'quotations' ? 'active' : ''; ?>" onclick="showTab('quotations')">Quotations (<?php echo (int)$total_quotations; ?>)</button>
            <button class="fin-tab-btn tab-btn <?php echo $view === 'invoices' ? 'active' : ''; ?>" onclick="showTab('invoices')">Invoices (<?php echo (int)$total_invoices; ?>)</button>
            <button class="fin-tab-btn tab-btn <?php echo $view === 'purchase_orders' ? 'active' : ''; ?>" onclick="showTab('purchase_orders')">Purchase Orders (<?php echo (int)$total_purchase_orders; ?>)</button>
            <button class="fin-tab-btn tab-btn <?php echo $view === 'project_revenues' ? 'active' : ''; ?>" onclick="showTab('project_revenues')">Revenues (<?php echo count($project_revenues); ?>)</button>
            <button class="fin-tab-btn tab-btn <?php echo $view === 'expenses' ? 'active' : ''; ?>" onclick="showTab('expenses')">Expenses (<?php echo count($expenses); ?>)</button>
        </div>

        <!-- TAB: OVERVIEW -->
        <div id="money_flow" class="tab-content <?php echo ($view === 'money_flow' || $view === 'overview') ? 'active' : ''; ?>">
            <div class="fin-overview-grid">
                <div class="fin-cat-card">
                    <div class="fin-cat-title">Income by Category</div>
                    <?php if (empty($income_by_category)): ?>
                        <p style="color:#6b7280;font-size:.85rem;">No income recorded yet.</p>
                    <?php else: ?>
                        <?php foreach ($income_by_category as $cat => $amt): ?>
                        <div class="fin-cat-row">
                            <span><?php echo Security::escapeHTML($cat); ?></span>
                            <span class="income-val">R <?php echo number_format($amt, 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="fin-cat-total">
                        <span>Recent (30 days)</span>
                        <span class="income-val">R <?php echo number_format($recent_income, 2); ?></span>
                    </div>
                </div>
                <div class="fin-cat-card">
                    <div class="fin-cat-title">Expenses by Category</div>
                    <?php if (empty($expense_by_category)): ?>
                        <p style="color:#6b7280;font-size:.85rem;">No expenses recorded yet.</p>
                    <?php else: ?>
                        <?php foreach ($expense_by_category as $cat => $amt): ?>
                        <div class="fin-cat-row">
                            <span><?php echo Security::escapeHTML($cat); ?></span>
                            <span class="expense-val">R <?php echo number_format($amt, 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="fin-cat-total">
                        <span>Recent (30 days)</span>
                        <span class="expense-val">R <?php echo number_format($recent_expenses, 2); ?></span>
                    </div>
                </div>
                <div class="fin-chart-card">
                    <h3>Cash Flow Trend</h3>
                    <canvas id="cashFlowChart"></canvas>
                </div>
            </div>
        </div>

        <!-- TAB: QUOTATIONS -->
        <div id="quotations" class="tab-content <?php echo $view === 'quotations' ? 'active' : ''; ?>">
            <div class="fin-controls">
                <input type="text" id="q-search" placeholder="Search client or quotation #..." oninput="filterFinTab('q-tbody','q-search','q-status','q-count')">
                <select id="q-status" onchange="filterFinTab('q-tbody','q-search','q-status','q-count')">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="completed">Completed</option>
                </select>
                <span class="fin-controls-count" id="q-count"><?php echo count($quotations); ?> quotation<?php echo count($quotations) !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead><tr>
                        <th>Quotation #</th><th>Client</th><th>Date</th><th>Valid Until</th><th>Amount</th><th>Status</th><th>Created By</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="q-tbody">
                        <?php foreach ($quotations as $quotation): ?>
                        <tr data-search="<?php echo strtolower(Security::escapeHTML($quotation['quotation_number']).' '.Security::escapeHTML($quotation['client_name'] ?? '')); ?>"
                            data-status="<?php echo $quotation['status']; ?>">
                            <td class="mono"><?php echo Security::escapeHTML($quotation['quotation_number']); ?></td>
                            <td><?php echo Security::escapeHTML($quotation['client_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d M Y', strtotime($quotation['quotation_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($quotation['valid_until'])); ?></td>
                            <td class="amt">R <?php echo number_format($quotation['total_amount'], 2); ?></td>
                            <td><span class="fbadge fbadge-<?php echo $quotation['status']; ?>"><?php echo ucfirst($quotation['status']); ?></span></td>
                            <td><?php echo Security::escapeHTML($quotation['created_by_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="fin-btn fin-btn-teal" onclick="viewQuotation(<?php echo $quotation['id']; ?>)">View</button>
                                    <button class="fin-btn fin-btn-blue" onclick="printQuotationPDF(<?php echo $quotation['id']; ?>)">PDF</button>
                                    <?php if ($quotation['status'] === 'completed'): ?>
                                        <?php if ($quotation['converted_invoice_id']): ?>
                                        <button class="fin-btn fin-btn-green" onclick="viewInvoice(<?php echo $quotation['converted_invoice_id']; ?>)">Invoice</button>
                                        <?php endif; ?>
                                    <?php elseif ($quotation['status'] === 'accepted'): ?>
                                        <?php if ($can_write): ?>
                                        <button class="fin-btn fin-btn-green" onclick="convertToInvoice(<?php echo $quotation['id']; ?>)">Convert to Invoice</button>
                                        <?php endif; ?>
                                    <?php elseif (!in_array($quotation['status'], ['rejected'])): ?>
                                        <?php if ($can_write): ?>
                                        <button class="fin-btn fin-btn-amber" onclick="editQuotation(<?php echo $quotation['id']; ?>)">Edit</button>
                                        <span class="fin-btn fin-btn-gray" style="padding:0;">
                                            <select onchange="updateQuotationStatus(<?php echo $quotation['id']; ?>, this.value)" title="Update Status">
                                                <option value="">Status</option>
                                                <option value="draft"    <?php echo $quotation['status']==='draft'    ?'selected':''; ?>>Draft</option>
                                                <option value="sent"     <?php echo $quotation['status']==='sent'     ?'selected':''; ?>>Sent</option>
                                                <option value="accepted" <?php echo $quotation['status']==='accepted' ?'selected':''; ?>>Accepted</option>
                                                <option value="rejected" <?php echo $quotation['status']==='rejected' ?'selected':''; ?>>Rejected</option>
                                            </select>
                                        </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="fin-no-results" id="q-tbody-no-results">No quotations match your filter.</div>
            </div>
        </div>

        <!-- TAB: INVOICES -->
        <div id="invoices" class="tab-content <?php echo $view === 'invoices' ? 'active' : ''; ?>">
            <div class="fin-controls">
                <input type="text" id="inv-search" placeholder="Search client or invoice #..." oninput="filterFinTab('inv-tbody','inv-search','inv-status','inv-count')">
                <select id="inv-status" onchange="filterFinTab('inv-tbody','inv-search','inv-status','inv-count')">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <span class="fin-controls-count" id="inv-count"><?php echo count($invoices); ?> invoice<?php echo count($invoices) !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead><tr>
                        <th>Invoice #</th><th>Client</th><th>Date</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="inv-tbody">
                        <?php foreach ($invoices as $invoice): ?>
                        <?php $balance = $invoice['total_amount'] - $invoice['paid_amount']; ?>
                        <tr data-search="<?php echo strtolower(Security::escapeHTML($invoice['invoice_number']).' '.Security::escapeHTML($invoice['client_name'] ?? '')); ?>"
                            data-status="<?php echo $invoice['status']; ?>">
                            <td class="mono"><?php echo Security::escapeHTML($invoice['invoice_number']); ?></td>
                            <td><?php echo Security::escapeHTML($invoice['client_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                            <td style="<?php echo (strtotime($invoice['due_date'])<time()&&$invoice['status']!=='paid')?'color:#dc2626;font-weight:600;':''; ?>">
                                <?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                            <td class="amt">R <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td style="color:#059669;font-weight:600;">R <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                            <td class="<?php echo $balance>0?'amt-red':'amt'; ?>">R <?php echo number_format($balance, 2); ?></td>
                            <td><span class="fbadge fbadge-<?php echo $invoice['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$invoice['status'])); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="fin-btn fin-btn-teal" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">View</button>
                                    <button class="fin-btn fin-btn-blue" onclick="printInvoicePDF(<?php echo $invoice['id']; ?>)">PDF</button>
                                    <?php if ($invoice['status'] === 'paid'): ?>
                                        <span class="fin-btn fin-btn-green" style="cursor:default;">&#10003; Paid</span>
                                    <?php else: ?>
                                        <?php if ($can_write): ?>
                                        <button class="fin-btn fin-btn-amber" onclick="editInvoice(<?php echo $invoice['id']; ?>)">Edit</button>
                                        <button class="fin-btn fin-btn-green" onclick="recordPayment(<?php echo $invoice['id']; ?>, <?php echo $balance; ?>)">Pay</button>
                                        <span class="fin-btn fin-btn-gray" style="padding:0;">
                                            <select onchange="updateInvoiceStatus(<?php echo $invoice['id']; ?>, this.value)" title="Update Status">
                                                <option value="">Status</option>
                                                <option value="draft"     <?php echo $invoice['status']==='draft'    ?'selected':''; ?>>Draft</option>
                                                <option value="sent"      <?php echo $invoice['status']==='sent'     ?'selected':''; ?>>Sent</option>
                                                <option value="pending"   <?php echo $invoice['status']==='pending'  ?'selected':''; ?>>Pending</option>
                                                <option value="overdue"   <?php echo $invoice['status']==='overdue'  ?'selected':''; ?>>Overdue</option>
                                                <option value="cancelled" <?php echo $invoice['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                            </select>
                                        </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="fin-no-results" id="inv-tbody-no-results">No invoices match your filter.</div>
            </div>
        </div>

        <!-- TAB: PURCHASE ORDERS -->
        <div id="purchase_orders" class="tab-content <?php echo $view === 'purchase_orders' ? 'active' : ''; ?>">
            <div class="fin-controls">
                <input type="text" id="po-search" placeholder="Search supplier or PO #..." oninput="filterFinTab('po-tbody','po-search','po-status','po-count')">
                <select id="po-status" onchange="filterFinTab('po-tbody','po-search','po-status','po-count')">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="received">Received</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <span class="fin-controls-count" id="po-count"><?php echo count($purchase_orders); ?> order<?php echo count($purchase_orders) !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead><tr>
                        <th>PO #</th><th>Supplier</th><th>Project</th><th>Order Date</th><th>Expected Delivery</th><th>Amount</th><th>Status</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="po-tbody">
                        <?php foreach ($purchase_orders as $po): ?>
                        <tr data-search="<?php echo strtolower(Security::escapeHTML($po['po_number']).' '.Security::escapeHTML($po['supplier_name'])); ?>"
                            data-status="<?php echo $po['status']; ?>">
                            <td class="mono"><?php echo Security::escapeHTML($po['po_number']); ?></td>
                            <td>
                                <div style="font-weight:600;"><?php echo Security::escapeHTML($po['supplier_name']); ?></div>
                                <div style="font-size:.78rem;color:#6b7280;"><?php echo Security::escapeHTML($po['supplier_email']); ?></div>
                            </td>
                            <td><?php echo Security::escapeHTML($po['project_name'] ?? '—'); ?></td>
                            <td><?php echo date('d M Y', strtotime($po['order_date'])); ?></td>
                            <td><?php echo $po['expected_delivery'] ? date('d M Y', strtotime($po['expected_delivery'])) : '—'; ?></td>
                            <td class="amt">R <?php echo number_format($po['total_amount'], 2); ?></td>
                            <td><span class="fbadge fbadge-<?php echo $po['status']; ?>"><?php echo ucfirst($po['status']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="fin-btn fin-btn-teal" onclick="viewPurchaseOrder(<?php echo $po['id']; ?>)">View</button>
                                    <button class="fin-btn fin-btn-amber" onclick="editPurchaseOrder(<?php echo $po['id']; ?>)">Edit</button>
                                    <?php if (in_array($po['status'], ['approved','completed'])): ?>
                                        <button class="fin-btn fin-btn-blue" onclick="printPurchaseOrderPDF(<?php echo $po['id']; ?>)">PDF</button>
                                    <?php else: ?>
                                        <button class="fin-btn fin-btn-gray" disabled title="Approve first">PDF</button>
                                    <?php endif; ?>
                                    <?php if (!in_array($po['status'], ['completed', 'cancelled'])): ?>
                                    <span class="fin-btn fin-btn-gray" style="padding:0;">
                                        <select onchange="updatePOStatus(<?php echo $po['id']; ?>, this.value)" title="Update Status">
                                            <option value="">Change Status</option>
                                            <option value="pending"   <?php echo $po['status']==='pending'  ?'selected':''; ?>>Pending</option>
                                            <option value="approved"  <?php echo $po['status']==='approved' ?'selected':''; ?>>Approved</option>
                                            <option value="received"  <?php echo $po['status']==='received' ?'selected':''; ?>>Received</option>
                                            <option value="completed" <?php echo $po['status']==='completed'?'selected':''; ?>>Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="fin-no-results" id="po-tbody-no-results">No purchase orders match your filter.</div>
            </div>
        </div>

        <!-- TAB: PROJECT REVENUES -->
        <div id="project_revenues" class="tab-content <?php echo $view === 'project_revenues' ? 'active' : ''; ?>">
            <div class="fin-controls">
                <input type="text" id="rev-search" placeholder="Search project or client..." oninput="filterFinTab('rev-tbody','rev-search','rev-type','rev-count')">
                <select id="rev-type" onchange="filterFinTab('rev-tbody','rev-search','rev-type','rev-count')">
                    <option value="">All Types</option>
                    <option value="milestone">Milestone</option>
                    <option value="final">Final Payment</option>
                    <option value="deposit">Deposit</option>
                    <option value="retainer">Retainer</option>
                    <option value="bonus">Bonus</option>
                    <option value="other">Other</option>
                </select>
                <span class="fin-controls-count" id="rev-count"><?php echo count($project_revenues); ?> record<?php echo count($project_revenues) !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead><tr>
                        <th>Project</th><th>Client</th><th>Type</th><th>Amount</th><th>Received Date</th><th>Payment Method</th><th>Reference</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="rev-tbody">
                        <?php foreach ($project_revenues as $revenue): ?>
                        <tr data-search="<?php echo strtolower(Security::escapeHTML($revenue['project_name'] ?? '').' '.Security::escapeHTML($revenue['client_name'] ?? '')); ?>"
                            data-status="<?php echo $revenue['revenue_type']; ?>">
                            <td style="font-weight:600;"><?php echo Security::escapeHTML($revenue['project_name'] ?? 'N/A'); ?></td>
                            <td><?php echo Security::escapeHTML($revenue['client_name'] ?? 'N/A'); ?></td>
                            <td><span class="fbadge fbadge-accepted"><?php echo ucfirst($revenue['revenue_type']); ?></span></td>
                            <td class="amt">R <?php echo number_format($revenue['amount'], 2); ?></td>
                            <td><?php echo $revenue['received_date'] ? date('d M Y', strtotime($revenue['received_date'])) : '—'; ?></td>
                            <td><?php echo Security::escapeHTML(str_replace('_',' ',$revenue['payment_method'] ?? '—')); ?></td>
                            <td style="font-size:.8rem;color:#6b7280;"><?php echo Security::escapeHTML($revenue['reference_number'] ?? '—'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="fin-btn fin-btn-teal" onclick="viewRevenue(<?php echo htmlspecialchars(json_encode($revenue)); ?>)">View</button>
                                    <button class="fin-btn fin-btn-amber" onclick="editRevenue(<?php echo htmlspecialchars(json_encode($revenue)); ?>)">Edit</button>
                                    <button class="fin-btn fin-btn-red" onclick="confirmDeleteRevenue(<?php echo $revenue['id']; ?>, '<?php echo addslashes($revenue['project_name'] ?? 'N/A'); ?>', <?php echo $revenue['amount']; ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="fin-no-results" id="rev-tbody-no-results">No revenue records match your filter.</div>
            </div>
        </div>

        <!-- TAB: EXPENSES -->
        <div id="expenses" class="tab-content <?php echo $view === 'expenses' ? 'active' : ''; ?>">
            <div class="fin-controls">
                <input type="text" id="exp-search" placeholder="Search category or description..." oninput="filterFinTab('exp-tbody','exp-search','exp-status','exp-count')">
                <select id="exp-status" onchange="filterFinTab('exp-tbody','exp-search','exp-status','exp-count')">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <span class="fin-controls-count" id="exp-count"><?php echo count($expenses); ?> expense<?php echo count($expenses) !== 1 ? 's' : ''; ?></span>
            </div>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead><tr>
                        <th>Category</th><th>Description</th><th>Amount</th><th>Date</th><th>Project</th><th>Submitted By</th><th>Status</th><?php if ($can_write): ?><th>Actions</th><?php endif; ?>
                    </tr></thead>
                    <tbody id="exp-tbody">
                        <?php foreach ($expenses as $exp): ?>
                        <tr data-search="<?php echo strtolower(Security::escapeHTML($exp['category']).' '.Security::escapeHTML($exp['description'])); ?>"
                            data-status="<?php echo $exp['status']; ?>">
                            <td><span class="fbadge fbadge-sent"><?php echo Security::escapeHTML(ucfirst(str_replace('_',' ',$exp['category']))); ?></span></td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo Security::escapeHTML($exp['description']); ?>"><?php echo Security::escapeHTML($exp['description']); ?></td>
                            <td class="amt">R <?php echo number_format($exp['amount'], 2); ?></td>
                            <td style="white-space:nowrap;"><?php echo $exp['expense_date'] ? date('d M Y', strtotime($exp['expense_date'])) : '—'; ?></td>
                            <td><?php echo Security::escapeHTML($exp['project_name'] ?? '—'); ?></td>
                            <td><?php echo Security::escapeHTML($exp['submitted_by_name'] ?? '—'); ?></td>
                            <td>
                                <span class="fbadge fbadge-<?php echo $exp['status']; ?>">
                                    <?php echo ucfirst($exp['status']); ?>
                                </span>
                            </td>
                            <?php if ($can_write): ?>
                            <td>
                                <?php if ($exp['status'] === 'pending'): ?>
                                <div class="action-buttons">
                                    <form method="POST" style="display:inline;">
                                        <?php echo Security::getCSRFTokenField(); ?>
                                        <input type="hidden" name="expense_id" value="<?php echo $exp['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" name="update_expense_status" value="1" class="fin-btn fin-btn-green">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <?php echo Security::getCSRFTokenField(); ?>
                                        <input type="hidden" name="expense_id" value="<?php echo $exp['id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" name="update_expense_status" value="1" class="fin-btn fin-btn-red">Reject</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="font-size:.78rem;color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expenses)): ?>
                        <tr><td colspan="8" class="fin-no-results" style="display:table-cell;text-align:center;padding:40px;color:#6b7280;">No expenses recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="fin-no-results" id="exp-tbody-no-results">No expenses match your filter.</div>
            </div>
        </div>

    </div><!-- /.main-content -->
    <script src="../js/notification.js"></script>  
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }

        // Finance table filter function
        function filterFinTab(tbodyId, searchId, filterDropId, countId) {
            const q  = document.getElementById(searchId).value.toLowerCase().trim();
            const st = document.getElementById(filterDropId).value;
            const rows = document.querySelectorAll('#' + tbodyId + ' tr');
            let visible = 0;
            rows.forEach(row => {
                const matchQ  = !q  || (row.dataset.search||'').toLowerCase().includes(q);
                const matchSt = !st || (row.dataset.status||'') === st;
                if (matchQ && matchSt) { row.style.display = ''; visible++; }
                else                   { row.style.display = 'none'; }
            });
            const nr = document.getElementById(tbodyId + '-no-results');
            if (nr) nr.style.display = visible === 0 ? 'block' : 'none';
            const nouns = {q:'quotation',inv:'invoice',po:'order',rev:'record'};
            const prefix = tbodyId.split('-')[0];
            const noun = nouns[prefix] || 'item';
            const c = document.getElementById(countId);
            if (c) c.textContent = visible + ' ' + noun + (visible !== 1 ? 's' : '');
        }

        // Global variables for modal item management
        var quotationItemCount = 1;
        var invoiceItemCount = 1;
        
        // Cash Flow Chart — real monthly data from money_flow
        (function() {
            const ctx = document.getElementById('cashFlowChart');
            if (!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chart_labels_json; ?>,
                    datasets: [{
                        label: 'Income',
                        data: <?php echo $chart_income_json; ?>,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5,150,105,0.08)',
                        tension: 0.4, fill: true, pointRadius: 4
                    }, {
                        label: 'Expenses',
                        data: <?php echo $chart_expense_json; ?>,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.08)',
                        tension: 0.4, fill: true, pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => 'R ' + v.toLocaleString('en-ZA') }
                        }
                    }
                }
            });
        })();
        
        
        // Purchase Order item management (for modal)
        let poItemCount = 1; // Initialize counter
        
        function addPOItem() {
            const container = document.getElementById('po-modal-items');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <input type="text" name="items[${poItemCount}][description]" placeholder="Description" required>
                <input type="number" name="items[${poItemCount}][quantity]" placeholder="Quantity" step="0.01" required>
                <input type="number" name="items[${poItemCount}][unit_price]" placeholder="Unit Price" step="0.01" required>
                <span class="total-display">R 0.00</span>
                <button type="button" class="remove-item-btn" onclick="removePOItem(this)">Remove</button>
            `;
            container.appendChild(newItem);
            poItemCount++;
            updatePOTotals();
        }
        
        function removePOItem(button) {
            if (document.querySelectorAll('#po-modal-items .item-row').length > 1) {
                button.parentElement.remove();
                updatePOTotals();
            }
        }
        
        function updatePOTotals() {
            const rows = document.querySelectorAll('#po-modal-items .item-row');
            let grandTotal = 0;
            
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
                const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
                const total = quantity * unitPrice;
                row.querySelector('.total-display').textContent = `R ${total.toFixed(2)}`;
                grandTotal += total;
            });
            
            const vatAmount = grandTotal * 0.15; // 15% VAT
            const totalWithVat = grandTotal + vatAmount;
            
            // Update totals display if elements exist
            const subtotalEl = document.getElementById('po-subtotal');
            const vatEl = document.getElementById('po-vat');
            const totalEl = document.getElementById('po-total');
            
            if (subtotalEl) subtotalEl.textContent = `R ${grandTotal.toFixed(2)}`;
            if (vatEl) vatEl.textContent = `R ${vatAmount.toFixed(2)}`;
            if (totalEl) totalEl.textContent = `R ${totalWithVat.toFixed(2)}`;
        }
        
        function removeItem(button) {
            if (document.querySelectorAll('.item-row').length > 1) {
                button.parentElement.remove();
            }
        }
        
        // Auto-calculate totals
        document.addEventListener('input', function(e) {
            if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[unit_price]'))) {
                const row = e.target.closest('.item-row');
                const quantity = row.querySelector('input[name*="[quantity]"]').value || 0;
                const unitPrice = row.querySelector('input[name*="[unit_price]"]').value || 0;
                const total = (quantity * unitPrice).toFixed(2);
                row.querySelector('.total-display').textContent = total;
            }
        });
        
        // Modal functions
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function resetQuotationForm() {
            // Reset form to create new quotation mode
            const form = document.querySelector('#quotationModal form');
            if (form) form.reset();
            
            const updateButton = document.querySelector('#quotationModal button[name="update_quotation"]');
            if (updateButton) {
                updateButton.name = 'create_quotation';
                updateButton.innerHTML = '💾 Create Quotation';
            }
            
            const createButton = document.querySelector('#quotationModal button[name="create_quotation"]');
            if (createButton) createButton.innerHTML = '💾 Create Quotation';
            
            const title = document.querySelector('#quotationModal h3');
            if (title) title.textContent = 'Create New Quotation';
            
            // Remove quotation_id field if exists
            const quotationIdField = document.querySelector('#quotationModal input[name="quotation_id"]');
            if (quotationIdField) {
                quotationIdField.remove();
            }
            
            // Reset item count and display
            quotationItemCount = 1;
            if (typeof updateQuotationTotals === 'function') {
                updateQuotationTotals();
            }
        }
        
        function resetInvoiceForm() {
            // Reset form to create new invoice mode
            const form = document.querySelector('#invoiceModal form');
            if (form) form.reset();
            
            const updateButton = document.querySelector('#invoiceModal button[name="update_invoice"]');
            if (updateButton) {
                updateButton.name = 'create_invoice';
                updateButton.innerHTML = '💾 Create Invoice';
            }
            
            const createButton = document.querySelector('#invoiceModal button[name="create_invoice"]');
            if (createButton) createButton.innerHTML = '💾 Create Invoice';
            
            const title = document.querySelector('#invoiceModal h3');
            if (title) title.textContent = 'Create New Invoice';
            
            // Remove invoice_id field if exists
            const invoiceIdField = document.querySelector('#invoiceModal input[name="invoice_id"]');
            if (invoiceIdField) {
                invoiceIdField.remove();
            }
            
            // Reset item count and display
            invoiceItemCount = 1;
            if (typeof updateInvoiceTotals === 'function') {
                updateInvoiceTotals();
            }
        }
        
        function resetPurchaseOrderForm() {
            // Reset form to create new purchase order mode
            const form = document.querySelector('#purchaseOrderModal form');
            if (form) form.reset();
            
            const updateButton = document.querySelector('#purchaseOrderModal button[name="update_purchase_order"]');
            if (updateButton) {
                updateButton.name = 'create_purchase_order';
                updateButton.innerHTML = '💾 Create Purchase Order';
            }
            
            const createButton = document.querySelector('#purchaseOrderModal button[name="create_purchase_order"]');
            if (createButton) createButton.innerHTML = '💾 Create Purchase Order';
            
            const title = document.querySelector('#purchaseOrderModal h3');
            if (title) title.textContent = 'Create New Purchase Order';
            
            // Remove po_id field if exists
            const poIdField = document.querySelector('#purchaseOrderModal input[name="po_id"]');
            if (poIdField) {
                poIdField.remove();
            }
            
            // Clear items container and reset item count
            const itemsContainer = document.getElementById('po-modal-items'); // Fixed ID to match HTML
            if (itemsContainer) {
                itemsContainer.innerHTML = '';
            }
            purchaseOrderItemCount = 1;
            
            // Reset totals (use updatePOTotals to match HTML)
            if (typeof updatePOTotals === 'function') {
                updatePOTotals();
            }
        }
        
        // View/PDF functions
        function viewQuotation(id) {
            fetch('?ajax=view_quotation&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const quotation = data.data;
                        showQuotationViewModal(quotation);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading quotation:', error);
                    alert('Unable to load quotation data.');
                });
        }
        
        function printQuotationPDF(id) {
            window.open('finance_pdf.php?type=quotation&id=' + id, '_blank');
        }
        
        function viewInvoice(id) {
            fetch('?ajax=view_invoice&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const invoice = data.data;
                        showInvoiceViewModal(invoice);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading invoice:', error);
                    alert('Unable to load invoice data.');
                });
        }
        
        function editQuotation(id) {
            // Fetch quotation data and populate edit modal
            fetch('?ajax=view_quotation&id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text); // Debug log
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e, 'Raw text:', text);
                        throw new Error('Invalid JSON response');
                    }
                    
                    if (data.success) {
                        const quotation = data.data;
                        resetQuotationForm();
                        
                        // Populate form with existing data with null checks
                        const clientSelect = document.querySelector('#quotationModal select[name="client_id"]');
                        const quotationDate = document.querySelector('#quotationModal input[name="quotation_date"]');
                        const validUntil = document.querySelector('#quotationModal input[name="valid_until"]');
                        const vatRate = document.querySelector('#quotationModal input[name="vat_rate"]');
                        const notes = document.querySelector('#quotationModal textarea[name="notes"]');
                        const title = document.querySelector('#quotationModal h3');
                        const button = document.querySelector('#quotationModal button[name="create_quotation"]');
                        
                        if (clientSelect) clientSelect.value = quotation.client_id || '';
                        if (quotationDate) quotationDate.value = quotation.quotation_date || '';
                        if (validUntil) validUntil.value = quotation.valid_until || '';
                        if (vatRate) vatRate.value = (quotation.vat_rate * 100) || 15;
                        if (notes) notes.value = quotation.notes || '';
                        
                        // Configure for edit mode with null checks
                        if (title) title.textContent = '✏️ Edit Quotation #' + id;
                        if (button) {
                            button.name = 'update_quotation';
                            button.innerHTML = '💾 Update Quotation';
                        }
                        
                        // Add hidden field for quotation ID
                        let quotationIdField = document.querySelector('#quotationModal input[name="quotation_id"]');
                        if (!quotationIdField) {
                            quotationIdField = document.createElement('input');
                            quotationIdField.type = 'hidden';
                            quotationIdField.name = 'quotation_id';
                            document.querySelector('#quotationModal form').appendChild(quotationIdField);
                        }
                        quotationIdField.value = id;
                        
                        // Populate items if they exist
                        if (quotation.items && quotation.items.length > 0) {
                            const itemsContainer = document.getElementById('quotation-items');
                            itemsContainer.innerHTML = ''; // Clear existing items
                            quotationItemCount = 0;
                            
                            quotation.items.forEach(item => {
                                const newItem = document.createElement('div');
                                newItem.className = 'item-row';
                                newItem.innerHTML = `
                                    <input type="text" name="items[${quotationItemCount}][description]" placeholder="Description" value="${item.description}" required oninput="updateQuotationTotals()">
                                    <input type="number" name="items[${quotationItemCount}][quantity]" placeholder="Quantity" value="${item.quantity}" step="0.01" required oninput="updateQuotationTotals()">
                                    <input type="number" name="items[${quotationItemCount}][unit_price]" placeholder="Unit Price" value="${item.unit_price}" step="0.01" required oninput="updateQuotationTotals()">
                                    <span class="total-display">R ${(item.quantity * item.unit_price).toFixed(2)}</span>
                                    <button type="button" class="remove-item-btn" onclick="removeQuotationItem(this)">Remove</button>
                                `;
                                itemsContainer.appendChild(newItem);
                                quotationItemCount++;
                            });
                            updateQuotationTotals();
                        }
                        
                        document.getElementById('quotationModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading quotation:', error);
                    alert('Unable to load quotation data for editing: ' + error.message);
                });
        }

        // View modal functions
        function showQuotationViewModal(quotation) {
            // Create view modal HTML
            const viewModal = document.createElement('div');
            viewModal.id = 'quotationViewModal';
            viewModal.className = 'modal';
            viewModal.innerHTML = `
                <div class="modal-content" style="max-width: 800px;">
                    <span class="close" onclick="closeQuotationViewModal()">&times;</span>
                    <h3>📄 Quotation Details #${quotation.id}</h3>
                    
                    <div class="view-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
                        <div><strong>Client:</strong> ${quotation.client_name || 'N/A'}</div>
                        <div><strong>Date:</strong> ${quotation.quotation_date}</div>
                        <div><strong>Valid Until:</strong> ${quotation.valid_until}</div>
                        <div><strong>Status:</strong> ${quotation.status}</div>
                        <div><strong>VAT Rate:</strong> ${(quotation.vat_rate * 100).toFixed(1)}%</div>
                        <div><strong>Total:</strong> R ${parseFloat(quotation.total_amount).toFixed(2)}</div>
                    </div>
                    
                    ${quotation.notes ? `<div style="margin: 1rem 0;"><strong>Notes:</strong><br>${quotation.notes}</div>` : ''}
                    
                    <h4>Items:</h4>
                    <table class="view-items-table" style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Description</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Qty</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Unit Price</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${quotation.items ? quotation.items.map(item => `
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.description}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.quantity}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" style="text-align: center; padding: 1rem;">No items found</td></tr>'}
                        </tbody>
                    </table>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <button onclick="printQuotationPDF(${quotation.id})" class="btn">📄 Print PDF</button>
                        <button onclick="closeQuotationViewModal()" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('quotationViewModal');
            if (existingModal) existingModal.remove();
            
            // Add to body and show
            document.body.appendChild(viewModal);
            viewModal.style.display = 'block';
        }

        function showInvoiceViewModal(invoice) {
            // Create view modal HTML
            const viewModal = document.createElement('div');
            viewModal.id = 'invoiceViewModal';
            viewModal.className = 'modal';
            viewModal.innerHTML = `
                <div class="modal-content" style="max-width: 800px;">
                    <span class="close" onclick="closeInvoiceViewModal()">&times;</span>
                    <h3>📄 Invoice Details #${invoice.id}</h3>
                    
                    <div class="view-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
                        <div><strong>Client:</strong> ${invoice.client_name || 'N/A'}</div>
                        <div><strong>Invoice Date:</strong> ${invoice.invoice_date}</div>
                        <div><strong>Due Date:</strong> ${invoice.due_date}</div>
                        <div><strong>Status:</strong> ${invoice.status}</div>
                        <div><strong>VAT Rate:</strong> ${(invoice.vat_rate * 100).toFixed(1)}%</div>
                        <div><strong>Total:</strong> R ${parseFloat(invoice.total_amount).toFixed(2)}</div>
                    </div>
                    
                    ${invoice.notes ? `<div style="margin: 1rem 0;"><strong>Notes:</strong><br>${invoice.notes}</div>` : ''}
                    
                    <h4>Items:</h4>
                    <table class="view-items-table" style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Description</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Qty</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Unit Price</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${invoice.items ? invoice.items.map(item => `
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.description}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.quantity}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" style="text-align: center; padding: 1rem;">No items found</td></tr>'}
                        </tbody>
                    </table>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <button onclick="printInvoicePDF(${invoice.id})" class="btn">📄 Print PDF</button>
                        <button onclick="closeInvoiceViewModal()" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('invoiceViewModal');
            if (existingModal) existingModal.remove();
            
            // Add to body and show
            document.body.appendChild(viewModal);
            viewModal.style.display = 'block';
        }

        function closeQuotationViewModal() {
            const modal = document.getElementById('quotationViewModal');
            if (modal) modal.remove();
        }

        function closeInvoiceViewModal() {
            const modal = document.getElementById('invoiceViewModal');
            if (modal) modal.remove();
        }

        // Purchase Order functions
        function viewPurchaseOrder(id) {
            fetch('?ajax=view_purchase_order&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const po = data.data;
                        showPurchaseOrderViewModal(po);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading purchase order:', error);
                    alert('Unable to load purchase order data.');
                });
        }

        function editPurchaseOrder(id) {
            fetch('?ajax=view_purchase_order&id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw PO response:', text); // Debug log
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e, 'Raw text:', text);
                        throw new Error('Invalid JSON response');
                    }
                    
                    if (data.success) {
                        const po = data.data;
                        resetPurchaseOrderForm();
                        
                        // Populate form with existing data with null checks
                        const supplierName = document.querySelector('#purchaseOrderModal input[name="supplier_name"]');
                        const supplierEmail = document.querySelector('#purchaseOrderModal input[name="supplier_email"]');
                        const supplierPhone = document.querySelector('#purchaseOrderModal input[name="supplier_phone"]');
                        const projectSelect = document.querySelector('#purchaseOrderModal select[name="project_id"]');
                        const orderDate = document.querySelector('#purchaseOrderModal input[name="order_date"]');
                        const expectedDelivery = document.querySelector('#purchaseOrderModal input[name="expected_delivery"]');
                        const vatRate = document.querySelector('#purchaseOrderModal input[name="vat_rate"]');
                        const notes = document.querySelector('#purchaseOrderModal textarea[name="notes"]');
                        const title = document.querySelector('#purchaseOrderModal h3');
                        const button = document.querySelector('#purchaseOrderModal button[name="create_purchase_order"]');
                        
                        if (supplierName) supplierName.value = po.supplier_name || '';
                        if (supplierEmail) supplierEmail.value = po.supplier_email || '';
                        if (supplierPhone) supplierPhone.value = po.supplier_phone || '';
                        if (projectSelect) projectSelect.value = po.project_id || '';
                        if (orderDate) orderDate.value = po.order_date || '';
                        if (expectedDelivery) expectedDelivery.value = po.expected_delivery || '';
                        if (vatRate) vatRate.value = (po.vat_rate * 100) || 15;
                        if (notes) notes.value = po.notes || '';
                        
                        // Configure for edit mode with null checks
                        if (title) title.textContent = '✏️ Edit Purchase Order #' + id;
                        if (button) {
                            button.name = 'update_purchase_order';
                            button.innerHTML = '💾 Update Purchase Order';
                        }
                        
                        // Add hidden field for PO ID
                        let poIdField = document.querySelector('#purchaseOrderModal input[name="po_id"]');
                        if (!poIdField) {
                            poIdField = document.createElement('input');
                            poIdField.type = 'hidden';
                            poIdField.name = 'po_id';
                            document.querySelector('#purchaseOrderModal form').appendChild(poIdField);
                        }
                        poIdField.value = id;
                        
                        // Populate items if they exist
                        if (po.items && po.items.length > 0) {
                            const itemsContainer = document.getElementById('po-modal-items'); // Fixed ID to match HTML
                            if (itemsContainer) {
                                itemsContainer.innerHTML = ''; // Clear existing items
                                purchaseOrderItemCount = 0;
                                
                                po.items.forEach(item => {
                                    const newItem = document.createElement('div');
                                    newItem.className = 'item-row';
                                    newItem.innerHTML = `
                                        <input type="text" name="items[${purchaseOrderItemCount}][description]" placeholder="Description" value="${item.description}" required oninput="updatePOTotals()">
                                        <input type="number" name="items[${purchaseOrderItemCount}][quantity]" placeholder="Quantity" value="${item.quantity}" step="0.01" required oninput="updatePOTotals()">
                                        <input type="number" name="items[${purchaseOrderItemCount}][unit_price]" placeholder="Unit Price" value="${item.unit_price}" step="0.01" required oninput="updatePOTotals()">
                                        <span class="total-display">R ${(item.quantity * item.unit_price).toFixed(2)}</span>
                                        <button type="button" class="remove-item-btn" onclick="removePOItem(this)">Remove</button>
                                    `;
                                    itemsContainer.appendChild(newItem);
                                    purchaseOrderItemCount++;
                                });
                                updatePOTotals(); // Fixed function name to match HTML
                            }
                        }
                        
                        document.getElementById('purchaseOrderModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading purchase order:', error);
                    alert('Unable to load purchase order data for editing: ' + error.message);
                });
        }

        function printPurchaseOrderPDF(id) {
            window.open('finance_pdf.php?type=purchase_order&id=' + id, '_blank');
        }

        function showPurchaseOrderViewModal(po) {
            // Create view modal HTML
            const viewModal = document.createElement('div');
            viewModal.id = 'purchaseOrderViewModal';
            viewModal.className = 'modal';
            viewModal.innerHTML = `
                <div class="modal-content" style="max-width: 800px;">
                    <span class="close" onclick="closePurchaseOrderViewModal()">&times;</span>
                    <h3>📦 Purchase Order Details #${po.id}</h3>
                    
                    <div class="view-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
                        <div><strong>Supplier:</strong> ${po.supplier_name || 'N/A'}</div>
                        <div><strong>Email:</strong> ${po.supplier_email || 'N/A'}</div>
                        <div><strong>Phone:</strong> ${po.supplier_phone || 'N/A'}</div>
                        <div><strong>Project:</strong> ${po.project_name || 'N/A'}</div>
                        <div><strong>Order Date:</strong> ${po.order_date}</div>
                        <div><strong>Expected Delivery:</strong> ${po.expected_delivery}</div>
                        <div><strong>Status:</strong> ${po.status}</div>
                        <div><strong>VAT Rate:</strong> ${(po.vat_rate * 100).toFixed(1)}%</div>
                        <div><strong>Total:</strong> R ${parseFloat(po.total_amount).toFixed(2)}</div>
                    </div>
                    
                    ${po.notes ? `<div style="margin: 1rem 0;"><strong>Notes:</strong><br>${po.notes}</div>` : ''}
                    
                    <h4>Items:</h4>
                    <table class="view-items-table" style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Description</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Qty</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Unit Price</th>
                                <th style="border: 1px solid #ddd; padding: 0.5rem;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${po.items ? po.items.map(item => `
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.description}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">${item.quantity}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td style="border: 1px solid #ddd; padding: 0.5rem;">R ${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" style="text-align: center; padding: 1rem;">No items found</td></tr>'}
                        </tbody>
                    </table>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        ${po.status === 'approved' || po.status === 'completed' ? `<button onclick="printPurchaseOrderPDF(${po.id})" class="btn">📄 Print PDF</button>` : '<small>PDF available when approved</small>'}
                        <button onclick="closePurchaseOrderViewModal()" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('purchaseOrderViewModal');
            if (existingModal) existingModal.remove();
            
            // Add to body and show
            document.body.appendChild(viewModal);
            viewModal.style.display = 'block';
        }

        function closePurchaseOrderViewModal() {
            const modal = document.getElementById('purchaseOrderViewModal');
            if (modal) modal.remove();
        }

        // Print PDF functions
        function printInvoicePDF(id) {
            window.open('finance_pdf.php?type=invoice&id=' + id, '_blank');
        }
        
        function editInvoice(id) {
            // Fetch invoice data and populate edit modal  
            fetch('?ajax=view_invoice&id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text); // Debug log
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e, 'Raw text:', text);
                        throw new Error('Invalid JSON response');
                    }
                    
                    if (data.success) {
                        const invoice = data.data;
                        resetInvoiceForm();
                        
                        // Populate form with existing data with null checks
                        const clientSelect = document.querySelector('#invoiceModal select[name="client_id"]');
                        const invoiceDate = document.querySelector('#invoiceModal input[name="invoice_date"]');
                        const dueDate = document.querySelector('#invoiceModal input[name="due_date"]');
                        const vatRate = document.querySelector('#invoiceModal input[name="vat_rate"]');
                        const notes = document.querySelector('#invoiceModal textarea[name="notes"]');
                        const title = document.querySelector('#invoiceModal h3');
                        const button = document.querySelector('#invoiceModal button[name="create_invoice"]');
                        
                        if (clientSelect) clientSelect.value = invoice.client_id || '';
                        if (invoiceDate) invoiceDate.value = invoice.invoice_date || '';
                        if (dueDate) dueDate.value = invoice.due_date || '';
                        if (vatRate) vatRate.value = (invoice.vat_rate * 100) || 15;
                        if (notes) notes.value = invoice.notes || '';
                        
                        // Configure for edit mode with null checks
                        if (title) title.textContent = '✏️ Edit Invoice #' + id;
                        if (button) {
                            button.name = 'update_invoice';
                            button.innerHTML = '💾 Update Invoice';
                        }
                        
                        // Add hidden field for invoice ID
                        let invoiceIdField = document.querySelector('#invoiceModal input[name="invoice_id"]');
                        if (!invoiceIdField) {
                            invoiceIdField = document.createElement('input');
                            invoiceIdField.type = 'hidden';
                            invoiceIdField.name = 'invoice_id';
                            document.querySelector('#invoiceModal form').appendChild(invoiceIdField);
                        }
                        invoiceIdField.value = id;
                        
                        // Populate items if they exist
                        if (invoice.items && invoice.items.length > 0) {
                            const itemsContainer = document.getElementById('invoice-items');
                            itemsContainer.innerHTML = ''; // Clear existing items
                            invoiceItemCount = 0;
                            
                            invoice.items.forEach(item => {
                                const newItem = document.createElement('div');
                                newItem.className = 'item-row';
                                newItem.innerHTML = `
                                    <input type="text" name="items[${invoiceItemCount}][description]" placeholder="Description" value="${item.description}" required oninput="updateInvoiceTotals()">
                                    <input type="number" name="items[${invoiceItemCount}][quantity]" placeholder="Quantity" value="${item.quantity}" step="0.01" required oninput="updateInvoiceTotals()">
                                    <input type="number" name="items[${invoiceItemCount}][unit_price]" placeholder="Unit Price" value="${item.unit_price}" step="0.01" required oninput="updateInvoiceTotals()">
                                    <span class="total-display">R ${(item.quantity * item.unit_price).toFixed(2)}</span>
                                    <button type="button" class="remove-item-btn" onclick="removeInvoiceItem(this)">Remove</button>
                                `;
                                itemsContainer.appendChild(newItem);
                                invoiceItemCount++;
                            });
                            updateInvoiceTotals();
                        }
                        
                        document.getElementById('invoiceModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading invoice:', error);
                    alert('Unable to load invoice data for editing: ' + error.message);
                });
        }
        
        function updatePOStatus(poId, newStatus) {
            if (!newStatus) return;
            if (confirm('Update Purchase Order status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action  = '?view=purchase_orders';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="update_po_status" value="1">
                    <input type="hidden" name="po_id" value="${poId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateQuotationStatus(quotationId, newStatus) {
            if (!newStatus) return;
            if (confirm('Update Quotation status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action  = '?view=quotations';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="update_quotation_status" value="1">
                    <input type="hidden" name="quotation_id" value="${quotationId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateInvoiceStatus(invoiceId, newStatus) {
            if (!newStatus) return;
            if (confirm('Update Invoice status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action  = '?view=invoices';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="update_invoice_status" value="1">
                    <input type="hidden" name="invoice_id" value="${invoiceId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        
        // Quotation item management
        function addQuotationItem() {
            const container = document.getElementById('quotation-items');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <input type="text" name="items[${quotationItemCount}][description]" placeholder="Description" required oninput="updateQuotationTotals()">
                <input type="number" name="items[${quotationItemCount}][quantity]" placeholder="Quantity" step="0.01" required oninput="updateQuotationTotals()">
                <input type="number" name="items[${quotationItemCount}][unit_price]" placeholder="Unit Price" step="0.01" required oninput="updateQuotationTotals()">
                <span class="total-display">R 0.00</span>
                <button type="button" class="remove-item-btn" onclick="removeQuotationItem(this)">Remove</button>
            `;
            container.appendChild(newItem);
            quotationItemCount++;
            updateQuotationTotals();
        }
        
        function removeQuotationItem(button) {
            if (document.querySelectorAll('#quotation-items .item-row').length > 1) {
                button.parentElement.remove();
                updateQuotationTotals();
            }
        }
        
        function updateQuotationTotals() {
            const rows = document.querySelectorAll('#quotation-items .item-row');
            let grandTotal = 0;
            
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
                const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
                const total = quantity * unitPrice;
                if (row.querySelector('.total-display')) {
                    row.querySelector('.total-display').textContent = `R ${total.toFixed(2)}`;
                }
                grandTotal += total;
            });
            
            const vatRate = parseFloat(document.querySelector('#quotationModal input[name="vat_rate"]')?.value || 15) / 100;
            const vatAmount = grandTotal * vatRate;
            const totalWithVat = grandTotal + vatAmount;
            
            if (document.getElementById('quotation-subtotal')) {
                document.getElementById('quotation-subtotal').textContent = `R ${grandTotal.toFixed(2)}`;
            }
            if (document.getElementById('quotation-vat')) {
                document.getElementById('quotation-vat').textContent = `R ${vatAmount.toFixed(2)}`;
            }
            if (document.getElementById('quotation-total')) {
                document.getElementById('quotation-total').textContent = `R ${totalWithVat.toFixed(2)}`;
            }
        }
        
        // Invoice item management
        function addInvoiceItem() {
            const container = document.getElementById('invoice-items');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <input type="text" name="items[${invoiceItemCount}][description]" placeholder="Description" required oninput="updateInvoiceTotals()">
                <input type="number" name="items[${invoiceItemCount}][quantity]" placeholder="Quantity" step="0.01" required oninput="updateInvoiceTotals()">
                <input type="number" name="items[${invoiceItemCount}][unit_price]" placeholder="Unit Price" step="0.01" required oninput="updateInvoiceTotals()">
                <span class="total-display">R 0.00</span>
                <button type="button" class="remove-item-btn" onclick="removeInvoiceItem(this)">Remove</button>
            `;
            container.appendChild(newItem);
            invoiceItemCount++;
            updateInvoiceTotals();
        }
        
        function removeInvoiceItem(button) {
            if (document.querySelectorAll('#invoice-items .item-row').length > 1) {
                button.parentElement.remove();
                updateInvoiceTotals();
            }
        }
        
        function updateInvoiceTotals() {
            const rows = document.querySelectorAll('#invoice-items .item-row');
            let grandTotal = 0;
            
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
                const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
                const total = quantity * unitPrice;
                if (row.querySelector('.total-display')) {
                    row.querySelector('.total-display').textContent = `R ${total.toFixed(2)}`;
                }
                grandTotal += total;
            });
            
            const vatRate = parseFloat(document.querySelector('#invoiceModal input[name="vat_rate"]')?.value || 15) / 100;
            const vatAmount = grandTotal * vatRate;
            const totalWithVat = grandTotal + vatAmount;
            
            if (document.getElementById('invoice-subtotal')) {
                document.getElementById('invoice-subtotal').textContent = `R ${grandTotal.toFixed(2)}`;
            }
            if (document.getElementById('invoice-vat')) {
                document.getElementById('invoice-vat').textContent = `R ${vatAmount.toFixed(2)}`;
            }
            if (document.getElementById('invoice-total')) {
                document.getElementById('invoice-total').textContent = `R ${totalWithVat.toFixed(2)}`;
            }
        }
        
        // Missing functions that are called in onclick handlers
        function convertToInvoice(quotationId) {
            if (confirm('Are you sure you want to convert this quotation to an invoice? This cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <?php echo Security::getCSRFTokenField(); ?>
                    <input type="hidden" name="convert_to_invoice" value="1">
                    <input type="hidden" name="quotation_id" value="${quotationId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function recordPayment(invoiceId, outstandingAmount) {
            const amount = prompt('Enter payment amount (Outstanding: R' + outstandingAmount.toFixed(2) + '):', outstandingAmount.toFixed(2));
            
            if (amount !== null && amount !== '') {
                const paymentAmount = parseFloat(amount);
                if (paymentAmount > 0 && paymentAmount <= outstandingAmount) {
                    // Create form and submit payment
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="record_payment" value="1">
                        <input type="hidden" name="invoice_id" value="${invoiceId}">
                        <input type="hidden" name="payment_amount" value="${paymentAmount}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert('Please enter a valid payment amount between R0.01 and R' + outstandingAmount.toFixed(2));
                }
            }
        }
        
        // Project Revenue Functions
        function viewRevenue(revenueData) {
            alert(`💵 Project Revenue Details\n\n` +
                  `Project: ${revenueData.project_name || 'N/A'}\n` +
                  `Client: ${revenueData.client_name || 'N/A'}\n` +
                  `Revenue Type: ${revenueData.revenue_type}\n` +
                  `Amount: R ${parseFloat(revenueData.amount).toLocaleString('en-ZA', {minimumFractionDigits: 2})}\n` +
                  `Received Date: ${new Date(revenueData.received_date).toLocaleDateString('en-ZA')}\n` +
                  `Payment Method: ${revenueData.payment_method || 'N/A'}\n` +
                  `Reference: ${revenueData.reference_number || 'N/A'}\n` +
                  `Notes: ${revenueData.notes || 'N/A'}`);
        }
        
        function editRevenue(revenueData) {
            // Populate the revenue modal with existing data
            document.querySelector('#projectRevenueModal input[name="project_id"]').value = revenueData.project_id || '';
            document.querySelector('#projectRevenueModal input[name="client_id"]').value = revenueData.client_id || '';
            document.querySelector('#projectRevenueModal select[name="revenue_type"]').value = revenueData.revenue_type || '';
            document.querySelector('#projectRevenueModal input[name="amount"]').value = revenueData.amount || '';
            document.querySelector('#projectRevenueModal input[name="received_date"]').value = revenueData.received_date || '';
            document.querySelector('#projectRevenueModal select[name="payment_method"]').value = revenueData.payment_method || '';
            document.querySelector('#projectRevenueModal input[name="reference_number"]').value = revenueData.reference_number || '';
            document.querySelector('#projectRevenueModal textarea[name="notes"]').value = revenueData.notes || '';
            
            // Add hidden field for revenue ID to enable updates
            let revenueIdField = document.querySelector('#projectRevenueModal input[name="revenue_id"]');
            if (!revenueIdField) {
                revenueIdField = document.createElement('input');
                revenueIdField.type = 'hidden';
                revenueIdField.name = 'revenue_id';
                document.querySelector('#projectRevenueModal form').appendChild(revenueIdField);
            }
            revenueIdField.value = revenueData.id;
            
            // Change form action to update
            document.querySelector('#projectRevenueModal button[name="record_project_revenue"]').name = 'update_project_revenue';
            document.querySelector('#projectRevenueModal button[name="update_project_revenue"]').innerHTML = '💾 Update Revenue';
            
            // Change modal title
            document.querySelector('#projectRevenueModal h3').textContent = '✏️ Edit Project Revenue';
            
            // Show modal
            document.getElementById('projectRevenueModal').style.display = 'block';
        }
        
        function resetRevenueForm() {
            // Reset form to create new revenue mode
            document.querySelector('#projectRevenueModal form').reset();
            document.querySelector('#projectRevenueModal button[name="update_project_revenue"]').name = 'record_project_revenue';
            document.querySelector('#projectRevenueModal button[name="record_project_revenue"]').innerHTML = '💾 Record Revenue';
            document.querySelector('#projectRevenueModal h3').textContent = '💵 Record Project Revenue';
            
            // Remove revenue_id field if exists
            const revenueIdField = document.querySelector('#projectRevenueModal input[name="revenue_id"]');
            if (revenueIdField) {
                revenueIdField.remove();
            }
        }
        
        function confirmDeleteRevenue(revenueId, projectName, amount) {
            document.getElementById('deleteRevenueId').value = revenueId;
            document.getElementById('deleteRevenueDetails').innerHTML = 
                `<strong>Project:</strong> ${projectName}<br>` +
                `<strong>Amount:</strong> R ${parseFloat(amount).toLocaleString('en-ZA', {minimumFractionDigits: 2})}`;
            document.getElementById('deleteRevenueModal').style.display = 'block';
        }
    </script>

    <!-- Quotation Modal -->
    <div id="quotationModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Create New Quotation</h3>
                <span onclick="closeModal('quotationModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Client:</label>
                        <select name="client_id" required>
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quotation Date:</label>
                        <input type="date" name="quotation_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Valid Until:</label>
                        <input type="date" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>VAT Rate (%):</label>
                        <input type="number" name="vat_rate" step="0.01" value="15" required oninput="updateQuotationTotals()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                
                <h4>Items</h4>
                <div id="quotation-items">
                    <div class="item-row">
                        <input type="text" name="items[0][description]" placeholder="Description" required oninput="updateQuotationTotals()">
                        <input type="number" name="items[0][quantity]" placeholder="Quantity" step="0.01" required oninput="updateQuotationTotals()">
                        <input type="number" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" required oninput="updateQuotationTotals()">
                        <span class="total-display">R 0.00</span>
                        <button type="button" class="remove-item-btn" onclick="removeQuotationItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addQuotationItem()" class="btn btn-secondary" style="margin-top: 10px;">+ Add Item</button>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4>Quotation Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Subtotal:</span>
                        <span id="quotation-subtotal">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>VAT (15%):</span>
                        <span id="quotation-vat">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 10px 0; padding-top: 10px; border-top: 2px solid #ddd; font-weight: bold; font-size: 1.1em;">
                        <span>Total:</span>
                        <span id="quotation-total">R 0.00</span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="create_quotation" class="btn">Create Quotation</button>
                    <button type="button" onclick="closeModal('quotationModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div id="invoiceModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Create New Invoice</h3>
                <span onclick="closeModal('invoiceModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Client:</label>
                        <select name="client_id" required>
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Invoice Date:</label>
                        <input type="date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Due Date:</label>
                        <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>VAT Rate (%):</label>
                        <input type="number" name="vat_rate" step="0.01" value="15" required oninput="updateInvoiceTotals()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                
                <h4>Items</h4>
                <div id="invoice-items">
                    <div class="item-row">
                        <input type="text" name="items[0][description]" placeholder="Description" required oninput="updateInvoiceTotals()">
                        <input type="number" name="items[0][quantity]" placeholder="Quantity" step="0.01" required oninput="updateInvoiceTotals()">
                        <input type="number" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" required oninput="updateInvoiceTotals()">
                        <span class="total-display">R 0.00</span>
                        <button type="button" class="remove-item-btn" onclick="removeInvoiceItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addInvoiceItem()" class="btn btn-secondary" style="margin-top: 10px;">+ Add Item</button>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4>Invoice Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Subtotal:</span>
                        <span id="invoice-subtotal">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>VAT (15%):</span>
                        <span id="invoice-vat">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 10px 0; padding-top: 10px; border-top: 2px solid #ddd; font-weight: bold; font-size: 1.1em;">
                        <span>Total:</span>
                        <span id="invoice-total">R 0.00</span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="create_invoice" class="btn">Create Invoice</button>
                    <button type="button" onclick="closeModal('invoiceModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase Order Modal -->
    <div id="purchaseOrderModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Create New Purchase Order</h3>
                <span onclick="closeModal('purchaseOrderModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Supplier Name:</label>
                        <input type="text" name="supplier_name" required>
                    </div>
                    <div class="form-group">
                        <label>Supplier Email:</label>
                        <input type="email" name="supplier_email" required>
                    </div>
                    <div class="form-group">
                        <label>Supplier Phone:</label>
                        <input type="tel" name="supplier_phone">
                    </div>
                    <div class="form-group">
                        <label>Project (Optional):</label>
                        <select name="project_id">
                            <option value="">Select Project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo Security::escapeHTML($project['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Order Date:</label>
                        <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Expected Delivery:</label>
                        <input type="date" name="expected_delivery" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                    <div class="form-group">
                        <label>VAT Rate (%):</label>
                        <input type="number" name="vat_rate" step="0.01" value="15" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                
                <h4>Items</h4>
                <div id="po-modal-items">
                    <div class="item-row">
                        <input type="text" name="items[0][description]" placeholder="Description" required>
                        <input type="number" name="items[0][quantity]" placeholder="Quantity" step="0.01" required oninput="updatePOTotals()">
                        <input type="number" name="items[0][unit_price]" placeholder="Unit Price" step="0.01" required oninput="updatePOTotals()">
                        <span class="total-display">R 0.00</span>
                        <button type="button" class="remove-item-btn" onclick="removePOItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addPOItem()" class="btn btn-secondary" style="margin-top: 10px;">+ Add Item</button>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4>Order Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Subtotal:</span>
                        <span id="po-subtotal">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>VAT (15%):</span>
                        <span id="po-vat">R 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 10px 0; padding-top: 10px; border-top: 2px solid #ddd; font-weight: bold; font-size: 1.1em;">
                        <span>Total:</span>
                        <span id="po-total">R 0.00</span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="create_purchase_order" class="btn">Create Purchase Order</button>
                    <button type="button" onclick="closeModal('purchaseOrderModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Project Revenue Modal -->
    <div id="projectRevenueModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="revenueModalTitle">Record Project Revenue</h3>
                <span onclick="closeModal('projectRevenueModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <form method="POST" id="revenueForm">
                <?php echo Security::getCSRFTokenField(); ?>
                <input type="hidden" name="revenue_id" id="revenue_id" value="">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Project *:</label>
                        <select name="project_id" id="project_id" required>
                            <option value="">Select Project...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo Security::escapeHTML($project['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Client *:</label>
                        <select name="client_id" id="client_id" required>
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name']); ?> - <?php echo Security::escapeHTML($client['company']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Revenue Type *:</label>
                        <select name="revenue_type" id="revenue_type" required>
                            <option value="">Select Type...</option>
                            <option value="milestone">Milestone Payment</option>
                            <option value="final">Final Payment</option>
                            <option value="deposit">Deposit</option>
                            <option value="retainer">Retainer</option>
                            <option value="bonus">Bonus</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (ZAR) *:</label>
                        <input type="number" name="amount" id="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Received Date *:</label>
                        <input type="date" name="received_date" id="received_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method:</label>
                        <select name="payment_method" id="payment_method">
                            <option value="">Select Method...</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" placeholder="Transaction/Invoice reference">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea name="notes" id="notes" placeholder="Additional notes about this revenue..."></textarea>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="record_project_revenue" id="submitRevenueBtn" class="btn">Record Revenue</button>
                    <button type="button" onclick="closeModal('projectRevenueModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Revenue View Modal -->
    <div id="revenueViewModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Revenue Details</h3>
                <span onclick="closeModal('revenueViewModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <div id="revenueViewContent" class="form-grid">
                <!-- Content will be populated by JavaScript -->
            </div>
            
            <div style="margin-top: 20px;">
                <button type="button" onclick="closeModal('revenueViewModal')" class="btn">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteRevenueModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>⚠️ Confirm Delete Project Revenue</h3>
                <span onclick="closeModal('deleteRevenueModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p><strong>Are you sure you want to delete this project revenue record?</strong></p>
                <div id="deleteRevenueDetails" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0; border-left: 4px solid #dc3545;"></div>
                <p style="color: #dc3545; font-weight: bold;">⚠️ This action cannot be undone and will also remove the corresponding money flow entries.</p>
            </div>
            
            <form id="deleteRevenueForm" method="POST">
                <?php echo Security::getCSRFTokenField(); ?>
                <input type="hidden" id="deleteRevenueId" name="revenue_id" value="">
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('deleteRevenueModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="delete_project_revenue" class="btn btn-danger">🗑️ Delete Revenue</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Modal -->
    <div id="expenseModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.4);">
        <div style="background:#fefefe;margin:5% auto;padding:20px;border:1px solid #888;width:90%;max-width:560px;border-radius:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3>Submit Expense Claim</h3>
                <span onclick="closeModal('expenseModal')" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;">&times;</span>
            </div>
            <form method="POST" action="?view=expenses">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select category...</option>
                            <option value="office">Office Supplies</option>
                            <option value="travel">Travel</option>
                            <option value="equipment">Equipment</option>
                            <option value="utilities">Utilities</option>
                            <option value="software">Software / Subscriptions</option>
                            <option value="marketing">Marketing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (ZAR) *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Expense Date *</label>
                        <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Project (optional)</label>
                        <select name="project_id">
                            <option value="">— None —</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo Security::escapeHTML($project['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="3" placeholder="What was this expense for?" required></textarea>
                </div>
                <div style="margin-top:16px;display:flex;gap:10px;">
                    <button type="submit" name="create_expense" value="1" class="btn">Submit Expense</button>
                    <button type="button" onclick="closeModal('expenseModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Money Flow Modal -->
    <div id="moneyFlowModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add Money Flow Entry</h3>
                <span onclick="closeModal('moneyFlowModal')" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            <p>Money flow entry form coming soon...</p>
            <button type="button" onclick="closeModal('moneyFlowModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
    
    <script>
        // Project Revenue functions
        function viewRevenue(revenue) {
            const content = document.getElementById('revenueViewContent');
            content.innerHTML = `
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Project:</span>
                    <span>${escapeHtml(revenue.project_name || 'N/A')}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Client:</span>
                    <span>${escapeHtml(revenue.client_name || 'N/A')}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Revenue Type:</span>
                    <span><span class="status-badge status-accepted">${revenue.revenue_type.charAt(0).toUpperCase() + revenue.revenue_type.slice(1)}</span></span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Amount:</span>
                    <span class="amount income">R ${parseFloat(revenue.amount).toLocaleString('en-ZA', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Received Date:</span>
                    <span>${revenue.received_date || 'N/A'}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Payment Method:</span>
                    <span>${escapeHtml(revenue.payment_method || 'N/A')}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: bold;">Reference Number:</span>
                    <span>${escapeHtml(revenue.reference_number || 'N/A')}</span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                    <span style="font-weight: bold;">Notes:</span>
                    <span>${escapeHtml(revenue.notes || 'N/A')}</span>
                </div>
            `;
            document.getElementById('revenueViewModal').style.display = 'block';
        }
        
        function editRevenue(revenue) {
            // Set modal title
            document.getElementById('revenueModalTitle').textContent = 'Edit Project Revenue';
            document.getElementById('submitRevenueBtn').textContent = 'Update Revenue';
            document.getElementById('submitRevenueBtn').name = 'update_project_revenue';
            
            // Populate form fields
            document.getElementById('revenue_id').value = revenue.id;
            document.getElementById('project_id').value = revenue.project_id;
            document.getElementById('client_id').value = revenue.client_id;
            document.getElementById('revenue_type').value = revenue.revenue_type;
            document.getElementById('amount').value = revenue.amount;
            document.getElementById('received_date').value = revenue.received_date;
            document.getElementById('payment_method').value = revenue.payment_method || '';
            document.getElementById('reference_number').value = revenue.reference_number || '';
            document.getElementById('notes').value = revenue.notes || '';
            
            // Show modal
            document.getElementById('projectRevenueModal').style.display = 'block';
        }
        
        function resetRevenueForm() {
            document.getElementById('revenueModalTitle').textContent = 'Record Project Revenue';
            document.getElementById('submitRevenueBtn').textContent = 'Record Revenue';
            document.getElementById('submitRevenueBtn').name = 'record_project_revenue';
            document.getElementById('revenueForm').reset();
            document.getElementById('revenue_id').value = '';
            document.getElementById('received_date').value = '<?php echo date('Y-m-d'); ?>';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Delete revenue confirmation function
        function confirmDeleteRevenue(revenueId, projectName, amount) {
            const details = document.getElementById('deleteRevenueDetails');
            details.innerHTML = `
                <div style="margin-bottom: 0.5rem;"><strong>Project:</strong> ${escapeHtml(projectName)}</div>
                <div style="margin-bottom: 0.5rem;"><strong>Amount:</strong> R ${parseFloat(amount).toLocaleString('en-ZA', {minimumFractionDigits: 2})}</div>
                <div><strong>Revenue ID:</strong> ${revenueId}</div>
            `;
            
            document.getElementById('deleteRevenueId').value = revenueId;
            document.getElementById('deleteRevenueModal').style.display = 'block';
        }
        
        // Ensure modal opens with clean form
        document.addEventListener('DOMContentLoaded', function() {
            const addRevenueButton = document.querySelector('button[onclick*="projectRevenueModal"]');
            if (addRevenueButton) {
                addRevenueButton.addEventListener('click', resetRevenueForm);
            }
        });
    </script>
</body>
</html>
