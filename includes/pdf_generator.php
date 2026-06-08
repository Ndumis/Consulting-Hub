<?php

class EnterprisePDFGenerator {

    // ── Company constants ────────────────────────────────────────────────────
    const COMPANY_NAME    = 'KConsulting';
    const COMPANY_PHONE   = '+27 64 519 0549';
    const COMPANY_EMAIL   = 'info@thekconsult.co.za';
    const COMPANY_WEBSITE = 'www.thekconsult.co.za';
    const COMPANY_ADDRESS = 'Northgate Spica Cres, Randburg, 2125';
    const COMPANY_REG     = '2023/655117/07';

    const BANK_NAME         = 'Capitec Bank';
    const BANK_ACCOUNT_TYPE = 'Capitec Business Account';
    const BANK_ACCOUNT_NO   = '1053718071';
    const BANK_BRANCH_CODE  = '450105';

    const TERMS_HEADING = 'STRATEGIC DEPLOYMENT & OPERATIONAL DIRECTIVES';
    const TERMS_BODY    = '<strong>Mobilization & Milestone Structure:</strong> A 50% technical commitment deposit is required to secure scheduling alignment and activate the primary engineering sprint. The remaining 50% balance is due immediately upon successful deployment and system hand-off.';

    // ── Public entry points ──────────────────────────────────────────────────
    public static function generateQuotationPDF($quotation, $items) {
        $html = self::buildQuotationHTML($quotation, $items);
        self::output($html, 'quotation-' . $quotation['quotation_number'] . '.pdf');
    }

    public static function generateInvoicePDF($invoice, $items) {
        $html = self::buildInvoiceHTML($invoice, $items);
        self::output($html, 'invoice-' . $invoice['invoice_number'] . '.pdf');
    }

    public static function generatePurchaseOrderPDF($po, $items) {
        $html = self::buildPurchaseOrderHTML($po, $items);
        self::output($html, 'purchase-order-' . $po['po_number'] . '.pdf');
    }

    // ── Shared CSS ───────────────────────────────────────────────────────────
    private static function baseCSS() {
        return '
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            line-height: 1.45;
            background: #fff;
        }

        /* ── Two-column header ── */
        .doc-header { width: 100%; margin-bottom: 18px; }
        .doc-header td { vertical-align: top; }
        .col-logo  { width: 55%; padding-right: 15px; }
        .col-title { width: 45%; text-align: right; }

