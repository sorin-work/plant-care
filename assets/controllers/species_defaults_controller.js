import { Controller } from '@hotwired/stimulus';

/*
 * When a species is picked, show its care hint and preselect its preferred
 * light, unless the light was already chosen by hand.
 */
export default class extends Controller {
    static targets = ['species', 'light', 'hint'];

    connect() {
        this.lightTouched = this.lightTarget.value !== '' && this.speciesTarget.value !== '';
        this.showHint();
    }

    touch() {
        this.lightTouched = true;
    }

    apply() {
        const option = this.speciesTarget.selectedOptions[0];
        if (option?.dataset.light && !this.lightTouched) {
            this.lightTarget.value = option.dataset.light;
        }
        this.showHint();
    }

    showHint() {
        const hint = this.speciesTarget.selectedOptions[0]?.dataset.hint;
        this.hintTarget.textContent = hint || '';
        this.hintTarget.hidden = !hint;
    }
}
