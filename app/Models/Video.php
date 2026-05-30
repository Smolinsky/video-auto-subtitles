<?php

namespace App\Models;

use App\Enums\VideoProcessingStatus;
use App\Models\Scopes\VideoScope;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'userId',
    'status',
    'sourceDisk',
    'sourcePath',
    'audioDisk',
    'audioPath',
    'transcriptProvider',
    'transcriptModel',
    'transcriptLanguage',
    'transcriptText',
    'transcriptSegments',
    'transcriptDurationSeconds',
    'srtDisk',
    'srtPath',
    'originalName',
    'mimeType',
    'sizeBytes',
    'failureMessage',
    'audioExtractedAt',
    'transcriptionStartedAt',
    'transcribedAt',
    'srtGeneratedAt',
    'processingCompletedAt',
])]
/**
 * @property string|null $srtDisk
 * @property string|null $srtPath
 */
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory, VideoScope;

    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    protected function casts(): array
    {
        return [
            'status' => VideoProcessingStatus::class,
            'transcriptSegments' => 'array',
            'transcriptDurationSeconds' => 'float',
            'audioExtractedAt' => 'datetime',
            'transcriptionStartedAt' => 'datetime',
            'transcribedAt' => 'datetime',
            'srtGeneratedAt' => 'datetime',
            'processingCompletedAt' => 'datetime',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }
}