        .logo-wrap img { height: 56px; width: auto; }
        .logo-wrap .logo-name { font-size: 16pt; font-weight: 900; letter-spacing: -1px; color: #000; display: inline-block; vertical-align: middle; margin-left: 6px; }

        .company-details { margin-top: 10px; font-size: 8pt; color: #444; line-height: 1.85; }
        .company-details .reg { font-weight: 700; font-size: 8pt; color: #1a1a1a; }

        .doc-type-title { font-size: 22pt; font-weight: 900; color: #000; letter-spacing: 3px; margin-bottom: 10px; }
        .doc-meta-table { margin-left: auto; border-collapse: collapse; }
        .doc-meta-table td { padding: 1.5px 0; font-size: 8.5pt; }
        .doc-meta-table .lbl { color: #666; padding-right: 10px; }
        .doc-meta-table .val { font-weight: 600; color: #000; }

        /* ── Dividers ── */
        .divider { border: none; border-top: 1px solid #d0d0d0; margin: 14px 0; }
        .divider-bold { border: none; border-top: 2px solid #1a1a1a; margin: 0 0 6px 0; }

        /* ── Prepared for / Bill to ── */
        .prepared-section { border-left: 3px solid #999; padding-left: 12px; margin-bottom: 20px; }
        .prepared-lbl { font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 5px; }
        .prepared-name { font-size: 14pt; font-weight: 700; color: #000; margin-bottom: 7px; }
        .prepared-contacts { width: 100%; border-collapse: collapse; }
        .prepared-contacts td { font-size: 8.5pt; color: #333; padding-right: 40px; vertical-align: top; }

        /* ── Items table ── */
        .items-table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 9pt; }
        .items-table thead tr { background: #1a1a1a; }
        .items-table thead th {
            color: #fff;
            font-weight: 700;
            font-size: 8pt;
            letter-spacing: 0.5px;
            padding: 9px 12px;
            text-align: left;
        }
        .items-table thead th.right { text-align: right; }
        .items-table thead th.center { text-align: center; }
        .items-table tbody tr { border-bottom: 1px solid #e5e5e5; }
        .items-table tbody td { padding: 14px 12px; vertical-align: top; }
        .items-table tbody td.num { width: 38px; color: #888; font-weight: 600; font-size: 8.5pt; }
        .items-table tbody td.desc { font-weight: 600; }
        .items-table tbody td.desc .sub { font-size: 8pt; font-weight: 400; color: #666; margin-top: 3px; }
        .items-table tbody td.right { text-align: right; font-weight: 600; white-space: nowrap; }
        .items-table tbody td.center { text-align: center; }
        .items-table tbody tr:last-child { border-bottom: 2px solid #aaa; }

        /* ── Footer two-col ── */
        .doc-footer-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .col-remittance { width: 42%; vertical-align: top; padding-right: 18px; }
        .col-totals { width: 58%; vertical-align: top; }

        .remit-lbl { font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 7px; }
        .remit-box {
            border: 1px solid #ccc;
            padding: 9px 11px;
            font-size: 8pt;
            line-height: 1.9;
        }
        .remit-box strong { color: #000; }

        .totals-table { width: 100%; border-collapse: collapse; margin-left: auto; }
        .totals-table td { padding: 4px 10px; font-size: 9.5pt; }
        .totals-table td:last-child { text-align: right; white-space: nowrap; }
        .totals-table .lbl-cell { color: #444; }
        .totals-table .divider-row td { border-top: 1px solid #999; padding-top: 7px; }
        .totals-table .final-lbl { font-size: 11.5pt; font-weight: 700; color: #000; }
        .totals-table .final-val { font-size: 11.5pt; font-weight: 700; color: #000; }

        /* ── Terms ── */
        .terms-section { margin-top: 24px; font-size: 8.5pt; color: #222; border-top: 1px solid #ddd; padding-top: 14px; }
        .terms-heading { font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 5px; font-size: 8pt; }
        .terms-body { line-height: 1.65; }

        /* ── Page number ── */
        .page-num { text-align: right; font-size: 7.5pt; color: #aaa; margin-top: 18px; }

        /* ── Status badge ── */
        .status-badge { font-size: 7.5pt; font-weight: 700; padding: 2px 7px; border-radius: 3px; }
        .s-draft     { background: #f3f4f6; color: #374151; }
        .s-sent      { background: #fef9c3; color: #854d0e; }
        .s-accepted  { background: #dcfce7; color: #166534; }
        .s-rejected  { background: #fee2e2; color: #991b1b; }
        .s-pending   { background: #dbeafe; color: #1e40af; }
        .s-paid      { background: #dcfce7; color: #166534; }
        .s-overdue   { background: #fee2e2; color: #991b1b; }
        .s-approved  { background: #dcfce7; color: #166534; }
        .s-completed { background: #f3f4f6; color: #374151; }

        @media print {
            @page { size: A4; margin: 15mm 20mm; }
            body { padding: 0; }
        }
        ';
    }

    // ── Logo helper ──────────────────────────────────────────────────────────
    private static function logoTag() {
        $path = __DIR__ . '/../img/KConsultingLogo.png';
        if (file_exists($path)) {
            $b64 = base64_encode(file_get_contents($path));
            return '<img src="data:image/png;base64,' . $b64 . '" alt="KConsulting" style="height:52px;width:auto;display:block;">';
        }
        return '<span style="font-size:20pt;font-weight:900;color:#000;">K<span style="font-weight:300;">Consulting</span></span>';
    }

    // ── HTML skeleton ────────────────────────────────────────────────────────
    private static function wrap($title, $body) {
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($title) . '</title>
<style>' . self::baseCSS() . '</style>
</head>
<body>
' . $body . '
<div class="page-num">Page 1 of 1</div>
</body>
</html>';
    }

    // ── Shared header block ──────────────────────────────────────────────────
    private static function docHeader($docType, $metaRows) {
        $logo = self::logoTag();
        $metas = '';
        foreach ($metaRows as $label => $value) {
            $metas .= '<tr><td class="lbl">' . htmlspecialchars($label) . ':</td><td class="val">' . $value . '</td></tr>';
        }
        return '
<table class="doc-header"><tr>
  <td class="col-logo">
    <div class="logo-wrap">' . $logo . '</div>
    <div class="company-details">
      ' . self::COMPANY_PHONE . '<br>
      ' . self::COMPANY_EMAIL . '<br>
      ' . self::COMPANY_WEBSITE . '<br>
      ' . self::COMPANY_ADDRESS . '<br>
      <span class="reg">REG NO: ' . self::COMPANY_REG . '</span>
    </div>
  </td>
  <td class="col-title">
    <div class="doc-type-title">' . $docType . '</div>
    <table class="doc-meta-table"><tbody>' . $metas . '</tbody></table>
  </td>
</tr></table>
<hr class="divider">';
    }

    // ── Prepared-for / Bill-to block ─────────────────────────────────────────
    private static function preparedFor($label, $name, $contacts) {
        $contact_cells = '';
        foreach ($contacts as $k => $v) {
            if ($v) $contact_cells .= '<td><strong>' . htmlspecialchars($k) . ':</strong> ' . htmlspecialchars($v) . '</td>';
        }
        return '
<div class="prepared-section">
  <div class="prepared-lbl">' . $label . '</div>
  <div class="prepared-name">' . htmlspecialchars($name) . '</div>
  <table class="prepared-contacts"><tr>' . $contact_cells . '</tr></table>
</div>
<hr class="divider">';
    }

    // ── Totals block ─────────────────────────────────────────────────────────
    private static function totalsBlock($subtotal, $vat_amount, $total_amount, $vat_pct, $paid_amount = null) {
        $rows = '
<tr><td class="lbl-cell">Sub-Total:</td><td>R ' . number_format($subtotal, 2) . '</td></tr>
<tr><td class="lbl-cell">VAT +' . number_format($vat_pct, 0) . '%:</td><td>R ' . number_format($vat_amount, 2) . '</td></tr>';
        if ($paid_amount !== null && $paid_amount > 0) {
            $rows .= '<tr><td class="lbl-cell">Amount Paid:</td><td>R ' . number_format($paid_amount, 2) . '</td></tr>';
        }
        $label = ($paid_amount !== null && $paid_amount > 0) ? 'Balance Due' : 'Final Total';
        $final = ($paid_amount !== null) ? $total_amount - $paid_amount : $total_amount;
        return '
<table class="totals-table">
  <tbody>
    ' . $rows . '
    <tr class="divider-row"><td class="final-lbl">' . $label . ':</td><td class="final-val">R ' . number_format($final, 2) . '</td></tr>
  </tbody>
</table>';
    }

    // ── Remittance block ─────────────────────────────────────────────────────
    private static function remittanceBlock() {
        return '
<div class="remit-lbl">Remittance Information</div>
<div class="remit-box">
  <strong>Bank Name:</strong> ' . self::BANK_NAME . '<br>
  <strong>Account Type:</strong> ' . self::BANK_ACCOUNT_TYPE . '<br>
  <strong>Account Number:</strong> ' . self::BANK_ACCOUNT_NO . '<br>
  <strong>Branch Code:</strong> ' . self::BANK_BRANCH_CODE . '<br>
  <strong>Company Registration:</strong> ' . self::COMPANY_REG . '
</div>';
    }

    // ── Terms block ──────────────────────────────────────────────────────────
    private static function termsBlock($extra_notes = '') {
        $out = '<div class="terms-section">';
        $out .= '<div class="terms-heading">' . self::TERMS_HEADING . '</div>';
        $out .= '<div class="terms-body">' . self::TERMS_BODY . '</div>';
        if ($extra_notes) {
            $out .= '<div class="terms-body" style="margin-top:8px;"><strong>Notes:</strong> ' . nl2br(htmlspecialchars($extra_notes)) . '</div>';
        }
        $out .= '</div>';
        return $out;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // QUOTATION
    // ══════════════════════════════════════════════════════════════════════════
    private static function buildQuotationHTML($q, $items) {
        $vat_pct     = round($q['vat_rate'] * 100);
        $valid_days  = (int)round((strtotime($q['valid_until']) - strtotime($q['quotation_date'])) / 86400);
        $validity    = $valid_days > 0 ? $valid_days . ' Business Days' : date('d.m.Y', strtotime($q['valid_until']));

        $header = self::docHeader('QUOTATION', [
            'Quote No'  => Security::escapeHTML($q['quotation_number']),
            'Date'      => date('d.m.Y', strtotime($q['quotation_date'])),
            'Validity'  => $validity,
        ]);

        $prepared = self::preparedFor(
            'PREPARED FOR',
            $q['client_name'] ?? 'N/A',
            [
                'Contact' => $q['client_phone']  ?? '',
                'Email'   => $q['client_email']  ?? '',
                'Company' => $q['client_company'] ?? '',
            ]
        );

        // Items table
        $rows = '';
        foreach ($items as $i => $item) {
            $rows .= '
<tr>
  <td class="num">' . ($i + 1) . '</td>
  <td class="desc">' . Security::escapeHTML($item['description']) . '</td>
  <td class="right">R ' . number_format($item['total_price'], 2) . '</td>
</tr>';
        }
        $table = '
<table class="items-table">
  <thead><tr>
    <th style="width:38px;">#</th>
    <th>SCOPE &amp; INFRASTRUCTURE SPECIFICATIONS</th>
    <th class="right" style="width:130px;">TOTAL (ZAR)</th>
  </tr></thead>
  <tbody>' . $rows . '</tbody>
</table>';

        // Footer: remittance left, totals right
        $footer = '
<table class="doc-footer-table"><tr>
  <td class="col-remittance">' . self::remittanceBlock() . '</td>
  <td class="col-totals">' . self::totalsBlock($q['subtotal'], $q['vat_amount'], $q['total_amount'], $vat_pct) . '</td>
</tr></table>';

        $body = $header . $prepared . $table . $footer . self::termsBlock($q['notes'] ?? '');
        return self::wrap('Quotation ' . $q['quotation_number'], $body);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // INVOICE
    // ══════════════════════════════════════════════════════════════════════════
    private static function buildInvoiceHTML($inv, $items) {
        $vat_pct = round($inv['vat_rate'] * 100);
        $isOverdue = (strtotime($inv['due_date']) < time() && $inv['status'] !== 'paid');

        $header = self::docHeader('INVOICE', [
            'Invoice No' => Security::escapeHTML($inv['invoice_number']),
            'Date'       => date('d.m.Y', strtotime($inv['invoice_date'])),
            'Due Date'   => date('d.m.Y', strtotime($inv['due_date'])) . ($isOverdue ? ' <span style="color:#b91c1c;font-weight:700;">(OVERDUE)</span>' : ''),
            'Status'     => '<span class="status-badge s-' . $inv['status'] . '">' . ucfirst($inv['status']) . '</span>',
        ]);

        $prepared = self::preparedFor(
            'BILL TO',
            $inv['client_name'] ?? 'N/A',
            [
                'Contact' => $inv['client_phone']  ?? '',
                'Email'   => $inv['client_email']  ?? '',
                'Company' => $inv['client_company'] ?? '',
            ]
        );

        $rows = '';
        foreach ($items as $i => $item) {
            $rows .= '
<tr>
  <td class="num">' . ($i + 1) . '</td>
  <td class="desc">' . Security::escapeHTML($item['description']) . '</td>
  <td class="center">' . number_format($item['quantity'], 0) . '</td>
  <td class="right">R ' . number_format($item['unit_price'], 2) . '</td>
  <td class="right">R ' . number_format($item['total_price'], 2) . '</td>
</tr>';
        }
        $table = '
<table class="items-table">
  <thead><tr>
    <th style="width:38px;">#</th>
    <th>DESCRIPTION</th>
    <th class="center" style="width:55px;">QTY</th>
    <th class="right" style="width:110px;">UNIT PRICE</th>
    <th class="right" style="width:120px;">AMOUNT (ZAR)</th>
  </tr></thead>
  <tbody>' . $rows . '</tbody>
</table>';

        $footer = '
<table class="doc-footer-table"><tr>
  <td class="col-remittance">' . self::remittanceBlock() . '</td>
  <td class="col-totals">' . self::totalsBlock($inv['subtotal'], $inv['vat_amount'], $inv['total_amount'], $vat_pct, $inv['paid_amount']) . '</td>
</tr></table>';

        $body = $header . $prepared . $table . $footer . self::termsBlock($inv['notes'] ?? '');
        return self::wrap('Invoice ' . $inv['invoice_number'], $body);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PURCHASE ORDER
    // ══════════════════════════════════════════════════════════════════════════
    private static function buildPurchaseOrderHTML($po, $items) {
        $vat_pct = round($po['vat_rate'] * 100);

        $header = self::docHeader('PURCHASE ORDER', [
            'PO Number'  => Security::escapeHTML($po['po_number']),
            'Order Date' => date('d.m.Y', strtotime($po['order_date'])),
            'Delivery'   => date('d.m.Y', strtotime($po['expected_delivery'])),
            'Status'     => '<span class="status-badge s-' . $po['status'] . '">' . ucfirst($po['status']) . '</span>',
        ]);

        // Supplier section
        $prepared = self::preparedFor(
            'SUPPLIER',
            $po['supplier_name'] ?? 'N/A',
            [
                'Phone'   => $po['supplier_phone'] ?? '',
                'Email'   => $po['supplier_email'] ?? '',
                'Project' => $po['project_name']   ?? '',
            ]
        );

        $rows = '';
        foreach ($items as $i => $item) {
            $rows .= '
<tr>
  <td class="num">' . ($i + 1) . '</td>
  <td class="desc">' . Security::escapeHTML($item['description']) . '</td>
  <td class="center">' . number_format($item['quantity'], 0) . '</td>
  <td class="right">R ' . number_format($item['unit_price'], 2) . '</td>
  <td class="right">R ' . number_format($item['total_price'], 2) . '</td>
</tr>';
        }
        $table = '
<table class="items-table">
  <thead><tr>
    <th style="width:38px;">#</th>
    <th>ITEM DESCRIPTION</th>
    <th class="center" style="width:55px;">QTY</th>
    <th class="right" style="width:110px;">UNIT PRICE</th>
    <th class="right" style="width:120px;">TOTAL (ZAR)</th>
  </tr></thead>
  <tbody>' . $rows . '</tbody>
</table>';

        // No remittance for POs — totals only
        $footer = '
<div style="text-align:right;margin-top:18px;">
  ' . self::totalsBlock($po['subtotal'], $po['vat_amount'], $po['total_amount'], $vat_pct) . '
</div>';

        $body = $header . $prepared . $table . $footer;
        if (!empty($po['notes'])) {
            $body .= '<div class="terms-section"><div class="terms-heading">Notes</div><div class="terms-body">' . nl2br(Security::escapeHTML($po['notes'])) . '</div></div>';
        }
        return self::wrap('Purchase Order ' . $po['po_number'], $body);
    }

    // ── Output: wkhtmltopdf → fallback HTML print view ───────────────────────
    private static function output($html, $filename) {
        if (self::wkhtmlAvailable()) {
            self::viaWkhtmltopdf($html, $filename);
        } else {
            self::viaPrintView($html, $filename);
        }
    }

    private static function wkhtmlAvailable() {
        $paths = [
            'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            '/usr/local/bin/wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
        ];
        foreach ($paths as $p) { if (file_exists($p)) return $p; }
        $which = shell_exec('which wkhtmltopdf 2>/dev/null') ?: shell_exec('where wkhtmltopdf 2>NUL');
        return $which ? trim($which) : false;
    }

    private static function viaWkhtmltopdf($html, $filename) {
        $exe   = self::wkhtmlAvailable();
        $tmp_h = tempnam(sys_get_temp_dir(), 'kc_html_') . '.html';
        $tmp_p = tempnam(sys_get_temp_dir(), 'kc_pdf_') . '.pdf';
        file_put_contents($tmp_h, $html);

        $cmd = '"' . $exe . '" --page-size A4 --margin-top 15mm --margin-right 20mm --margin-bottom 15mm --margin-left 20mm --enable-local-file-access --quiet "' . $tmp_h . '" "' . $tmp_p . '"';
        exec($cmd, $out, $rc);

        if ($rc === 0 && file_exists($tmp_p) && filesize($tmp_p) > 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            readfile($tmp_p);
            unlink($tmp_h); unlink($tmp_p);
        } else {
            @unlink($tmp_h); @unlink($tmp_p);
            self::viaPrintView($html, $filename);
        }
    }

    private static function viaPrintView($html, $filename) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($filename) . '</title>
<style>
body { margin: 0; padding: 0; background: #e5e7eb; font-family: Arial, sans-serif; }
.controls {
    position: fixed; top: 12px; right: 12px; z-index: 9999;
    display: flex; gap: 6px; flex-direction: column;
    background: #111; padding: 10px; border-radius: 8px;
}
.controls button {
    padding: 8px 14px; border: none; border-radius: 5px;
    font-size: 12px; font-weight: 600; cursor: pointer;
}
.btn-print { background: #059669; color: #fff; }
.btn-close { background: #6b7280; color: #fff; }
.doc-wrap {
    background: #fff;
    max-width: 210mm;
    min-height: 297mm;
    margin: 24px auto;
    padding: 15mm 20mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
}
@media print {
    .controls { display: none !important; }
    body { background: #fff; }
    .doc-wrap { margin: 0; padding: 0; box-shadow: none; max-width: none; }
    @page { size: A4; margin: 15mm 20mm; }
}
</style>
</head>
<body>
<div class="controls">
    <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>
<div class="doc-wrap">' . $html . '</div>
</body>
</html>';
    }
}
?>
