// assets/app.js

// Tailwind CSS pur
import './styles/app-tailwind.css';

// Tes styles personnalisés
import './styles/app.css';

// Alpine + Sortable
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Sortable = Sortable;

Alpine.start();
