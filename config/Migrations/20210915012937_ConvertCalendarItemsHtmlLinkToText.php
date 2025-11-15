<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ConvertCalendarItemsHtmlLinkToText extends BaseMigration
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
        $table = $this->table('calendar_items');
        $table->changeColumn('html_link', 'text', [
            'null' => true,
        ]);
        $table->save();
    }
}
