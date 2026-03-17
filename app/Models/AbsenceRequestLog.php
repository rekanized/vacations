<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceRequestLog extends Model
{
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';

    protected $fillable = [
        'request_uuid',
        'user_id',
        'actor_id',
        'action',
        'absence_type',
        'status',
        'date_start',
        'date_end',
        'date_count',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date:Y-m-d',
            'date_end' => 'date:Y-m-d',
            'metadata' => 'array',
        ];
    }

    public static function actionOptions(): array
    {
        return [
            self::ACTION_SUBMITTED => 'Submitted',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_REJECTED => 'Rejected',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
