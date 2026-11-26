// Phone menu: the button with id="menu-button" opens and closes id="mobile-menu"
const menuButton = document.getElementById('menu-button');
const mobileMenu = document.getElementById('mobile-menu');

if (menuButton && mobileMenu) {
    menuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
}


document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
});


document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});


document.querySelectorAll('[data-height]').forEach((el) => {
    el.style.height = el.dataset.height + 'px';
});

// Pop-up windows: a button with data-modal-open="id" opens <dialog id="id">
document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById(button.dataset.modalOpen)?.showModal();
    });
});

// Any button with data-modal-close closes its pop-up
document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog').close());
});

// Click on the dark background closes the pop-up. Reopen it after a form error.
document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
    }
});