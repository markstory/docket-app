<?php
declare(strict_types=1);
/**
 * @var \Calendar\Model\Entity\CalendarSource $calendarSource
 */
// configure layout
$this->set('closable', false);

$this->setLayout('modal');

echo $this->element('confirm_dialog', [
    'target' => [
        '_name' => 'calendarsources:delete',
        'id' => $calendarSource->id,
        'providerId' => $calendarSource->calendar_provider_id,
    ],
    'title' => 'Are you sure?',
    'description' => 'This will delete all events in this calendar.',
]);
