import { Controller } from '@hotwired/stimulus';

/*
 * Keeps the task counter in sync and shows the empty state once the last
 * task has been handled.
 */
export default class extends Controller {
    static targets = ['count', 'empty'];

    update() {
        const remaining = this.element.querySelectorAll('[data-controller~="task"]').length;
        if (this.hasCountTarget) {
            this.countTarget.textContent = remaining === 0 ? '' : ` · ${remaining} ${remaining === 1 ? 'task' : 'tasks'}`;
        }
        if (remaining === 0 && this.hasEmptyTarget) {
            this.emptyTarget.hidden = false;
        }
    }
}
