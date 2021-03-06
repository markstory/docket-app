export function insertAtIndex<Item>(items: Item[], index: number, insert: Item): Item[] {
  return [...items.slice(0, index), insert, ...items.slice(index, items.length)];
}
