<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'type' => fake()->numberBetween(1, 3),
            'link_url' => fake()->url(),
            'image_url' => 'uploads/posts/'.fake()->uuid().'.jpg',
            'body' => fake()->paragraphs(3, true),
            'author' => fake()->name(),
            'observation' => fake()->sentence(),
            'event_id' => null,
            'slug' => Str::slug($title).'-'.fake()->numberBetween(100, 999),
            'is_active' => fake()->boolean(85) ? 1 : 0,
            'references' => fake()->sentence(),
            'date_publication' => fake()->dateTimeBetween('-2 years', 'now'),
            'fichier_url' => null,
            'minister_id' => null,
        ];
    }
}
