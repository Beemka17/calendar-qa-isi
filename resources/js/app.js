import './bootstrap';
import './calendar';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

window.tippy = tippy;
