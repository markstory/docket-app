<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\CalendarService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * CalendarSourceSync command.
 */
class CalendarSourceSyncCommand extends Command
{
    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    protected $CalendarSources;

    /**
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * @var \App\Service\CalendarService
     */
    private $service;

    public function __construct(CalendarService $service)
    {
        $this->service = $service;
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
        $parser->setDescription('A debugging tool for calendar sync testing and development');
        $parser->addArgument('calendarSourceId', [
            'required' => true,
            'help' => __('The id of the calendar source to refresh.'),
        ]);

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
        $this->loadModel('CalendarSources');
        $this->loadModel('Users');

        $sourceId = $args->getArgument('calendarSourceId');
        $source = $this->CalendarSources->get($sourceId, ['contain' => ['CalendarProviders']]);
        $user = $this->Users->get($source->calendar_provider->user_id);

        $this->service->setAccessToken($source->calendar_provider);
        $io->out('Starting sync');
        $this->service->syncEvents($user, $source);
        $io->out('<success>Sync complete</success>');
    }
}
