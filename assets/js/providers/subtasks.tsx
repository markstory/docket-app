import { useState, createContext, useContext } from 'react';
import * as React from 'react';

import {Subtask} from 'app/types';

type ContextData = {
  state: Subtask[];
  setSubtasks: (subtasks: null | Subtask[]) => void;
};
const SubtasksContext = createContext<ContextData>({
  state: [],
  setSubtasks: () => {},
});

type ProviderProps = {
  children: React.ReactNode;
  subtasks: Subtask[];
};

function SubtasksProvider({subtasks, children}: ProviderProps): JSX.Element {
  // Default state to null as we only need it to hold state
  // while a sorting request is being made.
  const [state, setState] = useState<null | Subtask[]>(null);
  const contextValue = {
    state: state || subtasks,
    setSubtasks: setState,
  };

  return (
    <SubtasksContext.Provider value={contextValue}>{children}</SubtasksContext.Provider>
  );
}

function useSubtasks(): [ContextData['state'], ContextData['setSubtasks']] {
  const {state, setSubtasks} = useContext(SubtasksContext);

  return [state, setSubtasks];
}

export {useSubtasks, SubtasksProvider};
