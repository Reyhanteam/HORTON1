import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';

const sidebarHiddenClass = 'translate-x-full';

window.hortonAdmin = {
    isSidebarOpen() {
        const sidebar = document.querySelector('[data-sidebar]');
        return sidebar && !sidebar.classList.contains(sidebarHiddenClass);
    },
    toggleSidebar() {
        this.isSidebarOpen() ? this.closeSidebar() : this.openSidebar();
    },
    openSidebar() {
        document.querySelector('[data-sidebar]')?.classList.remove(sidebarHiddenClass);
        document.querySelector('[data-sidebar-backdrop]')?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    },
    closeSidebar() {
        document.querySelector('[data-sidebar]')?.classList.add(sidebarHiddenClass);
        document.querySelector('[data-sidebar-backdrop]')?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    },
    toast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-5 end-5 z-[100] rounded-2xl px-4 py-3 text-sm font-medium text-white shadow-xl ${type === 'success' ? 'bg-ink' : 'bg-danger'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3200);
    },
};

window.crm = window.hortonAdmin;

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toast]');
    if (button) {
        window.hortonAdmin.toast(button.dataset.toast);
    }

    if (event.target.closest('[data-sidebar] nav a') && window.innerWidth < 1024) {
        window.hortonAdmin.closeSidebar();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    window.jalaliDatepicker?.startWatch({
        time: true,
        hasSecond: false,
        persianDigits: true,
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) window.hortonAdmin.closeSidebar();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && window.hortonAdmin.isSidebarOpen()) {
            window.hortonAdmin.closeSidebar();
        }
    });
});
