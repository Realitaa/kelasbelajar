<?php

namespace App\Models;

use Database\Factories\ModuleObjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'module_id',
    'object_id',
    'object_type',
    'position',
])]
class ModuleObject extends Model
{
    /** @use HasFactory<ModuleObjectFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ClassroomModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(ClassroomModule::class, 'module_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function object(): MorphTo
    {
        return $this->morphTo();
    }
}
