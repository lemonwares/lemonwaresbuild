<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminStaffPermissionsAndCoversTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_super_admin_can_create_limited_staff_and_permissions_are_enforced(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.staff.store'), [
            'name' => 'Support Only',
            'email' => 'support-only@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'permissions' => ['support', 'subscribers'],
        ])->assertRedirect(route('admin.staff.index'));

        $limited = User::query()->where('email', 'support-only@example.com')->firstOrFail();
        $this->assertFalse($limited->is_super_admin);
        $this->assertSame(['support', 'subscribers'], $limited->admin_permissions);

        $this->post(route('admin.logout'));

        $this->post(route('admin.login.submit'), [
            'email' => 'support-only@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.support-tickets.index'));

        $this->get(route('admin.support-tickets.index'))->assertOk();
        $this->get(route('admin.customers.index'))->assertForbidden();
        $this->get(route('admin.staff.index'))->assertForbidden();
    }

    public function test_blog_and_project_cover_uploads_work(): void
    {
        Storage::fake('public');
        $this->loginAsAdmin();

        $cover = UploadedFile::fake()->create('cover.jpg', 200, 'image/jpeg');

        $this->post(route('admin.blog-posts.store'), [
            'title' => 'Covered Post',
            'excerpt' => 'With image',
            'body' => 'Body text here.',
            'is_published' => '1',
            'cover' => $cover,
        ])->assertRedirect(route('admin.blog-posts.index'));

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Covered Post',
        ]);

        $post = \App\Models\BlogPost::query()->where('title', 'Covered Post')->firstOrFail();
        $this->assertNotNull($post->cover_path);
        Storage::disk('public')->assertExists($post->cover_path);

        $projectCover = UploadedFile::fake()->create('project.png', 200, 'image/png');

        $this->post(route('admin.projects.store'), [
            'title' => 'Covered Project',
            'summary' => 'Shot',
            'description' => 'Desc',
            'is_published' => '1',
            'cover' => $projectCover,
        ])->assertRedirect(route('admin.projects.index'));

        $project = \App\Models\Project::query()->where('title', 'Covered Project')->firstOrFail();
        $this->assertNotNull($project->cover_path);
        Storage::disk('public')->assertExists($project->cover_path);
    }
}
