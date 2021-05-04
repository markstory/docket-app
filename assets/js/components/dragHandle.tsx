import {Icon} from './icon';
import {useSortable} from '@dnd-kit/sortable';

type Props = Partial<Pick<ReturnType<typeof useSortable>, 'attributes' | 'listeners'>>;

function DragHandle({attributes, listeners}: Props): JSX.Element {
  return (
    <button className="dnd-handle" {...attributes} {...listeners}>
      <Icon icon="grabber" width="xlarge" />
    </button>
  );
}

export default DragHandle;
