<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    private function realImage(string $name = 'me.png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), $name, 'image/png', null, true);
    }

    private function profilePayload(): array
    {
        return ['name' => 'Worker One', 'experience_years' => 5, 'service_radius_km' => 20];
    }

    public function test_a_valid_photo_is_stored_privately_with_a_generated_name(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->put(route('worker.profile.update'), [
            ...$this->profilePayload(),
            'photo' => $this->realImage('../../evil.php.png'),
        ])->assertRedirect();

        $path = $worker->workerProfile->fresh()->photo;
        $this->assertMatchesRegularExpression('#^worker-photos/[A-Za-z0-9]+\.png$#', $path);
        $this->assertStringNotContainsString('evil', $path);
        $this->assertStringNotContainsString('..', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_replacing_a_photo_deletes_the_previous_file(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->put(route('worker.profile.update'), [...$this->profilePayload(), 'photo' => $this->realImage()])->assertRedirect();
        $first = $worker->workerProfile->fresh()->photo;

        $this->actingAs($worker)->put(route('worker.profile.update'), [...$this->profilePayload(), 'photo' => $this->realImage('second.png')])->assertRedirect();
        $second = $worker->workerProfile->fresh()->photo;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_svg_upload_is_rejected(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.profile.edit'))->put(route('worker.profile.update'), [
            ...$this->profilePayload(),
            'photo' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($worker->workerProfile->fresh()->photo);
    }

    public function test_executable_upload_is_rejected(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.profile.edit'))->put(route('worker.profile.update'), [
            ...$this->profilePayload(),
            'photo' => UploadedFile::fake()->create('shell.php', 8, 'application/x-httpd-php'),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($worker->workerProfile->fresh()->photo);
    }

    public function test_oversized_image_is_rejected(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.profile.edit'))->put(route('worker.profile.update'), [
            ...$this->profilePayload(),
            'photo' => UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg'),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($worker->workerProfile->fresh()->photo);
    }

    public function test_non_worker_cannot_use_the_worker_profile_endpoint(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->put(route('worker.profile.update'), [
            ...$this->profilePayload(),
            'photo' => $this->realImage(),
        ])->assertForbidden();
    }

    public function test_a_worker_cannot_fetch_another_hidden_workers_photo(): void
    {
        Storage::fake('local');
        $hidden = $this->workerUser(verification: 'pending');
        $this->actingAs($hidden)->put(route('worker.profile.update'), [...$this->profilePayload(), 'photo' => $this->realImage()])->assertRedirect();

        $intruder = $this->workerUser();

        $this->actingAs($intruder)->get(route('workers.photo', $hidden))->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->get(route('workers.photo', $hidden))->assertNotFound();
    }

    public function test_a_marketplace_visible_professionals_photo_is_served_publicly(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();
        $this->activeService($worker);
        $this->actingAs($worker)->put(route('worker.profile.update'), [...$this->profilePayload(), 'photo' => $this->realImage()])->assertRedirect();

        // Served to a genuine guest because the professional is marketplace-visible.
        $this->app['auth']->forgetGuards();

        $this->get(route('workers.photo', $worker))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(route('workers.photo', $worker), false);
    }

    public function test_the_owner_can_preview_their_photo_before_going_live(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser(verification: 'pending');
        $this->actingAs($worker)->put(route('worker.profile.update'), [...$this->profilePayload(), 'photo' => $this->realImage()])->assertRedirect();

        $this->actingAs($worker)->get(route('workers.photo', $worker))->assertOk();
    }
}
