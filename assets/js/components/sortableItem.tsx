import classnames from 'classnames';
import {useSortable} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';

import DragHandle from 'app/components/dragHandle';

type Props = React.PropsWithChildren<{
  id: string;
  active?: string;
  tag?: 'li' | 'div';
}>;
function SortableItem({id, active, children, tag}: Props): JSX.Element {
  const {attributes, listeners, setNodeRef, transform, transition} = useSortable({
    id,
  });
  const style = {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
  };
  const className = classnames('dnd-item', {
    'dnd-ghost': id === active,
  });

  // Can't be bothered to figure out 'union too complex' error
  // when using a dynamic tag.
  if (tag === 'li') {
    return (
      <li className={className} ref={setNodeRef} style={style}>
        <DragHandle attributes={attributes} listeners={listeners} />
        {children}
      </li>
    );
  }
  return (
    <div className={className} ref={setNodeRef} style={style}>
      <DragHandle attributes={attributes} listeners={listeners} />
      {children}
    </div>
  );
}

export default SortableItem;
