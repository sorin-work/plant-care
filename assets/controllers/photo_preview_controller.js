import { Controller } from '@hotwired/stimulus';

/*
 * Shows a preview of the picked photo before it is uploaded and warns about
 * files that are too large, so nobody waits for an 8 MB upload to fail.
 */
export default class extends Controller {
    static targets = ['input', 'preview', 'message'];
    static values = { maxBytes: { type: Number, default: 8 * 1024 * 1024 } };

    show() {
        const file = this.inputTarget.files[0];
        this.clear();
        if (!file) {
            return;
        }
        if (!file.type.startsWith('image/')) {
            this.messageTarget.textContent = 'Please choose an image.';
            return;
        }
        if (file.size > this.maxBytesValue) {
            this.messageTarget.textContent = 'This photo is larger than 8 MB.';
            this.inputTarget.value = '';
            return;
        }
        this.url = URL.createObjectURL(file);
        this.previewTarget.src = this.url;
        this.previewTarget.hidden = false;
    }

    clear() {
        if (this.url) {
            URL.revokeObjectURL(this.url);
            this.url = null;
        }
        this.previewTarget.hidden = true;
        this.messageTarget.textContent = '';
    }

    disconnect() {
        this.clear();
    }
}
