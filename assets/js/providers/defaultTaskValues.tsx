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
      let dateMatch = false;
      // Keep the lowest date as it is at the top of the
      // viewport which is where we want to add.
      if (acc.due_on === null) {
        acc.due_on = item.due_on;
        dateMatch = true;
      } else if (item.due_on < acc.due_on) {
        acc.due_on = item.due_on;
        dateMatch = true;
      } else if (item.due_on == acc.due_on) {
        dateMatch = true;
      }

      if (item.project_id) {
        acc.project_id = item.project_id;
      }
      if (dateMatch) {
        acc.evening = item.evening;
        acc.section_id = item.section_id;
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
      items = state.items.filter(item => !roughlyEqual(item, action.data));
      return {
        items,
        distilled: distillState(items),
      };
  }
}

/**
 * Rough equivalence. When comparing default task values
 * we want rougher equivalence than javascript provides.
 */
function roughlyEqual(first, second): bool {
  const firstKeys = Object.keys(first);
  const secondKeys = Object.keys(second);

  if (firstKeys.length !== secondKeys.length) {
    return false;
  }

  for (let i = 0; i <= firstKeys.length; i++) {
    const key = firstKeys[i];
    if (first[key] != second[key]) {
      return false;
    }
  }
  return true;
}

/**
 * Exposed context data and updater
 */
type ContextData = [State['distilled'], React.Dispatch<Action>];

export const DefaultTaskValuesContext = createContext<ContextData>([
  initialState.distilled,
  () => {},
]);

/**
 * Hook for DefaultValues reducer.
 *
 * Primarily intended for testing and possibly future use for nested state?
 */
export function useDefaultTaskValues(): [State, React.Dispatch<Action>] {
  const [state, dispatch] = useReducer(defaultTaskValuesReducer, initialState);
  return [state, dispatch];
}

type StoreProps = React.PropsWithChildren<{}>;

/**
 * Context Provider for Default Task context data.
 */
function DefaultTaskValuesStore({children}: StoreProps) {
  const [state, dispatch] = useDefaultTaskValues();

  return (
    <DefaultTaskValuesContext.Provider value={[state.distilled, dispatch]}>
      {children}
    </DefaultTaskValuesContext.Provider>
  );
}

export default DefaultTaskValuesStore;
