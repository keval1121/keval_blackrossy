<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Indexed policy pages should not mention the retired journal.
     */
    public function up(): void
    {
        $pairs = [
            'Product pages, category guides and our Journal are written' => 'Product pages and category guides are written',
            'Our Journal or pages may link' => 'Our pages may link',
        ];

        foreach (DB::table('pages')->get(['id', 'content']) as $row) {
            $updated = strtr((string) $row->content, $pairs);

            if ($updated !== (string) $row->content) {
                DB::table('pages')->where('id', $row->id)->update([
                    'content' => $updated,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
