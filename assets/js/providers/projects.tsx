import { useState, createContext, useContext } from 'react';
import * as React from 'react';

import {Project} from 'app/types';

type ContextData = {
  state: Project[];
  setProjects: (projects: null | Project[]) => void;
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
  const [state, setState] = useState<null | Project[]>(projects);
  const contextValue = {
    state: state || projects,
    setProjects: setState,
  };

  return (
    <ProjectsContext.Provider value={contextValue}>{children}</ProjectsContext.Provider>
  );
}

function useProjects(): [ContextData['state'], ContextData['setProjects']] {
  const {state, setProjects} = useContext(ProjectsContext);

  return [state, setProjects];
}

export {useProjects, ProjectsProvider};
