<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class FixActionOnTasksSectionId extends BaseMigration
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
        $this->table('tasks')
            ->dropForeignKey('section_id')
            ->addForeignKey('section_id', 'project_sections', 'id', [
                'update' => 'NO_ACTION',
                'delete' => 'SET_NULL',
            ])
            ->update();
    }
}
