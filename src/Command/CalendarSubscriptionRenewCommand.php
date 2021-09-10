<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\CalendarService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use RuntimeException;

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

        $expiring = $this->CalendarSubscriptions->find('expiring')
            ->select(['CalendarSubscriptions.id']);

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
            $io->out('Previous subscription deleted.');
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
    }
}
