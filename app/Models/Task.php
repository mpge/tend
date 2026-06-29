<?php

namespace App\Models;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $notes
 * @property TaskBucket $bucket
 * @property TaskStatus $status
 * @property int $priority
 * @property CarbonImmutable|null $due_date
 * @property int $position
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'notes',
        'bucket',
        'status',
        'priority',
        'due_date',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bucket' => TaskBucket::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'priority' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
