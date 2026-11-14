<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingConsentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'email',
        'action',
        'source',
        'ip_address',
        'user_agent',
        'notes',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(MarketingContact::class, 'contact_id');
    }
}
