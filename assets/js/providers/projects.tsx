import React, {useState, createContext, useContext} from 'react';

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
  // TODO when props change the context data isn't changing.
  // Perhaps this shouldn't be using state?
  const [state, setState] = useState<Project[]>(projects);
  const contextValue = {
    state,
    setProjects: setState,
  };

  return (
    <ProjectsContext.Provider value={contextValue}>{children}</ProjectsContext.Provider>
  );
}

function useProjects(): [Project[], (projects: Project[]) => void] {
  const {state, setProjects} = useContext(ProjectsContext);

  return [state, setProjects];
}

export {useProjects, ProjectsProvider};
