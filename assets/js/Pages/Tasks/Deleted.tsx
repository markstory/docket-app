import {Project, Task} from 'app/types';
import {t} from 'app/locale';
import {Icon} from 'app/components/icon';
import NoProjects from 'app/components/noProjects';
import TaskRow from 'app/components/taskRow';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  tasks: Task[];
  projects: Project[];
};

export default function TasksToday({tasks, projects}: Props): JSX.Element {
  const title = t('Trash Bin');
  if (!projects.length) {
    return (
      <LoggedIn title={title}>
        <NoProjects />
      </LoggedIn>
    );
  }

  return (
    <LoggedIn title={title}>
      <h2 className="heading-icon trash">
        <Icon icon="trash" className="trash" />
        {t('Trash Bin')}
      </h2>
      <p>{t('Trashed tasks will be deleted permanently after 14 days')}</p>
      {tasks.map(task => {
        return <TaskRow key={task.id} task={task} showProject showDueOn showRestore />;
      })}
    </LoggedIn>
  );
}
