<?php

namespace Tests\Feature;

use App\Models\AccountEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_account_entry_with_multiple_image_uploads(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'title' => 'Supplier Transfer',
            'description' => 'Advance payment for packaging materials.',
            'amount' => '275.50',
            'paid_at' => '2026-05-05',
            'payer_name' => 'Ahmed Ali',
            'images' => [
                UploadedFile::fake()->image('receipt-1.jpg'),
                UploadedFile::fake()->image('receipt-2.jpg'),
            ],
            'attachment' => UploadedFile::fake()->create('transfer-proof.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseHas('account_entries', [
            'title' => 'Supplier Transfer',
            'payer_name' => 'Ahmed Ali',
        ]);

        $entry = AccountEntry::query()->firstOrFail();
        $imagePaths = $entry->imagePaths();

        $this->assertSame('275.50', $entry->amount);
        $this->assertSame('2026-05-05', substr((string) $entry->getRawOriginal('paid_at'), 0, 10));
        $this->assertCount(2, $imagePaths);
        $this->assertTrue(Storage::disk('public')->exists($imagePaths[0]));
        $this->assertTrue(Storage::disk('public')->exists($imagePaths[1]));
        $this->assertTrue(Storage::disk('public')->exists((string) $entry->attachment_path));
        $this->assertSame('transfer-proof.pdf', $entry->attachment_name);
    }
}