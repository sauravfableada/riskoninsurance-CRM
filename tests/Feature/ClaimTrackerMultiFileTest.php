<?php

namespace Tests\Feature;

use App\Models\ClaimTracker;
use App\Models\Customer;
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimTrackerMultiFileTest extends TestCase
{
    use DatabaseTransactions;

    private function createTestData(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $customer = Customer::create([
            'name' => 'John Proposer',
            'email' => 'john.' . uniqid() . '@example.com',
            'phone' => '9876543210',
        ]);
        $familyGroup = FamilyGroup::create([
            'group_name' => 'Proposer Family',
            'holder_id' => $customer->id,
            'user_id' => $user->id,
        ]);
        $insuredMember = FamilyGroupMember::create([
            'family_group_id' => $familyGroup->id,
            'member_name' => 'Jane Member',
            'relation' => 'Spouse',
        ]);
        $policy = Policy::create([
            'family_group_id' => $familyGroup->id,
            'holder_id' => $customer->id,
            'policy_type_id' => 1,
            'policy_number' => 'POL-TEST-' . uniqid(),
            'policy_number_no' => 'POL-TEST-' . uniqid(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        return compact('user', 'customer', 'familyGroup', 'insuredMember', 'policy');
    }

    public function test_can_upload_multiple_files_on_create_and_manage_on_edit(): void
    {
        Storage::fake('public');
        $data = $this->createTestData();
        $this->actingAs($data['user']);

        $file1 = UploadedFile::fake()->create('doc1.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->image('doc2.png');

        $response = $this->post(route('claim-tracker.store'), [
            'family_group_id' => $data['familyGroup']->id,
            'proposer_id' => $data['customer']->id,
            'policy_id' => $data['policy']->id,
            'insured_member_id' => $data['insuredMember']->id,
            'claim_amount' => 5000,
            'issued_by' => $data['user']->id,
            'claim_file_received_date' => now()->toDateString(),
            'claim_status' => 'Processed',
            'claim_files' => [$file1, $file2],
        ]);

        $response->assertRedirect(route('claim-tracker.index'));

        $claim = ClaimTracker::latest('id')->first();
        $this->assertNotNull($claim);
        $this->assertCount(2, $claim->claim_files);

        foreach ($claim->claim_files as $path) {
            Storage::disk('public')->assertExists($path);
        }

        // Test view page displays both files
        $showResponse = $this->get(route('claim-tracker.show', $claim));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('doc1.pdf');
        $showResponse->assertSee('doc2.png');

        // Test edit page displays both files
        $editResponse = $this->get(route('claim-tracker.edit', $claim));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('doc1.pdf');
        $editResponse->assertSee('doc2.png');

        // Test update: keep first file, remove second file, and upload a third file
        $file3 = UploadedFile::fake()->create('doc3.docx', 50);
        $firstPath = $claim->claim_files[0];
        $secondPath = $claim->claim_files[1];

        $updateResponse = $this->put(route('claim-tracker.update', $claim), [
            'family_group_id' => $data['familyGroup']->id,
            'proposer_id' => $data['customer']->id,
            'policy_id' => $data['policy']->id,
            'insured_member_id' => $data['insuredMember']->id,
            'claim_amount' => 6000,
            'issued_by' => $data['user']->id,
            'claim_file_received_date' => now()->toDateString(),
            'claim_status' => 'Query',
            'existing_claim_files' => [$firstPath],
            'claim_files' => [$file3],
        ]);

        $updateResponse->assertRedirect(route('claim-tracker.index'));

        $claim->refresh();
        $this->assertEquals(6000, $claim->claim_amount);
        $this->assertEquals('Query', $claim->claim_status);
        $this->assertCount(2, $claim->claim_files);
        $this->assertContains($firstPath, $claim->claim_files);
        Storage::disk('public')->assertExists($firstPath);
        Storage::disk('public')->assertMissing($secondPath);

        // Test destroy deletes stored files
        $this->delete(route('claim-tracker.destroy', $claim));
        $this->assertDatabaseMissing('claim_trackers', ['id' => $claim->id]);
        foreach ($claim->claim_files as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_backward_compatibility_with_legacy_single_file_format(): void
    {
        Storage::fake('public');
        $data = $this->createTestData();
        $this->actingAs($data['user']);

        // Legacy format: raw single file path string in claim_file
        $legacyClaim = ClaimTracker::create([
            'family_group_id' => $data['familyGroup']->id,
            'proposer_id' => $data['customer']->id,
            'policy_id' => $data['policy']->id,
            'insured_member_id' => $data['insuredMember']->id,
            'claim_amount' => 3000,
            'issued_by' => $data['user']->id,
            'claim_file_received_date' => now()->toDateString(),
            'claim_status' => 'Processed',
            'claim_file' => 'claims/legacy_doc.pdf',
        ]);

        $this->assertCount(1, $legacyClaim->claim_files);
        $this->assertEquals('claims/legacy_doc.pdf', $legacyClaim->claim_files[0]);

        $showResponse = $this->get(route('claim-tracker.show', $legacyClaim));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('legacy_doc.pdf');

        $editResponse = $this->get(route('claim-tracker.edit', $legacyClaim));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('legacy_doc.pdf');
    }

    public function test_can_toggle_policy_directly_into_claim_tracker_via_checkbox(): void
    {
        $data = $this->createTestData();
        $this->actingAs($data['user']);

        // 1. Tick policy checkbox to add to claim tracker
        $response = $this->postJson(route('claim-tracker.toggle-policy'), [
            'policy_id' => $data['policy']->id,
            'track' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'tracked' => true,
        ]);

        $this->assertDatabaseHas('claim_trackers', [
            'policy_id' => $data['policy']->id,
            'claim_status' => 'Processed',
        ]);

        // 2. Verify it shows in Claim Tracker index
        $indexResponse = $this->get(route('claim-tracker.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($data['policy']->policy_number_no);
        $indexResponse->assertSee('Select Policies');

        // 3. Untick policy checkbox to remove from claim tracker
        $untrackResponse = $this->postJson(route('claim-tracker.toggle-policy'), [
            'policy_id' => $data['policy']->id,
            'track' => false,
        ]);

        $untrackResponse->assertStatus(200);
        $untrackResponse->assertJson([
            'status' => 'success',
            'tracked' => false,
        ]);

        $this->assertDatabaseMissing('claim_trackers', [
            'policy_id' => $data['policy']->id,
        ]);
    }

    public function test_can_bulk_toggle_policies_for_claim_tracker(): void
    {
        $data = $this->createTestData();
        $this->actingAs($data['user']);

        $secondPolicy = Policy::create([
            'family_group_id' => $data['familyGroup']->id,
            'holder_id' => $data['customer']->id,
            'policy_type_id' => 1,
            'policy_number' => 'POL-BULK-' . uniqid(),
            'policy_number_no' => 'POL-BULK-' . uniqid(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        // Bulk track
        $bulkResponse = $this->postJson(route('claim-tracker.bulk-toggle-policies'), [
            'policy_ids' => [$data['policy']->id, $secondPolicy->id],
            'track' => true,
        ]);

        $bulkResponse->assertStatus(200);
        $this->assertDatabaseHas('claim_trackers', ['policy_id' => $data['policy']->id]);
        $this->assertDatabaseHas('claim_trackers', ['policy_id' => $secondPolicy->id]);

        // Bulk untrack
        $bulkUntrackResponse = $this->postJson(route('claim-tracker.bulk-toggle-policies'), [
            'policy_ids' => [$data['policy']->id, $secondPolicy->id],
            'track' => false,
        ]);

        $bulkUntrackResponse->assertStatus(200);
        $this->assertDatabaseMissing('claim_trackers', ['policy_id' => $data['policy']->id]);
        $this->assertDatabaseMissing('claim_trackers', ['policy_id' => $secondPolicy->id]);
    }
}
