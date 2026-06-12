<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'tmp/'.$this->faker->uuid().'.jpg',
            'filename' => $this->faker->uuid().'.jpg',
            'original_name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(100, 5000),
            'status' => 'temporary',
            'fileable_type' => null,
            'fileable_id' => null,
            'uploaded_by' => User::factory(),
        ];
    }
}
