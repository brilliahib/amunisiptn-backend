<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TicketReportReply extends Model
{
    use HasUlids;

    protected $fillable = [
        'ticket_report_id',
        'user_id',
        'message',
    ];

    public function ticket()
    {
        return $this->belongsTo(TicketReport::class, 'ticket_report_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
