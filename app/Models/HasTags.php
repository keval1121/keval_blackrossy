<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function syncTagsFromString(?string $tags): void
    {
        $names = collect(explode(',', (string) $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        $ids = $names->map(function (string $name) {
            return Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        });

        $this->tags()->sync($ids->all());
    }
}
