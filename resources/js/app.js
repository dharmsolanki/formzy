import './bootstrap';

import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.default.css';

window.Alpine = Alpine;
window.TomSelect = TomSelect;

// Any <select class="searchable"> becomes a searchable dropdown
window.initSearchable = function (root = document) {
    root.querySelectorAll('select.searchable:not(.tomselected)').forEach((el) => {
        new TomSelect(el, {
            allowEmptyOption: true,
            maxOptions: null,
            create: false,
        });
    });
};

Alpine.start();
window.initSearchable();