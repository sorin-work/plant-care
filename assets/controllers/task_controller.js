import { Controller } from '@hotwired/stimulus';

/*
 * Sends done / skip / snooze forms in the background and removes the task
 * from the list when the server accepts it. Without JavaScript the forms
 * still work as normal posts.
 */
export default class extends Controller {
    static targets = ['status'];

    async submit(event) {
        event.preventDefault();
        const form = event.target.closest('form');
        const submitter = event.submitter;
        const body = new FormData(form, submitter);
        const action = submitter?.getAttribute('formaction') || form.action;

        this.element.classList.add('is-busy');
        this.element.querySelectorAll('button').forEach((b) => (b.disabled = true));

        try {
            const response = await fetch(action, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Something went wrong.');
            }
            this.finish(data.message);
        } catch (error) {
            this.element.classList.remove('is-busy');
            this.element.querySelectorAll('button').forEach((b) => (b.disabled = false));
            this.showStatus(error.message, true);
        }
    }

    finish(message) {
        this.showStatus(message, false);
        this.element.classList.add('is-done');
        setTimeout(() => {
            const list = this.element.closest('ul');
            this.element.remove();
            if (list && list.children.length === 0) {
                list.closest('.room-group')?.remove();
            }
            this.dispatch('changed', { prefix: 'today' });
        }, 600);
    }

    showStatus(message, isError) {
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.textContent = message;
        this.statusTarget.classList.toggle('danger', isError);
    }
}
