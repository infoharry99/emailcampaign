<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function contacts()
    {
        return $this->belongsToMany(
            MarketingContact::class,
            'marketing_contact_list_members',
            'contact_list_id',
            'contact_id'
        )->withTimestamps();
    }

    public function activeContactsCount(): int
    {
        return $this->contacts()
            ->where('subscription_status', 'subscribed')
            ->where('consent_status', 'granted')
            ->whereNotIn('email', function ($q) {
                $q->select('email')->from('marketing_suppressions');
            })
            ->count();
    }
}
