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
        $this->table('calendar_provider')
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
            ->addColumn('access_token', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('refresh_token', 'string', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('token_expiry', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addForeignKey(['user_id'], 'users')
            ->create();

        // A calendar in the provider.
        $this->table('calendar_source')
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
            ->addColumn('color', 'char', [
                'default' => null,
                'limit' => 6,
                'null' => false,
            ])
            ->addColumn('last_sync', 'datetime', [
                'default' => null,
                'null' => false,
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
            ->addForeignKey(['calendar_provider_id'], 'calendar_provider')
            ->create();

        // Individual calendar events from a source.
        $this->table('calendar_item')
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
            ->addColumn('start_time', 'datetime', [
                'null' => false,
            ])
            ->addColumn('end_time', 'datetime', [
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
            ->addForeignKey(['calendar_source_id'], 'calendar_source')
            ->create();
    }
}
