<?php

namespace App\Models;

use Database\Factories\ClassroomModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'classroom_id',
    'title',
    'position',
])]
class ClassroomModule extends Model
{
    /** @use HasFactory<ClassroomModuleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return HasMany<ModuleObject, $this>
     */
    public function objects(): HasMany
    {
        return $this->hasMany(ModuleObject::class, 'module_id')->orderBy('position');
    }
}
