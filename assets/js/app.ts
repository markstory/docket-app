import 'vite/modulepreload-polyfill';

import '../sass/app.scss';

// Htmx setup
import htmx from 'htmx.org';

// Expose htmx on window
// @ts-ignore-next-line
window.htmx = htmx;

// htmx extensions
import 'app/extensions/ajax';
import 'app/extensions/flashMessage';
import 'app/extensions/hotkeys';
import 'app/extensions/projectSorter';
import 'app/extensions/taskSorter';
import 'app/extensions/sectionSorter';
import 'app/extensions/subtaskSorter';
import 'app/extensions/removeRow';

// Webcomponents
import 'app/webcomponents/dropDown';
import 'app/webcomponents/dueOn';
import 'app/webcomponents/keyboardList';
import 'app/webcomponents/markdownText';
import 'app/webcomponents/modalWindow';
import 'app/webcomponents/reloadAfter';
import 'app/webcomponents/selectBox';
import 'app/webcomponents/sideBar';
