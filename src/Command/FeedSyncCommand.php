<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\FeedService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Paging\SimplePaginator;

/**
 * FeedSync command.
 */
class FeedSyncCommand extends Command
{
    private FeedService $feedService;

    public function __construct(FeedService $feedService, ?CommandFactoryInterface $factory = null)
    {
        parent::__construct($factory);
        $this->feedService = $feedService;
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
        $parser->setDescription(
            'Command to start synchronization of all feeds with subscriptions'
        )->addOption('limit', [
            'short' => 'l',
            'help' => 'The number of records to fetch in each pagination loop',
            'default' => '100',
        ]);

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
        $limit = (int)$args->getOption('limit');

        $feedsTable = $this->fetchTable('Feeds');
        $query = $feedsTable->find('activeSubscriptions');
        $paginator = new SimplePaginator();

        $io->out('Sync start');
        $result = null;
        while (true) {
            $result = $paginator->paginate($query, ['limit' => $limit]);
            foreach ($result->items() as $feed) {
                $io->verbose("Sync {$feed->url} start");
                $this->feedService->refreshFeed($feed);
                $io->verbose("Sync {$feed->url} end");
            }

            if (!$result->hasNextPage()) {
                break;
            }
        }
        $io->out('Sync complete');

        return self::CODE_SUCCESS;
    }
}
