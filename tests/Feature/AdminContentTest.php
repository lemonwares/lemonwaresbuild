<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);
    }

    public function test_editor_lists_text_and_lists(): void
    {
        $this->get(route('admin.content.index', ['group' => 'faq']))
            ->assertOk()
            ->assertSee('Frequently Asked Questions')
            ->assertSee('Edit list');

        $this->get(route('admin.content.index', ['group' => 'faq', 'q' => 'still_title']))
            ->assertOk()
            ->assertSee('still_title')
            ->assertDontSee('home_lede');
    }

    public function test_edited_text_shows_on_the_website_and_can_be_restored(): void
    {
        $this->put(route('admin.content.text.save'), [
            'locale' => 'en',
            'group' => 'faq',
            'key' => 'title',
            'value' => 'Your Questions, Answered',
        ])->assertSessionHasNoErrors();

        $this->get(route('faq'))->assertOk()->assertSee('Your Questions, Answered');
        $this->get(route('admin.content.index', ['group' => 'faq', 'edited' => 1]))->assertSee('Your Questions, Answered');

        $this->delete(route('admin.content.text.reset'), ['locale' => 'en', 'group' => 'faq', 'key' => 'title']);
        $this->get(route('faq'))->assertOk()->assertSee('Frequently Asked Questions');
    }

    public function test_page_save_only_stores_changed_boxes_and_checks_placeholders(): void
    {
        $this->put(route('admin.content.texts.save'), [
            'locale' => 'en',
            'group' => 'faq',
            'values' => [
                'title' => 'All Your Answers',
                'lede' => __('faq.lede'),
                'still_title' => '',
            ],
        ])->assertSessionHasErrors('value');

        $this->assertSame('All Your Answers', __('faq.title'));
        $this->assertSame(1, \App\Models\ContentOverride::count(), 'unchanged and rejected boxes are not stored');

        $this->put(route('admin.content.texts.save'), [
            'locale' => 'en', 'group' => 'legal', 'values' => ['last_updated' => 'Updated recently'],
        ])->assertSessionHasErrors('value');

        $this->get(route('admin.content.index', ['group' => 'faq']))->assertOk()->assertSee('Search result title')->assertSee('Save changes');
    }

    public function test_edits_are_per_language(): void
    {
        $this->put(route('admin.content.text.save'), [
            'locale' => 'fr', 'group' => 'faq', 'key' => 'title', 'value' => 'Vos questions',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Vos questions', __('faq.title', [], 'fr'));
        $this->assertSame('Frequently Asked Questions', __('faq.title', [], 'en'));
    }

    public function test_placeholders_must_be_kept(): void
    {
        $this->put(route('admin.content.text.save'), [
            'locale' => 'en', 'group' => 'legal', 'key' => 'last_updated', 'value' => 'Last updated recently',
        ])->assertSessionHasErrors('value');

        $this->put(route('admin.content.text.save'), [
            'locale' => 'en', 'group' => 'legal', 'key' => 'last_updated', 'value' => 'Updated on :date',
        ])->assertSessionHasNoErrors();
    }

    public function test_unknown_keys_and_groups_are_rejected(): void
    {
        $this->put(route('admin.content.text.save'), [
            'locale' => 'en', 'group' => 'faq', 'key' => 'does.not.exist', 'value' => 'x',
        ])->assertNotFound();

        $this->put(route('admin.content.text.save'), [
            'locale' => 'en', 'group' => 'validation', 'key' => 'required', 'value' => 'x',
        ])->assertSessionHasErrors('group');
    }

    public function test_faq_items_can_be_added_removed_and_reordered(): void
    {
        $original = __('faq.items');
        $this->get(route('admin.content.list', ['locale' => 'en', 'group' => 'faq', 'key' => 'items']))
            ->assertOk()
            ->assertSee($original[0]['question']);

        $rows = [];
        foreach ($original as $i => $item) {
            $rows[$i] = ['_source' => $i, '_order' => ($i + 1) * 10, 'question' => $item['question'], 'answer' => $item['answer'], 'href' => $item['href'] ?? '', 'cta' => $item['cta'] ?? ''];
        }
        $rows[0]['_remove'] = '1';
        $rows[1]['_order'] = 999;
        $rows[] = ['_order' => 1, 'question' => 'Do you accept bank transfer?', 'answer' => 'Yes, admins can confirm it by hand.'];

        $this->put(route('admin.content.list.save'), ['locale' => 'en', 'group' => 'faq', 'key' => 'items', 'rows' => $rows])
            ->assertSessionHasNoErrors();

        $items = __('faq.items');
        $this->assertCount(count($original), $items);
        $this->assertSame('Do you accept bank transfer?', $items[0]['question']);
        $this->assertSame($original[1]['question'], end($items)['question']);
        $this->assertNotContains($original[0]['question'], array_column($items, 'question'));
        $this->assertSame($original[1]['href'] ?? null, end($items)['href'] ?? null, 'link fields survive a save');

        $this->get(route('faq'))->assertOk()->assertSee('Do you accept bank transfer?');

        $this->put(route('admin.content.list.save'), ['locale' => 'en', 'group' => 'faq', 'key' => 'items', 'reset' => '1']);
        $this->assertSame($original, __('faq.items'));
    }

    public function test_legal_sections_can_be_edited(): void
    {
        $sections = __('legal.terms.sections');
        $rows = [];
        foreach ($sections as $i => $section) {
            $rows[$i] = ['_source' => $i, '_order' => $i, 'heading' => $section['heading'], 'body' => $section['body']];
        }
        $rows[0]['body'] = 'Using our services means you accept these terms. Questions: :email';

        $this->put(route('admin.content.list.save'), ['locale' => 'en', 'group' => 'legal', 'key' => 'terms.sections', 'rows' => $rows])
            ->assertSessionHasNoErrors();

        $this->get(route('terms'))->assertOk()->assertSee('Using our services means you accept these terms.');
    }

    public function test_content_needs_permission(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'admin_permissions' => ['blog']]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $staff->id]);

        $this->get(route('admin.content.index'))->assertForbidden();
    }
}
