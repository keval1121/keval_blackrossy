<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('shop:enrich-product-copy {--dry-run : Preview counts without writing}')]
#[Description('Apply unique product-wise short and long descriptions from database/data/product_copy.php')]
class EnrichProductCopy extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = database_path('data/product_copy.php');
        if (! is_file($path)) {
            $this->error('Missing '.$path);

            return self::FAILURE;
        }

        /** @var array<string, array{short: string, description: string}> $copy */
        $copy = require $path;

        $updated = 0;
        $missing = [];

        Product::query()->orderBy('id')->each(function (Product $product) use ($copy, &$updated, &$missing): void {
            $row = $copy[$product->slug] ?? null;
            if ($row === null) {
                $missing[] = $product->slug;

                return;
            }

            $short = trim($row['short']);
            $description = trim($row['description']);

            if ($this->option('dry-run')) {
                $this->line($product->slug.' → '.str_word_count($description).' words');
                $updated++;

                return;
            }

            $product->update([
                'short_description' => $short,
                'description' => $description,
                'seo_title' => $product->name.' | Buy Online | Black Rossy',
                'seo_description' => Str::limit($short, 155),
            ]);
            $updated++;
        });

        $this->info(($this->option('dry-run') ? 'Would update' : 'Updated')." {$updated} products.");

        if ($missing !== []) {
            $this->warn('No copy for: '.implode(', ', $missing));
        }

        $extra = collect(array_keys($copy))->diff(Product::query()->pluck('slug'));
        if ($extra->isNotEmpty()) {
            $this->warn('Copy unused (no product): '.$extra->implode(', '));
        }

        return self::SUCCESS;
    }
}
