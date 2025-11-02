import { Controller } from '@hotwired/stimulus';

/*
 * Submits the household switcher as soon as another household is picked.
 * Each <option> value is the switch URL for that household.
 */
export default class extends Controller {
    go(event) {
        this.element.action = event.target.value;
        this.element.requestSubmit();
    }
}
