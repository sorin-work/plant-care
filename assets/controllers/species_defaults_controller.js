import { Controller } from '@hotwired/stimulus';

/*
 * When a species or room is picked, show the species' care hint and
 * preselect a light level (the room's usual light wins over what the species
 * prefers), unless the light was already chosen by hand.
 */
export default class extends Controller {
    static targets = ['species', 'room', 'light', 'hint'];

    connect() {
        this.lightTouched = this.lightTarget.value !== '' && this.speciesTarget.value !== '';
        this.showHint();
    }

    touch() {
        this.lightTouched = true;
    }

    apply() {
        const fromRoom = this.hasRoomTarget ? this.roomTarget.selectedOptions[0]?.dataset.light : null;
        const fromSpecies = this.speciesTarget.selectedOptions[0]?.dataset.light;
        const light = fromRoom || fromSpecies;
        if (light && !this.lightTouched) {
            this.lightTarget.value = light;
        }
        this.showHint();
    }

    showHint() {
        const hint = this.speciesTarget.selectedOptions[0]?.dataset.hint;
        this.hintTarget.textContent = hint || '';
        this.hintTarget.hidden = !hint;
    }
}
