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
        
        // Verify quotation exists and can be updated (not converted)
        $query = "SELECT * FROM quotations WHERE id = ? AND created_by = ? AND status NOT IN ('converted', 'completed')";
        $stmt = $db->prepare($query);
        $stmt->execute([$quotation_id, $_SESSION['user_id']]);
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
        
        $success_message = "Quotation updated successfully. Email notification sent.";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Update failed: " . $e->getMessage();
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
        
        $success_message = "Quotation successfully converted to Invoice {$invoice_number}. Email sent to client.";
        
    } catch (Exception $e) {
        // Rollback transaction on failure
        $db->rollBack();
        $error_message = "Conversion failed: " . $e->getMessage();
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
        
        // Verify invoice exists and can be updated (not paid)
        $query = "SELECT * FROM invoices WHERE id = ? AND created_by = ? AND status != 'paid'";
        $stmt = $db->prepare($query);
        $stmt->execute([$invoice_id, $_SESSION['user_id']]);
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
        $success_message = "Invoice updated successfully";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Update failed: " . $e->getMessage();
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
        
        $success_message = "Invoice {$invoice_number} created successfully. Email sent to client.";
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
        
        $query = "UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_paid_amount, $status, $invoice_id]);
        
        // Record in money flow
        $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, client_id, invoice_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute(['income', 'Payment', $payment_amount, "Payment for invoice {$invoice['invoice_number']}", 
                       date('Y-m-d'), $invoice['client_id'], $invoice_id, $_SESSION['user_id']]);
    }
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
        $success_message = "Purchase order updated successfully";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Update failed: " . $e->getMessage();
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
}

