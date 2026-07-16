import { render, html, SearchPage } from './components.js';

export class SearchTab {
    constructor() {
        this.has_appeared = false;
    }

    appear(container) {
        if (!this.has_appeared) {
            render(html`<${SearchPage} query=${window.im.conversations.q} />`, container);
            this.has_appeared = true;
        }
    }
}
