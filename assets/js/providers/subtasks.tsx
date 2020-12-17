import React, {useState, createContext, useContext} from 'react';

import {TodoSubtask} from 'app/types';

type ContextData = {
  state: TodoSubtask[];
  setSubtasks: (projects: TodoSubtask[]) => void;
};
const SubtasksContext = createContext<ContextData>({
  state: [],
  setSubtasks: () => {},
});

type ProviderProps = {
  children: React.ReactNode;
  subtasks: TodoSubtask[];
};

function SubtasksProvider({subtasks, children}: ProviderProps) {
  // TODO when props change the context data isn't changing.
  // Perhaps this shouldn't be using state?
  const [state, setState] = useState<TodoSubtask[]>(subtasks);
  const contextValue = {
    state,
    setSubtasks: setState,
  };

  return (
    <SubtasksContext.Provider value={contextValue}>{children}</SubtasksContext.Provider>
  );
}

function useSubtasks(): [TodoSubtask[], (tasks: TodoSubtask[]) => void] {
  const {state, setSubtasks} = useContext(SubtasksContext);

  return [state, setSubtasks];
}

export {useSubtasks, SubtasksProvider};
