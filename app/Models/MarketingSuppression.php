<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingSuppression extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'reason',
        'details',
        'source',
    ];

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }

    public static function suppress(string $email, string $reason, ?string $details = null, ?string $source = null): self
    {
        $normalized = strtolower(trim($email));

        // Also update contact status if exists
        MarketingContact::where('email', $normalized)->update([
            'subscription_status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return static::firstOrCreate(
            ['email' => $normalized],
            [
                'reason' => $reason,
                'details' => $details,
                'source' => $source ?? 'system',
            ]
        );
    }
}
