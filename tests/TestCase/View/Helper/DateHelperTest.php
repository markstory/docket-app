<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\DateHelper;
use Cake\I18n\FrozenDate;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * App\View\Helper\DateHelper Test Case
 */
class DateHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\View\Helper\DateHelper
     */
    protected $Date;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Date = new DateHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Date);

        parent::tearDown();
    }

    public static function formatCompactProvider()
    {
        $past = new FrozenDate('-93 days');
        $recent = new FrozenDate('-14 days');
        $thisweek = new FrozenDate('5 days');
        $faraway = new FrozenDate('30 days');

        return [
            // Input, output
            [new FrozenDate('today'), 'Today'],
            [new FrozenDate('tomorrow'), 'Tomorrow'],
            [$past, $past->i18nFormat('MMM d yyyy')],
            [$recent, $recent->i18nFormat('MMM d')],
            [$thisweek, $thisweek->i18nFormat('EEEE')],
            [$faraway, $faraway->i18nFormat('MMM d')],
        ];
    }

    /**
     * @dataProvider formatCompactProvider
     */
    public function testFormatCompact($input, $output)
    {
        $tomorrow = new FrozenDate('tomorrow');
        $result = $this->Date->formatCompact($input);
        $this->assertSame($output, $result);
    }
}
