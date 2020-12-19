import React, {useState, createContext, useContext} from 'react';

import {Subtask} from 'app/types';

type ContextData = {
  state: Subtask[];
  setSubtasks: (projects: Subtask[]) => void;
};
const SubtasksContext = createContext<ContextData>({
  state: [],
  setSubtasks: () => {},
});

type ProviderProps = {
  children: React.ReactNode;
  subtasks: Subtask[];
};

function SubtasksProvider({subtasks, children}: ProviderProps) {
  // TODO when props change the context data isn't changing.
  // Perhaps this shouldn't be using state?
  const [state, setState] = useState<Subtask[]>(subtasks);
  const contextValue = {
    state,
    setSubtasks: setState,
  };

  return (
    <SubtasksContext.Provider value={contextValue}>{children}</SubtasksContext.Provider>
  );
}

function useSubtasks(): [Subtask[], (tasks: Subtask[]) => void] {
  const {state, setSubtasks} = useContext(SubtasksContext);

  return [state, setSubtasks];
}

export {useSubtasks, SubtasksProvider};
