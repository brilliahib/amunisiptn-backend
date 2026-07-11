<?php

namespace App\Notifications;

use App\Models\TicketReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketReportStatusUpdated extends Notification
{
    use Queueable;

    public TicketReport $ticketReport;
    public string $newStatus;

    public function __construct(TicketReport $ticketReport, string $newStatus)
    {
        $this->ticketReport = $ticketReport;
        $this->newStatus = $newStatus;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'TICKET_STATUS_UPDATED',
            'title' => "Status laporan tiket #{$this->ticketReport->id} diubah",
            'description' => "Status laporan tiket Anda telah diubah menjadi {$this->newStatus}.",
            'ticket_report_id' => $this->ticketReport->id,
        ];
    }
}
