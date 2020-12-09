import React from 'react';
import ReactDOM from 'react-dom';

type State = {
  isDragging: boolean;
  draggingIndex: undefined | number;
  draggingTargetIndex: undefined | number;
  placeholderHeight: undefined | number;
  left: undefined | number;
  top: undefined | number;
};

const DRAG_CLASS = 'drag-item';
const GRAB_HANDLE_FUDGE = 25;

enum PlaceholderPosition {
  TOP,
  BOTTOM,
}

interface GeneralItem {
  id: number | string;
}

// TODO provide a prop to set the placeholder height.
type Props<Item> = {
  items: Item[];
  renderItem: (item: Item) => React.ReactNode;
  onChange: (items: Item[]) => void;
  itemElement: JSX.Element;
};

function isReactEvent(
  maybe: React.MouseEvent | React.TouchEvent | MouseEvent | TouchEvent
): maybe is React.MouseEvent | React.TouchEvent {
  return 'nativeEvent' in maybe;
}

function getPosition(
  event: React.MouseEvent | React.TouchEvent | MouseEvent | TouchEvent,
  field: 'pageX' | 'pageY'
) {
  let actual = isReactEvent(event) ? event.nativeEvent : event;

  if (window.TouchEvent && actual instanceof TouchEvent) {
    return actual.targetTouches[0][field];
  }
  if (actual instanceof MouseEvent) {
    return actual[field];
  }
  return 0;
}

// TODO figure out how to make this keyboard accessible
class DragContainer<Item extends GeneralItem> extends React.Component<
  Props<Item>,
  State
