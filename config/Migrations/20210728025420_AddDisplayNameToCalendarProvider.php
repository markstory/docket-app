<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDisplayNameToCalendarProvider extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('calendar_providers');
        $table->addColumn('display_name', 'string', [
            'null' => false,
            'default' => '',
            'after' => 'identifier',
        ]);
        $table->update();
    }
}
