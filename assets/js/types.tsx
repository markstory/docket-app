export interface SortableJsEvent extends Event {
  to: HTMLElement;
  from: HTMLElement;
  item: HTMLElement;
  newIndex?: number;
  oldIndex?: number;
  newDraggableIndex?: number;
  oldDraggableIndex?: number;
}
