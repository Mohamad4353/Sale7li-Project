<?php

namespace Tests\Feature\Security;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function bookingPayload(User $customer): array
    {
        $category = ServiceCategory::factory()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        return [
            'service_category_id' => $category->id,
            'problem_description' => 'Leak under the sink needs attention.',
            'address_id' => $address->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
        ];
    }

    private function realImage(string $name = 'photo.png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), $name, 'image/png', null, true);
    }

    public function test_valid_image_is_stored_on_the_private_disk_with_a_generated_name(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            ...$this->bookingPayload($customer),
            'photos' => [$this->realImage('../../evil.php.png')],
        ])->assertRedirect();

        $paths = ServiceRequest::where('customer_id', $customer->id)->firstOrFail()->problem_images;
        $this->assertCount(1, $paths);
        $this->assertMatchesRegularExpression('#^requests/[A-Za-z0-9]+\.png$#', $paths[0]);
        $this->assertStringNotContainsString('evil', $paths[0]);
        $this->assertStringNotContainsString('..', $paths[0]);
        Storage::disk('local')->assertExists($paths[0]);
    }

    public function test_svg_upload_is_rejected(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();

        $this->from(route('customer.booking.create'))
            ->actingAs($customer)
            ->post(route('customer.booking.store'), [
                ...$this->bookingPayload($customer),
                'photos' => [UploadedFile::fake()->create('vector.svg', 4, 'image/svg+xml')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_executable_upload_is_rejected(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();

        $this->from(route('customer.booking.create'))
            ->actingAs($customer)
            ->post(route('customer.booking.store'), [
                ...$this->bookingPayload($customer),
                'photos' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-httpd-php')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_oversized_upload_is_rejected(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();

        $this->from(route('customer.booking.create'))
            ->actingAs($customer)
            ->post(route('customer.booking.store'), [
                ...$this->bookingPayload($customer),
                'photos' => [UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_non_customer_cannot_upload_through_the_booking_endpoint(): void
    {
        Storage::fake('local');
        $worker = User::factory()->worker()->create();

        $this->actingAs($worker)
            ->post(route('customer.booking.store'), [
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'problem_description' => 'x',
                'preferred_date' => now()->addDay()->toDateString(),
                'preferred_time' => '10:00',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_download_their_own_request_attachment(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        Storage::disk('local')->put('requests/photo.jpg', 'binary-image-bytes');
        $request = ServiceRequest::factory()->for($customer, 'customer')->create([
            'problem_images' => ['requests/photo.jpg'],
        ]);

        $this->actingAs($customer)
            ->get(route('attachments.request', [$request, 'problem', 0]))
            ->assertOk();
    }
}
