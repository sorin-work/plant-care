import { Controller } from '@hotwired/stimulus';

/*
 * Collapsible navigation on small screens.
 */
export default class extends Controller {
    static targets = ['toggle', 'panel'];

    connect() {
        this.onKeydown = (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        };
        document.addEventListener('keydown', this.onKeydown);
        this.element.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', () => this.close()));
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
    }

    toggle() {
        this.setOpen(!this.element.classList.contains('is-open'));
    }

    close() {
        this.setOpen(false);
    }

    setOpen(open) {
        this.element.classList.toggle('is-open', open);
        this.toggleTarget.setAttribute('aria-expanded', String(open));
    }
}
