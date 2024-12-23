<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Table\FeedCategoriesTable;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FeedCategoriesController Test Case
 *
 * @uses \App\Controller\FeedCategoriesController
 */
class FeedCategoriesControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    private FeedCategoriesTable $FeedCategories;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.FeedCategories',
        'app.FeedSubscriptions',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->FeedCategories = $this->fetchTable('FeedCategories');
    }

    public function testIndex(): void
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $news = $this->makeFeedCategory('News', 1, ['ranking' => 1]);
        $nope = $this->makeFeedCategory('Not Yours', 2, ['ranking' => 1]);

        $this->login();
        $this->enableCsrfToken();
        $this->get('/feeds/categories/');

        $this->assertResponseOk();
        $this->assertResponseContains($blogs->title);
        $this->assertResponseContains($news->title);
        $this->assertResponseNotContains($nope->title);
    }

    public function testView(): void
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $feed = $this->makeFeed('https://example.com/feed.rss');
        $sub = $this->makeFeedSubscription($blogs->id, $feed->id);
        $item = $this->makeFeedItem($sub->id);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/feeds/categories/{$blogs->id}/view");

        $this->assertResponseOk();
        $this->assertResponseContains($blogs->title);
        $this->assertResponseContains($item->title);
    }

    public function testViewPermissiong(): void
    {
        $blogs = $this->makeFeedCategory('Blogs', 2, ['ranking' => 0]);
        $feed = $this->makeFeed('https://example.com/feed.rss');
        $this->makeFeedSubscription($blogs->id, $feed->id, 2);

        $this->login(1);
        $this->enableCsrfToken();
        $this->get("/feeds/categories/{$blogs->id}/view");

        $this->assertResponseCode(403);
    }

    /**
     * Test add forces user id to the current user.
     *
     * @return void
     */
    public function testAddForceUserId(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/categories/add', [
            'color' => 1,
            'title' => 'Blogs',
            'user_id' => 2,
        ]);
        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $this->assertFlashMessage('Feed category saved.');

        $category = $this->FeedCategories->findByUserId(1)->firstOrFail();
        $this->assertNotEmpty($category);
    }

    /**
     * Test add forces user id to the current user.
     *
     * @return void
     */
    public function testAddSuccess(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/categories/add', [
            'color' => 1,
            'title' => 'Blogs',
        ]);
        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $category = $this->FeedCategories->findByUserId(1)->firstOrFail();
        $this->assertNotEmpty($category);
        $this->assertEquals('Blogs', $category->title);
        $this->assertEquals(1, $category->color);
    }

    /**
     * Test add forces user id to the current user.
     *
     * @return void
     */
    public function testAddValidationError(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/feeds/categories/add', [
            'color' => 1,
            'title' => '',
        ]);
        $this->assertResponseOk();
        $this->assertFlashMessage('Feed category could not be saved. Please try again.');

        $category = $this->FeedCategories->findByUserId(1)->first();
        $this->assertEmpty($category);
    }

    /**
     * Test edit success
     */
    public function testEditSuccess(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->post("/feeds/categories/{$category->id}/edit", [
            'color' => 1,
            'title' => 'Updated',
        ]);
        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $this->assertFlashMessage('Feed category saved.');

        $category = $this->FeedCategories->get($category->id);
        $this->assertNotEmpty($category);
        $this->assertEquals('Updated', $category->title);
    }

    /**
     * Test edit validation errors
     */
    public function testEditValidationError(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/feeds/categories/{$category->id}/edit", [
            'color' => 1,
            'title' => '',
        ]);
        $this->assertResponseOk();
        $this->assertFlashMessage('Feed category could not be saved. Please try again.');

        $category = $this->FeedCategories->get($category->id);
        $this->assertNotEmpty($category);
        $this->assertEquals('Blogs', $category->title);
    }

    /**
     * Test edit validation errors
     */
    public function testEditCannotChangeUser(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/feeds/categories/{$category->id}/edit", [
            'color' => 1,
            'title' => 'Blogs',
            'user_id' => 2,
        ]);
        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $this->assertFlashMessage('Feed category saved.');

        $category = $this->FeedCategories->get($category->id);
        $this->assertNotEmpty($category);
        $this->assertEquals('Blogs', $category->title);
        $this->assertEquals(1, $category->user_id);
    }

    /**
     * Test edit validation errors
     */
    public function testEditPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/feeds/categories/{$category->id}/edit", [
            'color' => 1,
            'title' => 'Permission fail',
        ]);
        $this->assertResponseCode(403);

        $category = $this->FeedCategories->get($category->id);
        $this->assertNotEmpty($category);
        $this->assertEquals('Blogs', $category->title);
        $this->assertEquals(2, $category->user_id);
    }

    /**
     * Test delete method
     */
    public function testDeleteSuccess(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/feeds/categories/{$category->id}/delete");

        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $this->assertFlashMessage('Feed category has been deleted.');
        $this->assertFalse($this->FeedCategories->exists(['id' => $category->id]));
    }

    /**
     * Test delete method permissions
     */
    public function testDeletePermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/feeds/categories/{$category->id}/delete");

        $this->assertResponseCode(403);
        $this->assertTrue($this->FeedCategories->exists(['id' => $category->id]));
    }

    /**
     * Test delete_confirm method
     */
    public function testDeleteConfirmSuccess(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->get("/feeds/categories/{$category->id}/delete/confirm");

        $this->assertResponseOk();
        $this->assertTrue($this->FeedCategories->exists(['id' => $category->id]));
    }

    /**
     * Test delete_confirm method permissions
     */
    public function testDeleteConfirmPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);

        $this->login();
        $this->get("/feeds/categories/{$category->id}/delete/confirm");

        $this->assertResponseCode(403);
        $this->assertTrue($this->FeedCategories->exists(['id' => $category->id]));
    }

    public function testReorderOk()
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $music = $this->makeFeedCategory('Music', 1, ['ranking' => 3]);
        $comics = $this->makeFeedCategory('Comics', 1, ['ranking' => 6]);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/categories/reorder', [
            'id' => [$blogs->id, $music->id, $comics->id],
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Blogs');
        $this->assertResponseContains('Music');
        $this->assertResponseContains('Comics');

        $results = $this->FeedCategories->find()->orderByAsc('ranking')->toArray();
        $expected = [$blogs->id, $music->id, $comics->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderPermissions()
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $music = $this->makeFeedCategory('Music', 1, ['ranking' => 3]);
        $nope = $this->makeFeedCategory('Blogs Other', 2, ['ranking' => 0]);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/categories/reorder', [
            'id' => [$nope->id, $music->id, $blogs->id],
        ]);
        $this->assertResponseCode(400);

        $results = $this->FeedCategories->find()
            ->orderByAsc('user_id')
            ->orderByAsc('ranking')
            ->toArray();
        $expected = [$blogs->id, $music->id, $nope->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderPartialUpdates()
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $music = $this->makeFeedCategory('Music', 1, ['ranking' => 3]);
        $comics = $this->makeFeedCategory('Comics', 1, ['ranking' => 6]);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/categories/reorder', [
            'id' => [$comics->id, $blogs->id],
        ]);
        $this->assertResponseOk();

        $results = $this->FeedCategories->find()
            ->orderByAsc('ranking')
            ->orderByAsc('title')
            ->toArray();
        $expected = [$comics->id, $blogs->id, $music->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testToggleExpanded()
    {
        $blogs = $this->makeFeedCategory('Blogs', 1, ['ranking' => 0]);
        $this->assertNull($blogs->expanded);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/feeds/categories/{$blogs->id}/toggle-expanded");
        $this->assertResponseOk();
        $reload = $this->FeedCategories->get($blogs->id);
        $this->assertTrue($reload->expanded);
    }

    /**
     * Test delete_confirm method permissions
     */
    public function testToggleExpandedPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);

        $this->login();
        $this->post("/feeds/categories/{$category->id}/toggle-expanded");

        $this->assertResponseCode(403);

        $reload = $this->FeedCategories->get($category->id);
        $this->assertFalse($reload->expanded);
    }
}
