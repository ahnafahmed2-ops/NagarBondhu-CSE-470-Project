<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature F7 – Automatic priority and severity assessment. Run: php artisan test */
class PriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    private function reportBy(User $user, string $category, string $description): Report
    {
        return $user->reports()->create([
            'category_id' => Category::where('name', $category)->value('id'),
            'title' => 'Problem on our road',
            'description' => $description,
            'address' => 'Dhanmondi 27',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    public function test_broken_streetlight_without_supporters_is_low(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Broken Streetlight', 'The light has been off for a week.');

        $this->assertSame(24, $report->priority_score); // severity 2 × 12
        $this->assertSame('Low', $report->priority_level);
    }

    public function test_open_manhole_near_school_with_supporters_is_critical(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Open Manhole', 'Open manhole right next to the school gate.');
        $this->assertSame(70, $report->priority_score); // 5 × 12 + 10 hazard

        // three neighbours say it affects them too (4 affected citizens with the reporter)
        foreach (User::factory()->count(3)->create() as $neighbour) {
            $this->actingAs($neighbour)->post(route('reports.votes.store', $report), ['type' => 'affected']);
        }

        $report->refresh();
        $this->assertSame(82, $report->priority_score); // 60 + round(6 × log2(4)) = 12 + 10
        $this->assertSame('Critical', $report->priority_level);
    }

    public function test_still_there_confirmations_add_two_points_each(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Pothole', 'Pothole on the main road.');
        $this->assertSame(36, $report->priority_score);

        foreach (User::factory()->count(2)->create() as $witness) {
            $this->actingAs($witness)->post(route('reports.votes.store', $report), ['type' => 'still_there']);
        }

        $this->assertSame(40, $report->fresh()->priority_score);
        $this->assertSame('Medium', $report->fresh()->priority_level);
    }

    public function test_bangla_hazard_word_is_recognised(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Pothole', 'রাস্তার গর্তে প্রতিদিন দুর্ঘটনা হয়');

        $this->assertSame(46, $report->priority_score); // 36 + 10
    }

    public function test_admin_override_is_logged_and_survives_new_votes(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Pothole', 'Pothole on the main road.');

        $this->actingAs($this->admin())->post(route('admin.reports.priority', $report), [
            'priority_level' => 'Critical', 'reason' => 'Ambulance route to the hospital',
        ])->assertRedirect();

        $this->actingAs(User::factory()->create())->post(route('reports.votes.store', $report), ['type' => 'affected']);

        $report->refresh();
        $this->assertSame('Critical', $report->priority_level);
        $this->assertTrue($report->priority_overridden);
        $this->assertGreaterThan(36, $report->priority_score); // the score itself still updates
        $this->assertDatabaseHas('status_histories', [
            'report_id' => $report->id, 'note' => 'Priority changed from Medium to Critical: Ambulance route to the hospital',
        ]);
    }

    public function test_override_needs_a_reason_and_an_admin(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Pothole', 'Pothole on the main road.');

        $this->actingAs($this->admin())->post(route('admin.reports.priority', $report), ['priority_level' => 'High'])
            ->assertSessionHasErrors('reason');
        $this->actingAs(User::factory()->create())->post(route('admin.reports.priority', $report), [
            'priority_level' => 'High', 'reason' => 'I want it fixed first',
        ])->assertForbidden();

        $this->assertSame('Medium', $report->fresh()->priority_level);
    }

    public function test_override_can_be_returned_to_automatic(): void
    {
        $report = $this->reportBy(User::factory()->create(), 'Pothole', 'Pothole on the main road.');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.reports.priority', $report), ['priority_level' => 'Low', 'reason' => 'Minor issue']);

        $this->actingAs($admin)->delete(route('admin.reports.priority.reset', $report))->assertRedirect();

        $this->assertFalse($report->fresh()->priority_overridden);
        $this->assertSame('Medium', $report->fresh()->priority_level);
    }
}
