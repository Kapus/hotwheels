document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const layout = document.getElementById('layout');

    if (toggleSidebar && sidebar && layout) {
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            layout.classList.toggle('sidebar-open');
        });
    }

    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('#sidebar .nav-link').forEach(link => {
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });

    const filterForm = document.querySelector('.js-filter-form');
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                filterForm.requestSubmit();
            });
        });
    }
});
