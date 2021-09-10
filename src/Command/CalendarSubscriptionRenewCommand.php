<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\CalendarService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CalendarSubscriptionRenew command.
 */
class CalendarSubscriptionRenewCommand extends Command
{
    /**
     * @var \App\Model\Table\CalendarSubscriptionsTable
     */
    protected $CalendarSubscriptions;

    /**
     * @var \App\Service\CalendarService
     */
    protected $calendarService;

    public function __construct(CalendarService $service)
    {
        $this->calendarService = $service;
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Renew expired or soon to expire calendar subscriptions');

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->loadModel('CalendarSubscriptions');

        $results = $this->CalendarSubscriptions->find('expiring')
            ->contain('CalendarSources.CalendarProviders')
            ->all();

        $io->verbose('Starting calendar subscription renewal');
        foreach ($results as $row) {
            /** @var \App\Model\Entity\CalendarSubscription $row */
            if (!isset($row->calendar_source)) {
                $io->error("Could not find related calendar source for id={$row->id}");
                continue;
            }
            $io->out("Renewing subscription for source id={$row->calendar_source->id}");
            $provider = $row->calendar_source->calendar_provider;
            $this->calendarService->setAccessToken($provider);
            $this->calendarService->createSubscription($row->calendar_source);
            $io->out('New subscription created.');
        }
        $io->verbose("All done. Updated {count($results)} records.");
    }
}
