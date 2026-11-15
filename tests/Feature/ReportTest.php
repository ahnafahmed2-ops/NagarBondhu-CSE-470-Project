<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Feature F1 – Multi-Category Civic Issue Reporting. Run: php artisan test */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
        Storage::fake('public');
    }

    /** A real 1x1 PNG, so the tests do not need PHP's GD extension. */
    private function png(string $name = 'photo.png'): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::where('name', 'Pothole')->value('id'),
            'title' => 'Deep pothole near market',
            'description' => 'A very deep pothole that damages rickshaws daily.',
            'address' => 'Mirpur Road',
            'ward' => 'Ward 7',
        ], $overrides);
    }

    public function test_home_page_loads_for_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Pothole');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $this->post('/register', [
            'name' => 'Ahnaf', 'email' => 'a@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect(route('reports.index'));

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_bad_input(): void
    {
        $this->post('/register', [
            'name' => 'A', 'email' => 'bad', 'password' => '1', 'password_confirmation' => '2',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_works_and_wrong_password_fails(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('reports.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_create_report_with_photo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reports.store'), $this->validData([
            'evidence' => $this->png('pothole.png'),
        ]));

        $report = Report::firstOrFail();
        $response->assertRedirect(route('reports.show', $report));
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame('Submitted', $report->fresh()->status);
        Storage::disk('public')->assertExists($report->evidence_path);
    }

    public function test_invalid_report_is_rejected_with_errors(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), ['title' => 'ab', 'description' => 'short', 'address' => ''])
            ->assertSessionHasErrors(['category_id', 'title', 'description', 'address']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), $this->validData([
                'evidence' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
            ]))->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_list_shows_only_own_reports_and_filters_by_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $pothole = Category::where('name', 'Pothole')->value('id');
        $drain = Category::where('name', 'Blocked Drain')->value('id');

        $user->reports()->create($this->validData(['title' => 'My pothole report']));
        $user->reports()->create($this->validData(['title' => 'My drain report', 'category_id' => $drain]));
        $other->reports()->create($this->validData(['title' => 'Someone elses report']));

        $this->actingAs($user)->get(route('reports.index'))
            ->assertSee('My pothole report')->assertSee('My drain report')
            ->assertDontSee('Someone elses report');

        $this->actingAs($user)->get(route('reports.index', ['category' => $drain]))
            ->assertSee('My drain report')->assertDontSee('My pothole report');
    }

    public function test_owner_can_edit_report(): void
    {
        $user = User::factory()->create();
        $report = $user->reports()->create($this->validData());

        $this->actingAs($user)->get(route('reports.edit', $report))->assertOk()->assertSee('Deep pothole near market');

        $this->actingAs($user)->put(route('reports.update', $report), $this->validData([
            'title' => 'Broken road near market',
            'category_id' => Category::where('name', 'Damaged Road')->value('id'),
        ]))->assertRedirect(route('reports.show', $report));

        $this->assertSame('Broken road near market', $report->fresh()->title);
    }

    public function test_owner_can_delete_report_and_photo_is_removed(): void
    {
        $user = User::factory()->create();
        $path = 'evidence/p.png';
        Storage::disk('public')->put($path, 'x');
        $report = $user->reports()->create($this->validData(['evidence_path' => $path]));

        $this->actingAs($user)->delete(route('reports.destroy', $report))->assertRedirect(route('reports.index'));

        $this->assertDatabaseCount('reports', 0);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_other_users_cannot_view_edit_or_delete_a_report(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $report = $owner->reports()->create($this->validData());

        $this->actingAs($intruder)->get(route('reports.show', $report))->assertNotFound();
        $this->actingAs($intruder)->get(route('reports.edit', $report))->assertNotFound();
        $this->actingAs($intruder)->put(route('reports.update', $report), $this->validData())->assertNotFound();
        $this->actingAs($intruder)->delete(route('reports.destroy', $report))->assertNotFound();
        $this->assertDatabaseCount('reports', 1);
    }

    public function test_report_being_handled_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $report = $user->reports()->create($this->validData());
        $report->forceFill(['status' => 'Under Review'])->save();

        $this->actingAs($user)->put(route('reports.update', $report), $this->validData(['title' => 'Changed title']))
            ->assertRedirect(route('reports.show', $report));
        $this->actingAs($user)->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.show', $report));

        $this->assertSame($this->validData()['title'], $report->fresh()->title);
        $this->assertDatabaseCount('reports', 1);
    }

    public function test_html_in_title_is_escaped(): void
    {
        $user = User::factory()->create();
        $report = $user->reports()->create($this->validData(['title' => '<script>alert(1)</script> drain']));

        $this->actingAs($user)->get(route('reports.show', $report))
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
