import './bootstrap.js';
import './styles/app.css';

// Close open dropdown menus when clicking somewhere else.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details.menu[open]').forEach((menu) => {
        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});
