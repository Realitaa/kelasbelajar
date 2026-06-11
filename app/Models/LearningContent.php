<?php

namespace App\Models;

use Database\Factories\LearningContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'title',
    'content',
    'created_by',
])]
class LearningContent extends Model
{
    /** @use HasFactory<LearningContentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<ModuleObject, $this>
     */
    public function moduleObjects(): MorphMany
    {
        return $this->morphMany(ModuleObject::class, 'object');
    }
}
