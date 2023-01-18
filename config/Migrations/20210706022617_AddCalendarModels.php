<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add the tables used for calendar syncing.
 */
class AddCalendarModels extends AbstractMigration
{
    public function change()
    {
        // Table for Oauth tokens to read-only calendar data.
        // Starting with google, but more oauth based providers
        // could be added later.
        $this->table('calendar_providers')
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('identifier', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('access_token', 'text', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('refresh_token', 'text', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('token_expiry', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addForeignKey(['user_id'], 'users', ['id'], [
                'delete' => 'CASCADE',
            ])
            ->create();

        // A calendar in the provider.
        $this->table('calendar_sources')
            ->addColumn('name', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('calendar_provider_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('provider_id', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('color', 'integer', [
                'default' => 1,
                'null' => false,
            ])
            ->addColumn('last_sync', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('sync_token', 'string', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(['calendar_provider_id', 'provider_id'], ['unique' => true])
            ->addForeignKey(['calendar_provider_id'], 'calendar_providers', ['id'], [
                'delete' => 'CASCADE',
            ])
            ->create();

        // Push notification subscriptions for a calendar.
        // This is a 1:N relation as we could have multiple subscriptions
        // active for the same source. Google's docs mention that channels
        // can expire and we'll want to make a new channel before the old
        // expires.
        $this->table('calendar_subscriptions')
             ->addColumn('calendar_source_id', 'integer', [
                'default' => null,
                'null' => false,
             ])
             ->addColumn('identifier', 'string', [
                 'default' => null,
                 'null' => false,
             ])
             ->addColumn('verifier', 'string', [
                 'default' => null,
                 'null' => false,
             ])
            ->addIndex(['identifier'], ['unique' => true])
            ->addForeignKey(['calendar_source_id'], 'calendar_sources', ['id'], [
                'delete' => 'CASCADE',
            ])
            ->create();

        // Individual calendar events from a source.
        $this->table('calendar_items')
            ->addColumn('calendar_source_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('provider_id', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'null' => false,
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
            ])
            ->addColumn('start_time', 'datetime', [
                'null' => true,
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
            ])
            ->addColumn('end_time', 'datetime', [
                'null' => true,
            ])
            ->addColumn('all_day', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('html_link', 'string', [
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(['calendar_source_id', 'provider_id'], ['unique' => true])
            ->addForeignKey(['calendar_source_id'], 'calendar_sources', ['id'], [
                'delete' => 'CASCADE',
            ])
            ->create();
    }
}
