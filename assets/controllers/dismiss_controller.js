import { Controller } from '@hotwired/stimulus';

/*
 * Removes the element after a delay or when the close button is clicked.
 * Used for flash messages.
 */
export default class extends Controller {
    static values = { after: { type: Number, default: 6000 } };

    connect() {
        if (this.afterValue > 0) {
            this.timeout = setTimeout(() => this.close(), this.afterValue);
        }
    }

    disconnect() {
        clearTimeout(this.timeout);
    }

    close() {
        this.element.classList.add('is-leaving');
        setTimeout(() => this.element.remove(), 200);
    }
}
