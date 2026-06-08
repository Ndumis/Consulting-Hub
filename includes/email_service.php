<?php
// Enterprise Email Service for Finance System
// Integrates with Replit Mail service for automated email sending

class EmailService {
    
    private static function getReplitToken() {
        // Get authentication token from Replit environment
        $repl_identity = $_ENV['REPL_IDENTITY'] ?? false;
        $web_repl_renewal = $_ENV['WEB_REPL_RENEWAL'] ?? false;
        
        if ($repl_identity) {
            return "repl " . $repl_identity;
        } elseif ($web_repl_renewal) {
            return "depl " . $web_repl_renewal;
        }
        
        throw new Exception('No authentication token found. Please ensure you are running in Replit environment.');
    }
    
    public static function sendFinanceEmail($recipient, $subject, $message, $attachment_data = null) {
        try {
            $auth_token = self::getReplitToken();
            
            // Prepare email data
            $email_data = [
                'to' => $recipient,
                'subject' => $subject,
                'html' => $message,
                'text' => strip_tags($message)
            ];
            
            // Add attachment if provided
            if ($attachment_data) {
                $email_data['attachments'] = [$attachment_data];
            }
            
            // Send email via Replit Mail API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://connectors.replit.com/api/v2/mailer/send');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X_REPLIT_TOKEN: ' . $auth_token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $result = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email: ' . $response,
                    'http_code' => $http_code
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage()
            ];
        }
    }
    
    public static function sendQuotationEmail($quotation, $recipient_email) {
        $subject = "Quotation {$quotation['quotation_number']} - Business Management System";
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c5282; border-bottom: 2px solid #2c5282; padding-bottom: 10px;'>
                    New Quotation Available
                </h2>
                
                <p>Dear Valued Client,</p>
                
                <p>We are pleased to provide you with the following quotation:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Quotation Number:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$quotation['quotation_number']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Date:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($quotation['quotation_date'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Valid Until:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($quotation['valid_until'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Total Amount:</td>
                        <td style='padding: 8px; border: 1px solid #ddd; font-weight: bold; color: #2c5282;'>R " . number_format($quotation['total_amount'], 2) . "</td>
                    </tr>
                </table>
                
                <div style='background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #2c5282;'>📄 PDF Download Available</p>
                    <p style='margin: 5px 0 0 0;'>You can download the complete quotation PDF from our Finance system.</p>
                </div>
                
                <p>If you have any questions about this quotation, please don't hesitate to contact us.</p>
                
                <p>Thank you for your business!</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                    <p><strong>Business Management System</strong><br>
                    Professional Business Solutions<br>
                    Email: info@businessmanagement.co.za<br>
                    Phone: +27 (0) 11 123 4567</p>
                </div>
            </div>
        </body>
        </html>";
        
        return self::sendFinanceEmail($recipient_email, $subject, $message);
    }
    
    public static function sendInvoiceEmail($invoice, $recipient_email) {
        $status_color = $invoice['status'] === 'paid' ? '#28a745' : '#dc3545';
        $subject = "Invoice {$invoice['invoice_number']} - Payment " . ucfirst($invoice['status']);
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px;'>
                    Invoice Notification
                </h2>
                
                <p>Dear Valued Client,</p>
                
                <p>This email contains details about your invoice:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Invoice Number:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$invoice['invoice_number']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Invoice Date:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($invoice['invoice_date'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Due Date:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($invoice['due_date'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Status:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>
                            <span style='background: {$status_color}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;'>
                                " . strtoupper($invoice['status']) . "
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Total Amount:</td>
                        <td style='padding: 8px; border: 1px solid #ddd; font-weight: bold; color: #dc3545;'>R " . number_format($invoice['total_amount'], 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Amount Paid:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>R " . number_format($invoice['paid_amount'], 2) . "</td>
                    </tr>
                </table>";
        
        if ($invoice['status'] !== 'paid') {
            $outstanding = $invoice['total_amount'] - $invoice['paid_amount'];
            $message .= "
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #856404;'>💰 Payment Required</p>
                    <p style='margin: 5px 0 0 0;'>Outstanding Amount: <strong>R " . number_format($outstanding, 2) . "</strong></p>
                </div>";
        } else {
            $message .= "
                <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #155724;'>✅ Payment Complete</p>
                    <p style='margin: 5px 0 0 0;'>Thank you for your payment!</p>
                </div>";
        }
        
        $message .= "
                <div style='background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #2c5282;'>📄 PDF Download Available</p>
                    <p style='margin: 5px 0 0 0;'>You can download the complete invoice PDF from our Finance system.</p>
                </div>
                
                <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
                
                <p>Thank you for your business!</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                    <p><strong>Business Management System</strong><br>
                    Professional Business Solutions<br>
                    Email: info@businessmanagement.co.za<br>
                    Phone: +27 (0) 11 123 4567</p>
                </div>
            </div>
        </body>
        </html>";
        
        return self::sendFinanceEmail($recipient_email, $subject, $message);
    }
    
    public static function sendPurchaseOrderEmail($po, $recipient_email) {
        $subject = "Purchase Order {$po['po_number']} - " . ucfirst($po['status']);
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #38a169; border-bottom: 2px solid #38a169; padding-bottom: 10px;'>
                    Purchase Order Update
                </h2>
                
                <p>Dear Supplier,</p>
                
                <p>This email contains details about purchase order:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>PO Number:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$po['po_number']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Order Date:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($po['order_date'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Expected Delivery:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('d M Y', strtotime($po['expected_delivery'])) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Status:</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>
                            <span style='background: #38a169; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;'>
                                " . strtoupper($po['status']) . "
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>Total Amount:</td>
                        <td style='padding: 8px; border: 1px solid #ddd; font-weight: bold; color: #38a169;'>R " . number_format($po['total_amount'], 2) . "</td>
                    </tr>
                </table>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #38a169;'>📄 PDF Download Available</p>
                    <p style='margin: 5px 0 0 0;'>You can download the complete purchase order PDF from our system.</p>
                </div>
                
                <p>Please proceed with processing this order as specified.</p>
                
                <p>Thank you for your service!</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                    <p><strong>Business Management System</strong><br>
                    Professional Business Solutions<br>
                    Email: info@businessmanagement.co.za<br>
                    Phone: +27 (0) 11 123 4567</p>
                </div>
            </div>
        </body>
        </html>";
        
        return self::sendFinanceEmail($recipient_email, $subject, $message);
    }
}
?>