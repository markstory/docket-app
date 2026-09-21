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
use Psr\Log\LogLevel;
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

        $results = $this->CalendarSubscriptions
            ->find('expiring')
            ->contain(['CalendarSources.CalendarProviders'])
            ->all();

        $this->log('Starting calendar subscription renewal', LogLevel::INFO);
        $renewed = [];
        foreach ($results as $row) {
            /** @var \Calendar\Model\Entity\CalendarSubscription $row **/
            $this->log("Renewing subscription for source id={$row->calendar_source_id}", LogLevel::INFO);
            if (in_array($row->calendar_source_id, $renewed, true)) {
                $io->verbose("Skipping subscription {$row->id}. Source already has renewed subscription.");
                continue;
            }

            $provider = $row->calendar_source->calendar_provider;
            $this->calendarService->setAccessToken($provider);
            try {
                $this->calendarService->createSubscription($row->calendar_source);
                $io->verbose("New subscription created for source={$row->calendar_source->id}.");

                $this->CalendarSubscriptions->delete($row);
                $this->log("Previous subscription deleted. id={$row->id}", LogLevel::INFO);

                $renewed[] = $row->calendar_source_id;
            } catch (RuntimeException $e) {
                $this->log('Could not create subscription');
                $this->log('Error was:' . $e->getMessage());
            }
        }

        $results = $this->CalendarSubscriptions->CalendarSources->find('missingSubscription')
            ->contain('CalendarProviders')
            ->all();

        $io->verbose('Creating missing subscriptions');
        foreach ($results as $row) {
            $this->log("Creating new subscription for source id={$row->id}", LogLevel::INFO);
            $provider = $row->calendar_provider;
            $this->calendarService->setAccessToken($provider);
            try {
                $this->calendarService->createSubscription($row);
                $io->verbose('New subscription created.');
            } catch (RuntimeException $e) {
                $this->log('Could not create subscription');
                $this->log('Error was:' . $e->getMessage());
            }
        }
        $this->log('Done calendar subscription renewal', LogLevel::INFO);

        return static::CODE_SUCCESS;
    }
}
