<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_stores_a_query_for_admin(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'mobile' => '9876543210',
            'subject' => 'Order help',
            'message' => 'Please share the status of my COD order.',
            'is_read' => true,
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status', 'Message sent. We will get back to you soon.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'mobile' => '9876543210',
            'subject' => 'Order help',
            'message' => 'Please share the status of my COD order.',
            'is_read' => false,
        ]);
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_contact_form_requires_name_and_message(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), []);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_guest_cannot_open_admin_queries(): void
    {
        $this->get(route('admin.messages.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_read_and_delete_a_query(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-queries@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $query = ContactMessage::query()->create([
            'name' => "O'Reilly <script>alert('xss')</script>",
            'email' => 'shopper@example.com',
            'message' => 'Need help with a delayed parcel.',
        ]);

        $index = $this->actingAs($admin, 'admin')->get(route('admin.messages.index'));
        $index->assertOk();
        $index->assertSee('Need help with a delayed parcel.');
        $index->assertSee('&lt;script&gt;', false);
        $index->assertDontSee("<script>alert('xss')</script>", false);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.messages.index'))
            ->post(route('admin.messages.read', $query))
            ->assertRedirect(route('admin.messages.index'));
        $this->assertTrue($query->fresh()->is_read);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.messages.index'))
            ->delete(route('admin.messages.delete', $query))
            ->assertRedirect(route('admin.messages.index'));
        $this->assertModelMissing($query);
    }
}
