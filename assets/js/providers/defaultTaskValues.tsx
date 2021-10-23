import {createContext, useReducer} from 'react';
import {DefaultTaskValues} from 'app/types';

/**
 * Internal state for the hook
 *
 * Only the `distilled` property is exposed, as items is
 * meant to be internal.
 */
type State = {
  items: DefaultTaskValues[];
  distilled: DefaultTaskValues;
};

/**
 * Actions that can be dispatched
 */
type Action = {
  type: 'add' | 'remove';
  data: DefaultTaskValues;
};

const initialState: State = {
  items: [],
  distilled: {},
};

function distillState(items: State['items']) {
  return items.reduce(
    (acc: any, item: any) => {
      // Keep the lowest date as it is at the top of the
      // viewport which is where we want to add.
      if (acc.due_on === null) {
        acc.due_on = item.due_on;
      } else if (item.due_on < acc.due_on) {
        acc.due_on = item.due_on;
      }

      return acc;
    },
    {due_on: null}
  );
}

function defaultTaskValuesReducer(state: State, action: Action) {
  let items = [];
  switch (action.type) {
    case 'add':
      items = [...state.items, action.data];
      return {
        items,
        distilled: distillState(items),
      };
    case 'remove':
      items = state.items.filter(item => item !== action.data);
      return {
        items,
        distilled: distillState(items),
      };
  }
}

/**
 * Exposed context data and updater
 */
type ContextData = [State['distilled'], React.Dispatch<Action>];

export const DefaultTaskValuesContext = createContext<ContextData>([
  initialState.distilled,
  () => {},
]);

type StoreProps = React.PropsWithChildren<{}>;

/**
 * Context Provider for Default Task context data.
 */
function DefaultTaskValuesStore({children}: StoreProps) {
  const [state, dispatch] = useReducer(defaultTaskValuesReducer, initialState);

  return (
    <DefaultTaskValuesContext.Provider value={[state.distilled, dispatch]}>
      {children}
    </DefaultTaskValuesContext.Provider>
  );
}

export default DefaultTaskValuesStore;
