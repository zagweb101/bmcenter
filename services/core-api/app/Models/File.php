<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ملف/مستند (File). PRD §8.1, §21.
 */
class File extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'disk', 'path', 'original_name', 'mime_type',
        'size_bytes', 'checksum_sha256', 'visibility',
        'attachable_type', 'attachable_id', 'uploaded_by_user_id',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
