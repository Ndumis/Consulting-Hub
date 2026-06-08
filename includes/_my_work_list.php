<?php
// Renders $my_projects as a list — items vary by department
$dept_c = $dept_color ?? '#6b7280';
?>
<div style="display:flex;flex-direction:column;gap:0;margin-top:0.5rem;">
<?php foreach ($my_projects as $item):
    // Determine the title and meta fields based on department context
    $title  = $item['name']          // IT projects
           ?? $item['campaign_name'] // Marketing campaigns
           ?? $item['company_name']  // BD leads
           ?? $item['quotation_number'] // Finance quotes
           ?? $item['invoice_number']   // Finance invoices
           ?? $item['leave_type']       // HR leave
           ?? $item['name']             // Clients
           ?? '—';
    $meta   = $item['my_role']        // IT project role
           ?? $item['campaign_type']   // Marketing
           ?? $item['contact_person']  // BD
           ?? ($item['client_name'] ?? null) // Finance
           ?? null;
    $status = $item['status'] ?? null;
    $date   = $item['end_date'] ?? $item['next_follow_up'] ?? $item['valid_until'] ?? $item['due_date'] ?? $item['end_date'] ?? null;
    $progress = isset($item['progress']) ? (int)$item['progress'] : null;

    // Status color
    $sc = match($status) {
        'active', 'published', 'paid', 'completed', 'accepted', 'approved' => '#22c55e',
        'in_progress', 'sent', 'running' => '#3b82f6',
        'pending', 'draft'               => '#f59e0b',
        'rejected', 'overdue', 'cancelled', 'blocked' => '#ef4444',
        default => '#9ca3af',
    };
?>
<div style="display:flex;align-items:center;gap:0.75rem;padding:0.65rem 0;border-bottom:1px solid #f3f4f6;">
    <?php if ($progress !== null): ?>
    <div style="position:relative;width:36px;height:36px;flex-shrink:0;">
        <svg width="36" height="36" style="transform:rotate(-90deg);">
            <circle cx="18" cy="18" r="14" fill="none" stroke="#f3f4f6" stroke-width="3"/>
            <circle cx="18" cy="18" r="14" fill="none" stroke="<?= $dept_c ?>" stroke-width="3"
                stroke-dasharray="<?= round(87.96 * $progress / 100, 1) ?> 87.96"/>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:0.62rem;font-weight:700;color:#374151;"><?= $progress ?>%</div>
    </div>
    <?php else: ?>
    <div style="width:8px;height:8px;border-radius:50%;background:<?= $sc ?>;flex-shrink:0;"></div>
    <?php endif; ?>

    <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:0.85rem;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= Security::escapeHTML($title) ?>
        </div>
        <?php if ($meta): ?>
        <div style="font-size:0.72rem;color:#6b7280;"><?= Security::escapeHTML($meta) ?></div>
        <?php endif; ?>
    </div>

    <div style="text-align:right;flex-shrink:0;">
        <?php if ($status): ?>
        <span style="font-size:0.7rem;padding:2px 7px;border-radius:10px;background:<?= $sc ?>18;color:<?= $sc ?>;white-space:nowrap;">
            <?= ucfirst(str_replace('_', ' ', $status)) ?>
        </span>
        <?php endif; ?>
        <?php if ($date): ?>
        <div style="font-size:0.7rem;color:#9ca3af;margin-top:2px;"><?= date('M j', strtotime($date)) ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
