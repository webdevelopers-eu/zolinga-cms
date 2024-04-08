import HamburgerMenu from '/dist/zolinga-commons/web-components/hamburger-menu/hamburger-menu.js';

export default class CMSMenu extends HamburgerMenu {
    constructor() {
        super();
        this.classList.add('cms-menu');
        this.dataset.ready = 'true';
    }
}