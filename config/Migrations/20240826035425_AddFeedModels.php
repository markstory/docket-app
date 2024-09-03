<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddFeedModels extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        // RSS feeds - shared by all users
        $table = $this->table('feeds');
        $table->addColumn('url', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('refresh_interval', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('last_refresh', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addTimestamps();
        $table->create();

        // Items in each feed - shared by all users
        $table = $this->table('feed_items');
        $table->addColumn('feed_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('summary', 'text')
            ->addColumn('published_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('thumbnail_image_url', 'string', ['null' => true])
            ->addTimestamps();

        $table->addForeignKey(['feed_id'], 'feeds');
        $table->create();

        // Categories for how a user organizes feeds
        $table = $this->table('feed_categories');
        $table->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'null' => false,
            ])
            ->addColumn('color', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('ranking', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addTimestamps();
        $table->addForeignKey(['user_id'], 'users');

        // Subscriptions a user has to their feeds.
        $table = $this->table('feed_subscriptions');
        $table->addColumn('feed_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('feed_category_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('alias', 'string', [
                'null' => false,
            ])
            ->addColumn('ranking', 'int', [
                'null' => false,
            ])
            ->addTimestamps();
        $table->addForeignKey(['feed_id'], 'feeds');
        $table->addForeignKey(['feed_category_id'], 'feed_categories');
        $table->addForeignKey(['user_id'], 'users');
        $table->create();

        // Read/saved state for each item in a feed per subscription.
        $table = $this->table('feed_subscriptions_feed_items');
        $table->addColumn('feed_subscription_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('feed_item_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('is_read', 'boolean', [
                'null' => false,
                'default' => false,
            ])
            ->addColumn('is_saved', 'boolean', [
                'null' => false,
                'default' => false,
            ])
            ->addColumn('is_hidden', 'boolean', [
                'null' => false,
                'default' => false,
            ])
            ->addTimestamps();
        $table->addForeignKey(['feed_subscription_id'], 'feed_subscriptions');
        $table->addForeignKey(['feed_item_id'], 'feed_items');
        $table->create();

        // Saved items that a user wants retained forever.
        $table = $this->table('saved_feed_items');
        $table->addColumn('feed_subscription_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('title', 'string')
            ->addColumn('body', 'text')
            ->addTimestamps();
        $table->addForeignKey(['feed_subscription_id'], 'feed_subscriptions');
        $table->create();
    }
}