// Handle purchase order status update
if ($_POST && isset($_POST['update_po_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $po_id = (int)$_POST['po_id'];
    $status = Security::sanitizeInput($_POST['status']);

    $query = "UPDATE purchase_orders SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $po_id]);
}

// Handle quotation status update
if ($_POST && isset($_POST['update_quotation_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $quotation_id = (int)$_POST['quotation_id'];
    $allowed = ['draft', 'sent', 'accepted', 'rejected'];
    $status = Security::sanitizeInput($_POST['status']);
    if (in_array($status, $allowed)) {
        $query = "UPDATE quotations SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $quotation_id]);
        $success_message = "Quotation status updated to " . ucfirst($status) . ".";
    }
}

// Handle invoice status update
if ($_POST && isset($_POST['update_invoice_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Finance');

    $invoice_id = (int)$_POST['invoice_id'];
    $allowed = ['pending', 'overdue', 'cancelled'];
    $status = Security::sanitizeInput($_POST['status']);
    if (in_array($status, $allowed)) {
        $query = "UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $invoice_id]);
        $success_message = "Invoice status updated to " . ucfirst($status) . ".";
    }
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
        
        // Update corresponding money_flow entry using MySQL approach
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
            $old_revenue['project_id'], $old_revenue['client_id'], $old_revenue['amount'], 
            $old_revenue['received_date'], $_SESSION['user_id'],
            ucfirst($revenue_type), $amount, $notes ?: "Project revenue - {$revenue_type}", 
            $received_date, $project_id, $client_id
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
        
        // Log the deletion for audit purposes
        $query = "INSERT INTO activity_log (user_id, action, details, created_at) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $_SESSION['user_id'], 
            'delete_project_revenue', 
            "Deleted project revenue ID {$revenue_id} for project {$revenue['project_id']}, amount R" . number_format($revenue['amount'], 2)
        ]);
    }
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
    
    $category = Security::sanitizeInput($_POST['category']);
    $amount = floatval($_POST['amount']);
    $expense_date = Security::sanitizeInput($_POST['expense_date']);
    $description = Security::sanitizeInput($_POST['description']);
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    
    $expense_number = Utils::generateExpenseNumber();
    $vat_amount = $amount * 0.15; // 15% VAT
    $total_amount = $amount + $vat_amount;
    
    $query = "INSERT INTO expenses (expense_number, category, amount, vat_amount, total_amount, expense_date, 
              description, project_id, status, submitted_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->execute([$expense_number, $category, $amount, $vat_amount, $total_amount, $expense_date, 
                   $description, $project_id, 'pending', $_SESSION['user_id']]);
    
    $expense_id = $stmt->fetchColumn();
    
    // Record in money flow
    $query = "INSERT INTO money_flow (transaction_type, category, amount, description, transaction_date, 
              project_id, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute(['expense', ucfirst(str_replace('_', ' ', $category)), $total_amount, "Expense: {$description} ({$expense_number})", 
                   $expense_date, $project_id, $_SESSION['user_id']]);
}


// Get current view parameter
$view = $_GET['view'] ?? 'money_flow';
$tab = $_GET['view'] ?? 'finance';

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

$total_revenue = array_sum(array_column($invoices, 'paid_amount'));
$outstanding_amount = array_sum(array_map(function($i) { return $i['total_amount'] - $i['paid_amount']; }, $invoices));
$total_expenses = array_sum(array_column($purchase_orders, 'total_amount'));

// Money flow statistics
$total_income = array_sum(array_map(function($mf) { return $mf['transaction_type'] == 'income' ? $mf['amount'] : 0; }, $money_flows));
$total_expense_flow = array_sum(array_map(function($mf) { return $mf['transaction_type'] == 'expense' ? $mf['amount'] : 0; }, $money_flows));
$net_cash_flow = $total_income - $total_expense_flow;

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
    <title>Enhanced Finance Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
       

        /* Tab content styling */
        .tab-content {
            display: none;
            padding: 1.5rem;
            background: white;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section {
            background: white;
            margin-bottom: 2rem;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section.no-nav {
            border-radius: 8px;
        }
        
        .section-header {
            background: #333;
            color: white;
            padding: 1rem 1.5rem;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #333;
        }
        
        .stat-card.income {
            border-left-color: #28a745;
        }
        
        .stat-card.expense {
            border-left-color: #dc3545;
        }
        
        .stat-card.neutral {
            border-left-color: #17a2b8;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-number.positive {
            color: #28a745;
        }
        
        .stat-number.negative {
            color: #dc3545;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #333;
            box-shadow: 0 0 0 2px rgba(51, 51, 51, 0.1);
        }
        
        textarea {
            height: 80px;
            resize: vertical;
        }
        
        .btn {
            background: #333;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #555;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-info {
            background: #17a2b8;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: left;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tr:hover {
            background: #f0f0f0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-draft { background: #fff3cd; color: #856404; }
        .status-sent { background: #cce5ff; color: #004085; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-converted { background: #e2e3e5; color: #6c757d; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-received { background: #d4edda; color: #155724; }
        .status-partially_paid { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .document-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            background: #fafafa;
            margin-bottom: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .document-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .document-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .items-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
        }
        
        .item-row:last-child {
            margin-bottom: 0;
        }
        
        .remove-item {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .totals-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
        }
        
        .totals-row.total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .hidden {
            display: none;
        }
        
        .category-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .category-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .category-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .amount {
            font-weight: bold;
        }
        
        .amount.income {
            color: #28a745;
        }
        
        .amount.expense {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <?php include '../includes/header.php'; ?>
    
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="nav-tabs">
            <button onclick="showTab('money_flow')" class="tab-btn <?php echo $view === 'money_flow' ? 'active' : ''; ?>">💸 Money Flow</button>
            <button onclick="showTab('quotations')" class="tab-btn <?php echo $view === 'quotations' ? 'active' : ''; ?>">📋 Quotations</button>
            <button onclick="showTab('invoices')" class="tab-btn <?php echo $view === 'invoices' ? 'active' : ''; ?>">🧾 Invoices</button>
            <button onclick="showTab('purchase_orders')" class="tab-btn <?php echo $view === 'purchase_orders' ? 'active' : ''; ?>">🛒 Purchase Orders</button>
            <button onclick="showTab('project_revenues')" class="tab-btn <?php echo $view === 'project_revenues' ? 'active' : ''; ?>">💵 Project Revenues</button>
        </div>
        
        <!-- Tab Contents -->
        <div id="overview" class="tab-content <?php echo $view === 'overview' ? 'active' : ''; ?>">
            <h3>Department Overview</h3>
            <p>Select a specific section from the navigation above to view detailed financial information.</p>
        </div>

        <div id="money_flow" class="tab-content <?php echo $view === 'money_flow' ? 'active' : ''; ?>">
                    <h3>💸 Financial Overview & Cash Flow</h3>
                    
                    <div class="stats-overview">
                        <div class="stat-card neutral">
                            <div class="stat-number"><?php echo $total_quotations; ?></div>
                            <div class="stat-label">Total Quotations</div>
                        </div>
                        <div class="stat-card neutral">
                            <div class="stat-number"><?php echo $pending_quotations; ?></div>
                            <div class="stat-label">Pending Quotations</div>
                        </div>
                        <div class="stat-card neutral">
                            <div class="stat-number"><?php echo $total_invoices; ?></div>
                            <div class="stat-label">Total Invoices</div>
                        </div>
                        <div class="stat-card expense">
                            <div class="stat-number"><?php echo $unpaid_invoices; ?></div>
                            <div class="stat-label">Unpaid Invoices</div>
                        </div>
                        <div class="stat-card neutral">
                            <div class="stat-number"><?php echo $total_purchase_orders; ?></div>
                            <div class="stat-label">Purchase Orders</div>
                        </div>
                        <div class="stat-card expense">
                            <div class="stat-number"><?php echo $pending_purchase_orders; ?></div>
                            <div class="stat-label">Pending POs</div>
                        </div>
                        <div class="stat-card income">
                            <div class="stat-number positive">R <?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-card expense">
                            <div class="stat-number negative">R <?php echo number_format($outstanding_amount, 2); ?></div>
                            <div class="stat-label">Outstanding Amount</div>
                        </div>
                        <div class="stat-card expense">
                            <div class="stat-number negative">R <?php echo number_format($total_expenses, 2); ?></div>
                            <div class="stat-label">Total Expenses</div>
                        </div>
                        <div class="stat-card income">
                            <div class="stat-number positive">R <?php echo number_format($total_income, 2); ?></div>
                            <div class="stat-label">Total Income</div>
                        </div>
                        <div class="stat-card expense">
                            <div class="stat-number negative">R <?php echo number_format($total_expense_flow, 2); ?></div>
                            <div class="stat-label">Total Expenses (Flow)</div>
                        </div>
                        <div class="stat-card <?php echo $net_cash_flow >= 0 ? 'income' : 'expense'; ?>">
                            <div class="stat-number <?php echo $net_cash_flow >= 0 ? 'positive' : 'negative'; ?>">R <?php echo number_format($net_cash_flow, 2); ?></div>
                            <div class="stat-label">Net Cash Flow</div>
                        </div>
                    </div>

                    <div class="category-breakdown">
                        <div class="category-section">
                            <div class="category-title">💰 Income by Category</div>
                            <?php if (empty($income_by_category)): ?>
                                <p style="color: #666;">No income recorded yet.</p>
                            <?php else: ?>
                                <?php foreach ($income_by_category as $category => $amount): ?>
                                    <div class="category-item">
                                        <span><?php echo Security::escapeHTML($category); ?></span>
                                        <span class="amount income">R <?php echo number_format($amount, 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="category-item" style="font-weight: bold; border-top: 2px solid #28a745; margin-top: 1rem; padding-top: 1rem;">
                                <span>Recent Income (30 days)</span>
                                <span class="amount income">R <?php echo number_format($recent_income, 2); ?></span>
                            </div>
                        </div>

                        <div class="category-section">
                            <div class="category-title">💸 Expenses by Category</div>
                            <?php if (empty($expense_by_category)): ?>
                                <p style="color: #666;">No expenses recorded yet.</p>
                            <?php else: ?>
                                <?php foreach ($expense_by_category as $category => $amount): ?>
                                    <div class="category-item">
                                        <span><?php echo Security::escapeHTML($category); ?></span>
                                        <span class="amount expense">R <?php echo number_format($amount, 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="category-item" style="font-weight: bold; border-top: 2px solid #dc3545; margin-top: 1rem; padding-top: 1rem;">
                                <span>Recent Expenses (30 days)</span>
                                <span class="amount expense">R <?php echo number_format($recent_expenses, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cash-flow-chart" style="margin-top: 2rem;">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                    
        </div>

        <div id="quotations" class="tab-content <?php echo $view === 'quotations' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <span>📋 Quotations Management</span>
                        <button class="btn btn-success" onclick="resetQuotationForm(); document.getElementById('quotationModal').style.display='block'">+ New Quotation</button>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quotation #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotations as $quotation): ?>
                            <tr>
                                <td><?php echo Security::escapeHTML($quotation['quotation_number']); ?></td>
                                <td><?php echo Security::escapeHTML($quotation['client_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($quotation['quotation_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($quotation['valid_until'])); ?></td>
                                <td>R <?php echo number_format($quotation['total_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $quotation['status']; ?>"><?php echo ucfirst($quotation['status']); ?></span></td>
                                <td><?php echo Security::escapeHTML($quotation['created_by_name'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-small" onclick="viewQuotation(<?php echo $quotation['id']; ?>)" title="View Details">👁️ View</button>
                                    <?php /* PDF always visible regardless of status */ ?>
                                    <button class="btn btn-small btn-primary" onclick="printQuotationPDF(<?php echo $quotation['id']; ?>)" title="Download PDF">📄 PDF</button>

                                    <?php if ($quotation['status'] === 'completed'): ?>
                                        <?php if ($quotation['converted_invoice_id']): ?>
                                            <button class="btn btn-small btn-info" onclick="viewInvoice(<?php echo $quotation['converted_invoice_id']; ?>)" title="View Created Invoice">📋 View Invoice</button>
                                            <a href="finance_pdf.php?type=invoice&id=<?php echo $quotation['converted_invoice_id']; ?>" target="_blank" class="btn btn-small btn-success" title="Download Invoice PDF">📄 Invoice PDF</a>
                                        <?php endif; ?>
                                        <span class="text-success" style="font-size: 0.8em;">✅ Completed</span>

                                    <?php elseif ($quotation['status'] === 'accepted'): ?>
                                        <?php /* Accepted = locked — no status change, just convert */ ?>
                                        <button class="btn btn-small btn-secondary" onclick="editQuotation(<?php echo $quotation['id']; ?>)" title="Edit Quotation">✏️ Edit</button>
                                        <button class="btn btn-small btn-success" onclick="convertToInvoice(<?php echo $quotation['id']; ?>)">🔄 Convert to Invoice</button>

                                    <?php else: ?>
                                        <button class="btn btn-small btn-secondary" onclick="editQuotation(<?php echo $quotation['id']; ?>)" title="Edit Quotation">✏️ Edit</button>
                                        <select onchange="updateQuotationStatus(<?php echo $quotation['id']; ?>, this.value)" class="btn btn-small" title="Update Status" style="cursor:pointer;">
                                            <option value="">🔄 Status</option>
                                            <option value="draft"    <?php echo $quotation['status']==='draft'    ?'selected':''; ?>>Draft</option>
                                            <option value="sent"     <?php echo $quotation['status']==='sent'     ?'selected':''; ?>>Sent</option>
                                            <option value="accepted" <?php echo $quotation['status']==='accepted' ?'selected':''; ?>>Accepted</option>
                                            <option value="rejected" <?php echo $quotation['status']==='rejected' ?'selected':''; ?>>Rejected</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
        </div>

        <div id="invoices" class="tab-content <?php echo $view === 'invoices' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <span>🧾 Invoices Management</span>
                        <button class="btn btn-success" onclick="resetInvoiceForm(); document.getElementById('invoiceModal').style.display='block'">+ New Invoice</button>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo Security::escapeHTML($invoice['invoice_number']); ?></td>
                                <td><?php echo Security::escapeHTML($invoice['client_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($invoice['invoice_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></td>
                                <td>R <?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td>R <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                <td>R <?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $invoice['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?></span></td>
                                <td class="action-buttons">
                                    <button class="btn btn-small" onclick="viewInvoice(<?php echo $invoice['id']; ?>)" title="View Details">👁️ View</button>
                                    <button class="btn btn-small btn-secondary" onclick="editInvoice(<?php echo $invoice['id']; ?>)" title="Edit Invoice">✏️ Edit</button>
                                    <button class="btn btn-small btn-primary" onclick="printInvoicePDF(<?php echo $invoice['id']; ?>)" title="Download PDF">📄 PDF</button>
                                    <?php if ($invoice['status'] === 'paid'): ?>
                                        <button class="btn btn-small btn-info" disabled>✅ Paid</button>
                                    <?php else: ?>
                                        <button class="btn btn-small btn-success" onclick="recordPayment(<?php echo $invoice['id']; ?>, <?php echo $invoice['total_amount'] - $invoice['paid_amount']; ?>)">💳 Record Payment</button>
                                        <select onchange="updateInvoiceStatus(<?php echo $invoice['id']; ?>, this.value)" class="btn btn-small" title="Update Status" style="cursor:pointer;">
                                            <option value="">🔄 Status</option>
                                            <option value="pending" <?php echo $invoice['status']==='pending'?'selected':''; ?>>Pending</option>
                                            <option value="overdue" <?php echo $invoice['status']==='overdue'?'selected':''; ?>>Overdue</option>
                                            <option value="cancelled" <?php echo $invoice['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
        </div>

        <div id="purchase_orders" class="tab-content <?php echo $view === 'purchase_orders' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <span>🛒 Purchase Orders Management</span>
                        <button class="btn btn-success" onclick="document.getElementById('purchaseOrderModal').style.display='block'">+ New Purchase Order</button>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Supplier</th>
                                <th>Project</th>
                                <th>Order Date</th>
                                <th>Expected Delivery</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchase_orders as $po): ?>
                            <tr>
                                <td><?php echo Security::escapeHTML($po['po_number']); ?></td>
                                <td>
                                    <strong><?php echo Security::escapeHTML($po['supplier_name']); ?></strong><br>
                                    <small><?php echo Security::escapeHTML($po['supplier_email']); ?></small>
                                </td>
                                <td><?php echo Security::escapeHTML($po['project_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($po['order_date'])); ?></td>
                                <td><?php echo $po['expected_delivery'] ? date('M j, Y', strtotime($po['expected_delivery'])) : 'N/A'; ?></td>
                                <td>R <?php echo number_format($po['total_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $po['status']; ?>"><?php echo ucfirst($po['status']); ?></span></td>
                                <td class="action-buttons">
                                    <button class="btn btn-small" onclick="viewPurchaseOrder(<?php echo $po['id']; ?>)" title="View Details">👁️ View</button>
                                    <button class="btn btn-small btn-secondary" onclick="editPurchaseOrder(<?php echo $po['id']; ?>)" title="Edit Purchase Order">✏️ Edit</button>
                                    <?php if (in_array($po['status'], ['approved', 'completed'])): ?>
                                        <button class="btn btn-small btn-primary" onclick="printPurchaseOrderPDF(<?php echo $po['id']; ?>)" title="Download PDF">📄 PDF</button>
                                    <?php else: ?>
                                        <button class="btn btn-small btn-primary" onclick="printPurchaseOrderPDF(<?php echo $po['id']; ?>)" title="Download PDF" disabled>📄 PDF</button>
                                    <?php endif; ?>
                                    <?php if ($po['status'] === 'pending'): ?>
                                        <select onchange="updatePOStatus(<?php echo $po['id']; ?>, this.value)" class="btn btn-small btn-warning" title="Update Status">
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="received">Received</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    <?php else: ?>
                                        <span class="btn btn-small btn-info" style="cursor: default;">Status: <?php echo ucfirst($po['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
        </div>

        <div id="project_revenues" class="tab-content <?php echo $view === 'project_revenues' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <span>💵 Project Revenues</span>
                        <button class="btn btn-success" onclick="resetRevenueForm(); document.getElementById('projectRevenueModal').style.display='block'">+ Record Revenue</button>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Revenue Type</th>
                                <th>Amount</th>
                                <th>Received Date</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_revenues as $revenue): ?>
                            <tr>
                                <td><?php echo Security::escapeHTML($revenue['project_name'] ?? 'N/A'); ?></td>
                                <td><?php echo Security::escapeHTML($revenue['client_name'] ?? 'N/A'); ?></td>
                                <td><span class="status-badge status-accepted"><?php echo ucfirst($revenue['revenue_type']); ?></span></td>
                                <td class="amount income">R <?php echo number_format($revenue['amount'], 2); ?></td>
                                <td><?php echo $revenue['received_date'] ? date('M j, Y', strtotime($revenue['received_date'])) : 'N/A'; ?></td>
                                <td><?php echo Security::escapeHTML($revenue['payment_method'] ?? 'N/A'); ?></td>
                                <td><?php echo Security::escapeHTML($revenue['reference_number'] ?? 'N/A'); ?></td>
                                <td><?php echo Security::escapeHTML($revenue['notes'] ?? 'N/A'); ?></td>
                                <td>
                                    <button onclick="viewRevenue(<?php echo htmlspecialchars(json_encode($revenue)); ?>)" class="btn btn-small">View</button>
                                    <button onclick="editRevenue(<?php echo htmlspecialchars(json_encode($revenue)); ?>)" class="btn btn-small">Edit</button>
                                    <button onclick="confirmDeleteRevenue(<?php echo $revenue['id']; ?>, '<?php echo addslashes($revenue['project_name'] ?? 'N/A'); ?>', <?php echo $revenue['amount']; ?>)" class="btn btn-small btn-danger">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
        </div>
    </div>
    
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

        // Global variables for modal item management
        var quotationItemCount = 1;
        var invoiceItemCount = 1;
        
        // Chart.js Cash Flow Chart (with guard)
        if (typeof Chart !== 'undefined') {
            const ctx = document.getElementById('cashFlowChart');
            if (ctx) {
                const cashFlowChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Revenue',
                            data: [<?php echo $overview['total_revenue'] ?? 0; ?>, 45000, 38000, 52000, 48000, 55000],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Expenses',
                            data: [<?php echo $overview['total_expenses'] ?? 0; ?>, 32000, 28000, 35000, 31000, 38000],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
        
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
        
        function printInvoicePDF(id) {
            window.open('finance_pdf.php?type=invoice&id=' + id, '_blank');
        }
        
        function updatePOStatus(poId, newStatus) {
            if (!newStatus) return;
            if (confirm('Update Purchase Order #' + poId + ' status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
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
            if (confirm('Update Quotation #' + quotationId + ' status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
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
            if (confirm('Update Invoice #' + invoiceId + ' status to: ' + newStatus + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
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