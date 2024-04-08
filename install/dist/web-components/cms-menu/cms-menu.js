import HamburgerMenu from '/dist/zolinga-commons/web-components/hamburger-menu/hamburger-menu.js';

export default class CMSMenu extends HamburgerMenu {
    constructor() {
        super();
        this.ready(this.#init());
    }

    async #init() {
        this.classList.add('cms-menu');
        this.classList.remove('hidden');
    }
}