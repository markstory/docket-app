import {createContext} from 'react';

import {Project} from 'app/types';

const ProjectsContext = createContext<Project[]>([]);

export default ProjectsContext;
