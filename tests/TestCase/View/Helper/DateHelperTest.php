<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\DateHelper;
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
}
