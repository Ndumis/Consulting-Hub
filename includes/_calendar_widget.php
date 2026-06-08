<div class="calendar-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
    <span style="font-weight:600;">📅 Upcoming Events</span>
    <small style="color:#9ca3af;">Next 7 days</small>
</div>
<div class="calendar-events">
<?php if (empty($upcoming_events)): ?>
    <div style="text-align:center;color:#9ca3af;padding:1.5rem 0;">
        <div style="font-size:1.5rem;margin-bottom:0.5rem;">📭</div>
        <p>Calendar is clear</p>
    </div>
<?php else: ?>
    <?php
    $dept_event_colors = [
        'IT' => '#0ea5e9', 'Marketing' => '#f97316', 'Business Development' => '#8b5cf6',
        'Finance' => '#22c55e', 'HR' => '#ec4899', 'Clients' => '#f59e0b', 'calendar' => '#6b7280'
    ];
    foreach ($upcoming_events as $event):
        $ec = $dept_event_colors[$event['department']] ?? '#6b7280';
    ?>
    <div class="calendar-event" style="border-left:3px solid <?= $ec ?>;">
        <div class="event-time" style="min-width:52px;text-align:center;margin-right:0.75rem;flex-shrink:0;">
            <div style="font-size:0.8rem;font-weight:600;color:#374151;"><?= date('M j', strtotime($event['event_date'])) ?></div>
            <div style="font-size:0.7rem;color:#9ca3af;">
                <?= ($event['event_time'] && $event['event_time'] !== '00:00:00') ? date('H:i', strtotime($event['event_time'])) : 'All Day' ?>
            </div>
        </div>
        <div class="event-details" style="flex:1;min-width:0;">
            <div class="event-title" style="font-size:0.82rem;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= $event['icon'] ?> <?= Security::escapeHTML($event['title']) ?>
            </div>
            <div style="font-size:0.72rem;color:#9ca3af;margin-top:1px;">
                <?= Security::escapeHTML($event['event_type']) ?> &bull; <?= Security::escapeHTML($event['department']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
