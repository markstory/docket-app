<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddProjectSections extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $this->table('project_sections')
            ->addColumn('project_id', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'default' => null,
                'null' => false,
            ])
            ->addColumn('ranking', 'integer', [
                'default' => '0',
                'null' => false,
            ])
            ->addColumn('archived', 'boolean', [
                'default' => false,
                'null' => false,
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
            ->addIndex(['project_id'])
            ->create();

        $this->table('project_sections')
            ->addForeignKey(
                'project_id',
                'projects',
                'id',
                [
                    'update' => 'RESTRICT',
                    'delete' => 'RESTRICT',
                ]
            )
            ->update();

        $this->table('tasks')
             ->addColumn('project_section_id', 'integer', [
                 'null' => true,
                 'default' => null,
                 'after' => 'project_id',
             ])
             ->update();

        $this->table('tasks')
            ->addForeignKey(
                'project_section_id',
                'project_sections',
                'id',
                [
                    'update' => 'RESTRICT',
                    'delete' => 'RESTRICT',
                ]
            )
            ->update();
    }
}
