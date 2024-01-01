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
import 'app/webcomponents/dropDown.ts';
import 'app/webcomponents/dueOn.ts';
import 'app/webcomponents/keyboardList';
import 'app/webcomponents/markdownText.ts';
import 'app/webcomponents/modalWindow.ts';
import 'app/webcomponents/reloadAfter.ts';
import 'app/webcomponents/selectBox.ts';
