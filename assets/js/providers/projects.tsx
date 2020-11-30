import React, {useState, createContext, useContext, useEffect} from 'react';

import {Project} from 'app/types';

type ContextData = {
  state: Project[];
  setProjects: (projects: Project[]) => void;
};
const ProjectsContext = createContext<ContextData>({
  state: [],
  setProjects: () => {},
});

type ProviderProps = {
  children: React.ReactNode;
  projects: Project[];
};

function ProjectsProvider({projects, children}: ProviderProps) {
  const [state, setState] = useState<Project[]>(projects);
  const [contextValue, setContextValue] = useState({
    state,
    // Include the inner state setter so context can be
    // updated later.
    setProjects: setState,
  });

  // When the internal state changes update context.
  useEffect(() => {
    setContextValue(prev => ({
      ...prev,
      state,
    }));
  }, [state]);

  return (
    <ProjectsContext.Provider value={contextValue}>{children}</ProjectsContext.Provider>
  );
}

function useProjects(): [Project[], (projects: Project[]) => void] {
  const {state, setProjects} = useContext(ProjectsContext);

  return [state, setProjects];
}

export {useProjects, ProjectsProvider};
