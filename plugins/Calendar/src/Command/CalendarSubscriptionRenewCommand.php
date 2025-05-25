<?php
declare(strict_types=1);

namespace Calendar\Command;

use Calendar\Model\Table\CalendarSubscriptionsTable;
use Calendar\Service\CalendarService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ModelAwareTrait;
use RuntimeException;

/**
 * CalendarSubscriptionRenew command.
 */
class CalendarSubscriptionRenewCommand extends Command
{
    use ModelAwareTrait;

    /**
     * @var \Calendar\Model\Table\CalendarSubscriptionsTable
     */
    protected CalendarSubscriptionsTable $CalendarSubscriptions;

    /**
     * @var \App\Service\CalendarService
     */
    protected CalendarService $calendarService;

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
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->CalendarSubscriptions = $this->fetchTable('Calendar.CalendarSubscriptions');

        $expiring = $this->CalendarSubscriptions->find('expiring')
            ->select(['CalendarSubscriptions.calendar_source_id']);

        $results = $this->CalendarSubscriptions->CalendarSources
            ->find()
            ->where(['CalendarSources.id IN' => $expiring])
            ->contain('CalendarProviders')
            ->all();

        $io->verbose('Starting calendar subscription renewal');
        foreach ($results as $row) {
            $io->out("Renewing subscription for source id={$row->id}");
            $provider = $row->calendar_provider;
            $this->calendarService->setAccessToken($provider);
            try {
                $this->calendarService->createSubscription($row);
                $io->verbose('New subscription created.');
            } catch (RuntimeException $e) {
                $io->out('<error>Could not create subscription</error>');
                $io->out('Error was:');
                $io->out($e->getMessage());
            }

            $this->CalendarSubscriptions->delete($row);
            $io->out("Previous subscription deleted. id={$row->id}");
        }

        $results = $this->CalendarSubscriptions->CalendarSources->find('missingSubscription')
            ->contain('CalendarProviders')
            ->all();

        $io->verbose('Creating missing subscriptions');
        foreach ($results as $row) {
            $io->out("Creating new subscription for source id={$row->id}");
            $provider = $row->calendar_provider;
            $this->calendarService->setAccessToken($provider);
            try {
                $this->calendarService->createSubscription($row);
                $io->verbose('New subscription created.');
            } catch (RuntimeException $e) {
                $io->out('<error>Could not create subscription</error>');
                $io->out('Error was:');
                $io->out($e->getMessage());
            }
        }
        $io->verbose('All Done.');

        return static::CODE_SUCCESS;
    }
}
