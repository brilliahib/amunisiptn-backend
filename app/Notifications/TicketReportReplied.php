<?php

namespace App\Notifications;

use App\Models\TicketReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketReportReplied extends Notification
{
    use Queueable;

    public TicketReport $ticketReport;

    public function __construct(TicketReport $ticketReport)
    {
        $this->ticketReport = $ticketReport;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'TICKET_REPLIED',
            'title' => "Laporan tiket #{$this->ticketReport->id} mendapatkan balasan",
            'description' => "Admin telah membalas laporan tiket Anda.",
            'ticket_report_id' => $this->ticketReport->id,
        ];
    }
}
