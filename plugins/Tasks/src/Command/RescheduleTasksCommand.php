<?php
declare(strict_types=1);

namespace Tasks\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * RescheduleTasks command.
 */
class RescheduleTasksCommand extends Command
{
    use LocatorAwareTrait;

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
            'Development environment tool for rescheduling tasks' .
            'Will move all currently overdue items to between 0 and 14 days in the future.'
        );

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
        if (Configure::read('debug') !== true) {
            throw new StopException('Cannot rescheule tasks in non-dev environment.');
        }
        $tasks = $this->fetchTable('Tasks.Tasks');
        $query = $tasks->find('overdue');
        $today = new Date();
        $updated = 0;
        foreach ($query->all() as $task) {
            $days = rand(0, 14);
            $task->due_on = $today->modify("{$days} days");
            $tasks->save($task);
            $updated++;
        }
        $io->out("Updated {$updated} tasks");

        return static::CODE_SUCCESS;
    }
}