> {
  static defaultProps = {
    itemElement: <div />,
  };

  state = {
    isDragging: false,
    draggingIndex: undefined,
    draggingTargetIndex: undefined,
    placeholderHeight: undefined,
    left: undefined,
    top: undefined,
  };

  componentDidMount() {
    let portal: HTMLElement | null = document.getElementById('drag-container-portal');
    if (!portal) {
      portal = document.createElement('div');

      portal.setAttribute('id', 'drag-container-portal');
      portal.style.position = 'absolute';
      portal.style.top = '0';
      portal.style.left = '0';
      portal.style.zIndex = '1000';
      document.body.appendChild(portal);
    }
    this.portal = portal;
  }

  componentWillUnmount() {
    this.cleanUpListeners();
  }

  scopeClass: string = `drag-scope-${Math.round(Math.random() * 10000)}`;
  previousUserSelect: string | null = null;
  portal: HTMLElement | null = null;
  dragGhostRef = React.createRef<HTMLDivElement>();

  cleanUpListeners() {
    if (this.state.isDragging) {
      window.removeEventListener('mousemove', this.onDragMove);
      window.removeEventListener('touchmove', this.onDragMove);
      window.removeEventListener('mouseup', this.onDragEnd);
      window.removeEventListener('touchstart', this.onDragEnd);
    }
  }

  startDrag(
    event: React.MouseEvent<HTMLButtonElement> | React.TouchEvent<HTMLButtonElement>,
    index: number
  ) {
    const isDragging = this.state.isDragging;
    if (isDragging || !['mousedown', 'touchstart'].includes(event.type)) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();

    // prevent the user from selecting things when dragging a column.
    this.previousUserSelect = document.body.style.userSelect;

    // attach event listeners so that the mouse cursor can drag anywhere
    window.addEventListener('mousemove', this.onDragMove);
    window.addEventListener('mouseup', this.onDragEnd);
    window.addEventListener('touchmove', this.onDragMove);
    window.addEventListener('touchend', this.onDragEnd);

    // event.target is the drag handle button, so we need the parentNode.
    const dragItem = (event.target as HTMLElement).parentNode as HTMLElement;

    this.setState({
      isDragging: true,
      draggingIndex: index,
      draggingTargetIndex: index,
      placeholderHeight: dragItem.getBoundingClientRect().height,
      top: getPosition(event, 'pageY'),
      left: getPosition(event, 'pageX'),
    });
  }

  onDragMove = (event: MouseEvent | TouchEvent) => {
    if (!this.state.isDragging || !['mousemove', 'touchmove'].includes(event.type)) {
      return;
    }
    const pointerX = getPosition(event, 'pageX');
    const pointerY = getPosition(event, 'pageY');

    if (this.dragGhostRef.current) {
      // move the ghost box
      const ghostDOM = this.dragGhostRef.current;
      // Adjust so cursor is over the grab handle.
      ghostDOM.style.left = `${pointerX - GRAB_HANDLE_FUDGE}px`;
      ghostDOM.style.top = `${pointerY - GRAB_HANDLE_FUDGE}px`;
    }

    const dragItems = document.querySelectorAll(`.${DRAG_CLASS}.${this.scopeClass}`);

    // Find the item that the ghost is currently over.
    const targetIndex = Array.from(dragItems).findIndex(dragItem => {
      const rects = dragItem.getBoundingClientRect();
      const top = pointerY;

      const thresholdStart = window.scrollY + rects.top;
      const thresholdEnd = window.scrollY + rects.top + rects.height;

      return top >= thresholdStart && top <= thresholdEnd;
    });

    if (targetIndex >= 0 && targetIndex !== this.state.draggingTargetIndex) {
      this.setState({draggingTargetIndex: targetIndex});
    }
  };

  onDragEnd = (event: MouseEvent | TouchEvent) => {
    if (!this.state.isDragging || !['mouseup', 'touchend'].includes(event.type)) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();

    const sourceIndex = this.state.draggingIndex;
    const targetIndex = this.state.draggingTargetIndex;
    if (typeof sourceIndex !== 'number' || typeof targetIndex !== 'number') {
      return;
    }

    // remove listeners that were attached in startColumnDrag
    this.cleanUpListeners();

    // restore body user-select values
    if (this.previousUserSelect) {
      document.body.style.userSelect = this.previousUserSelect;
      this.previousUserSelect = null;
    }

    // Reorder columns and trigger change.
    const newColumns = [...this.props.items];
    const removed = newColumns.splice(sourceIndex, 1);
    newColumns.splice(targetIndex, 0, removed[0]);
    this.props.onChange(newColumns);

    this.setState({
      isDragging: false,
      placeholderHeight: undefined,
      left: undefined,
      top: undefined,
      draggingIndex: undefined,
      draggingTargetIndex: undefined,
    });
  };

  renderGhost() {
    const index = this.state.draggingIndex;
    if (typeof index !== 'number' || !this.state.isDragging || !this.portal) {
      return null;
    }
    const top = Number(this.state.top) - GRAB_HANDLE_FUDGE;
    const left = Number(this.state.left) - GRAB_HANDLE_FUDGE;
    const item = this.props.items[index];

    const style = {
      top: `${top}px`,
      left: `${left}px`,
    };
    const ghost = (
      <div className="drag-ghost" ref={this.dragGhostRef} style={style}>
        {this.renderItemOrPlaceholder(item, index, {isGhost: true})}
      </div>
    );

    return ReactDOM.createPortal(ghost, this.portal);
  }

  renderItemOrPlaceholder(item: Item, i: number, {isGhost = false}: {isGhost?: boolean}) {
    const {
      isDragging,
      draggingTargetIndex,
      draggingIndex,
      placeholderHeight,
    } = this.state;
    const {itemElement} = this.props;

    let placeholder: React.ReactNode = null;
    // Add a placeholder above the target row.
    if (isDragging && isGhost === false && draggingTargetIndex === i) {
      placeholder = React.cloneElement(itemElement, {
        className: `drag-placeholder ${DRAG_CLASS} ${this.scopeClass}`,
        key: `placeholder:${item.id}:true`,
        style: {height: `${placeholderHeight}px`},
      });
    }

    // If the current row is the row in the drag ghost return the placeholder
    if (isDragging && isGhost === false && draggingIndex === i) {
      return placeholder;
    }

    const position =
      Number(draggingTargetIndex) <= Number(draggingIndex)
        ? PlaceholderPosition.TOP
        : PlaceholderPosition.BOTTOM;

    const contents = (
      <React.Fragment>
        <button
          className="drag-handle"
          aria-label="Drag to reorder"
          onMouseDown={event => this.startDrag(event, i)}
          onTouchStart={event => this.startDrag(event, i)}
        >
          ::
        </button>
        {this.props.renderItem(item)}
      </React.Fragment>
    );

    if (isGhost) {
      return contents;
    }

    return (
      <React.Fragment key={`${i}:${item.id}:${isGhost}`}>
        {position === PlaceholderPosition.TOP && placeholder}
        {React.cloneElement(
          itemElement,
          {className: isGhost ? '' : `${DRAG_CLASS} ${this.scopeClass}`},
          contents
        )}
        {position === PlaceholderPosition.BOTTOM && placeholder}
      </React.Fragment>
    );
  }

  render() {
    const {items} = this.props;
    return (
      <React.Fragment>
        {this.renderGhost()}
        {items.map((item: Item, i: number) =>
          this.renderItemOrPlaceholder(item, i, {isGhost: false})
        )}
      </React.Fragment>
    );
  }
}

export default DragContainer;
