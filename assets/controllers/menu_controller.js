import { Controller } from '@hotwired/stimulus';

/*
 * Collapsible navigation on small screens.
 */
export default class extends Controller {
    static targets = ['toggle', 'panel'];

    toggle() {
        const open = !this.element.classList.contains('is-open');
        this.element.classList.toggle('is-open', open);
        this.toggleTarget.setAttribute('aria-expanded', String(open));
    }
}
