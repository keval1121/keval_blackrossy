<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The contact form is gone, so policy pages point shoppers to email and WhatsApp instead.
     */
    public function up(): void
    {
        $pairs = [
            'Messages and attachments you send via the Contact form or WhatsApp' => 'Messages and attachments you send by email or WhatsApp',
            'Message us from the Contact page or WhatsApp with:' => 'Email or WhatsApp us (details on the Contact page) with:',
        ];

        foreach (DB::table('pages')->get(['id', 'content']) as $page) {
            $updated = strtr((string) $page->content, $pairs);

            if ($updated !== (string) $page->content) {
                DB::table('pages')->where('id', $page->id)->update(['content' => $updated, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
