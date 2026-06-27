<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TicketReport extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'images',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReportReply::class)->oldest();
    }
}
