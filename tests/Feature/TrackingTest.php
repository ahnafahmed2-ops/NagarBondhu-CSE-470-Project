<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/** Feature F4 – Complaint tracking and status history. Run: php artisan test */
class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    private function reportBy(User $user, array $overrides = []): Report
    {
        return $user->reports()->create(array_merge([
            'category_id' => Category::where('name', 'Blocked Drain')->value('id'),
            'title' => 'Drain overflowing on Road 5',
            'description' => 'The drain overflows every time it rains.',
            'address' => 'Road 5, Dhanmondi',
        ], $overrides));
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'City Admin']);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    public function test_new_report_gets_tracking_code_and_first_history_entry(): void
    {
        $report = $this->reportBy(User::factory()->create());

        $this->assertMatchesRegularExpression('/^NB-\d{6}-[A-Z0-9]{5}$/', $report->tracking_code);
        $this->assertSame(1, $report->histories()->count());
        $this->assertSame('Submitted', $report->histories()->first()->to_status);
    }

    public function test_admin_moves_report_through_workflow_and_each_step_is_recorded(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $admin = $this->admin();

        foreach (['Under Review', 'Verified', 'Assigned', 'In Progress', 'Awaiting Verification', 'Resolved'] as $status) {
            $this->actingAs($admin)->post(route('admin.reports.status', $report), ['status' => $status, 'note' => "Now {$status}"])
                ->assertSessionHas('success');
        }

        $this->assertSame('Resolved', $report->fresh()->status);
        $this->assertSame(7, $report->histories()->count());
        $this->assertDatabaseHas('status_histories', [
            'report_id' => $report->id, 'from_status' => 'Verified', 'to_status' => 'Assigned', 'user_id' => $admin->id,
        ]);
    }

    public function test_illegal_transition_is_refused(): void
    {
        $report = $this->reportBy(User::factory()->create());

        $this->actingAs($this->admin())->post(route('admin.reports.status', $report), ['status' => 'Resolved'])
            ->assertSessionHas('error');

        $this->assertSame('Submitted', $report->fresh()->status);
        $this->assertSame(1, $report->histories()->count());

        $this->expectException(InvalidArgumentException::class);
        $report->changeStatus('Resolved', $this->admin());
    }

    public function test_end_states_allow_no_further_change(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $report->changeStatus('Rejected', $this->admin(), 'Not a municipal issue');

        $this->assertSame([], $report->allowedNextStatuses());
    }

    public function test_citizens_cannot_change_status_or_open_admin_pages(): void
    {
        $owner = User::factory()->create();
        $report = $this->reportBy($owner);

        $this->actingAs($owner)->post(route('admin.reports.status', $report), ['status' => 'Under Review'])->assertForbidden();
        $this->actingAs($owner)->get(route('admin.reports.index'))->assertForbidden();
        $this->assertSame('Submitted', $report->fresh()->status);
    }

    public function test_admin_page_lists_and_filters_reports(): void
    {
        $this->reportBy(User::factory()->create(), ['title' => 'First drain report']);
        $this->reportBy(User::factory()->create(), ['title' => 'Second drain report'])
            ->changeStatus('Under Review', $this->admin());

        $this->actingAs($this->admin())->get(route('admin.reports.index', ['status' => 'Under Review']))
            ->assertOk()->assertSee('Second drain report')->assertDontSee('First drain report');
    }

    public function test_admin_report_page_shows_reporter_next_statuses_and_timeline(): void
    {
        $owner = User::factory()->create(['name' => 'Rahim Uddin']);
        $report = $this->reportBy($owner);
        $report->changeStatus('Under Review', $this->admin(), 'Looking into it');

        $this->actingAs($this->admin())->get(route('admin.reports.show', $report))
            ->assertOk()->assertSee('Rahim Uddin')->assertSee('Verified')->assertSee('Looking into it');
    }

    public function test_public_tracking_is_case_insensitive_and_hides_reporter(): void
    {
        $owner = User::factory()->create(['name' => 'Private Person']);
        $report = $this->reportBy($owner);
        $report->changeStatus('Under Review', $this->admin(), 'Engineer will visit tomorrow');

        $this->get(route('track', ['code' => strtolower($report->tracking_code)]))
            ->assertOk()
            ->assertSee('Drain overflowing on Road 5')
            ->assertSee('Engineer will visit tomorrow')
            ->assertDontSee('Private Person')
            ->assertDontSee('City Admin')
            ->assertDontSee($owner->email);
    }

    public function test_unknown_tracking_code_shows_message(): void
    {
        $this->get(route('track', ['code' => 'NB-000000-XXXXX']))->assertOk()->assertSee('No report has the code');
    }

    public function test_owner_sees_timeline_on_report_page(): void
    {
        $owner = User::factory()->create();
        $report = $this->reportBy($owner);
        $report->changeStatus('Under Review', $this->admin(), 'Checking with the drainage team');

        $this->actingAs($owner)->get(route('reports.show', $report))
            ->assertSee($report->tracking_code)->assertSee('Checking with the drainage team');
    }
}
