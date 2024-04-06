<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenTime;

/**
 * Cleanup command.
 */
class CleanupCommand extends Command
{
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
        $parser->setDescription([
            'This command performs periodic cleanup on application data.',
            '',
            'Steps of this cleanup are:',
            '',
            '- Removing tasks that were deleted 14 days ago or more.',
            '',
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
        $io->quiet('Starting cleanup');

        $expires = \Cake\I18n\DateTime::parse('-14 days');
        $io->verbose("Going to remove tasks older than {$expires->toDateTimeString()}");

        $tasks = $this->fetchTable('Tasks');
        $query = $tasks->deleteQuery()
            ->where([
                'Tasks.deleted_at IS NOT' => null,
                'Tasks.deleted_at <' => $expires,
            ]);
        $result = $query->execute();
        $io->out("{$result->rowCount()} tasks were deleted");

        $io->quiet('Cleanup complete');
    }
}
