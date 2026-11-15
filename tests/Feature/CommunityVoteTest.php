<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature F5 – Community issue verification and voting. Run: php artisan test */
class CommunityVoteTest extends TestCase
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
            'category_id' => Category::where('name', 'Pothole')->value('id'),
            'title' => 'Pothole near the bus stop',
            'description' => 'A large pothole that fills with water.',
            'address' => 'Mirpur Road',
            'ward' => 'Ward 7',
        ], $overrides));
    }

    public function test_community_page_lists_other_citizens_open_reports_only(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->reportBy($me, ['title' => 'My own pothole']);
        $this->reportBy($other, ['title' => 'Neighbour pothole']);
        $this->reportBy($other, ['title' => 'Old fixed pothole'])->forceFill(['status' => 'Resolved'])->save();

        $this->actingAs($me)->get(route('community.index'))
            ->assertOk()->assertSee('Neighbour pothole')
            ->assertDontSee('My own pothole')->assertDontSee('Old fixed pothole');
    }

    public function test_community_page_filters_by_ward_and_hides_reporter_name(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['name' => 'Secret Reporter']);
        $this->reportBy($other, ['title' => 'Ward seven problem', 'ward' => 'Ward 7']);
        $this->reportBy($other, ['title' => 'Ward nine problem', 'ward' => 'Ward 9', 'address' => 'Uttara']);

        $this->actingAs($me)->get(route('community.index', ['ward' => 'Ward 7']))
            ->assertSee('Ward seven problem')->assertDontSee('Ward nine problem');

        $report = Report::where('title', 'Ward seven problem')->first();
        $this->actingAs($me)->get(route('community.show', $report))
            ->assertOk()->assertDontSee('Secret Reporter');
    }

    public function test_citizen_can_vote_once_per_type(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $voter = User::factory()->create();

        $this->actingAs($voter)->post(route('reports.votes.store', $report), ['type' => 'affected'])->assertRedirect();
        $this->actingAs($voter)->post(route('reports.votes.store', $report), ['type' => 'affected'])->assertRedirect();

        $this->assertSame(1, $report->voteCount('affected'));
    }

    public function test_cannot_vote_on_own_report(): void
    {
        $owner = User::factory()->create();
        $report = $this->reportBy($owner);

        $this->actingAs($owner)->post(route('reports.votes.store', $report), ['type' => 'affected'])->assertForbidden();
        $this->assertSame(0, $report->voteCount('affected'));
    }

    public function test_still_there_and_fixed_replace_each_other(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $voter = User::factory()->create();

        $this->actingAs($voter)->post(route('reports.votes.store', $report), ['type' => 'still_there']);
        $this->actingAs($voter)->post(route('reports.votes.store', $report), ['type' => 'fixed']);

        $this->assertSame(0, $report->voteCount('still_there'));
        $this->assertSame(1, $report->voteCount('fixed'));
    }

    public function test_closed_reports_refuse_votes(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $report->forceFill(['status' => 'Resolved'])->save();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.votes.store', $report), ['type' => 'affected'])
            ->assertSessionHas('error');

        $this->assertSame(0, $report->voteCount('affected'));
    }

    public function test_invalid_vote_type_is_rejected(): void
    {
        $report = $this->reportBy(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('reports.votes.store', $report), ['type' => 'love'])
            ->assertSessionHasErrors('type');
    }

    public function test_vote_can_be_taken_back(): void
    {
        $report = $this->reportBy(User::factory()->create());
        $voter = User::factory()->create();
        $this->actingAs($voter)->post(route('reports.votes.store', $report), ['type' => 'affected']);

        $this->actingAs($voter)->delete(route('reports.votes.destroy', [$report, 'affected']))->assertRedirect();

        $this->assertSame(0, $report->voteCount('affected'));
    }

    public function test_guest_cannot_open_community_page(): void
    {
        $this->get(route('community.index'))->assertRedirect(route('login'));
    }
}
