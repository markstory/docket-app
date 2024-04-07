<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\DateHelper;
use Cake\I18n\Date;
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
        $past = new Date('-93 days');
        $recent = new Date('-14 days');
        $thisweek = new Date('5 days');
        $faraway = new Date('30 days');

        return [
            // Input, output
            [new Date('today'), 'Today'],
            [new Date('tomorrow'), 'Tomorrow'],
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
        $tomorrow = new Date('tomorrow');
        $result = $this->Date->formatCompact($input);
        $this->assertSame($output, $result);
    }
}
