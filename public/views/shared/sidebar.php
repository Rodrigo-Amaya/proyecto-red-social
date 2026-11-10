<aside class="sidebar">
    <a href="index.php?page=inicio" class="app-name">t'ASK</a>

    <nav class="menu">
        <a href="index.php?page=inicio" class="<?= ($currentPage ?? '') === 'inicio' ? 'active' : '' ?>" aria-label="Inicio" title="Inicio">
            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/>
                <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            <span>Inicio</span>
        </a>
        
        <a href="index.php?page=explorar" class="<?= ($currentPage ?? '') === 'explorar' ? 'active' : '' ?>" aria-label="Explorar" title="Explorar">
            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"/>
            </svg>
            <span>Explorar tareas</span>
        </a>

        <a href="index.php?page=mis-tareas" class="<?= ($currentPage ?? '') === 'mis-tareas' ? 'active' : '' ?>" aria-label="Mis tareas" title="Mis tareas">
            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 5h.01"/><path d="M3 12h.01"/><path d="M3 19h.01"/><path d="M8 5h13"/><path d="M8 12h13"/><path d="M8 19h13"/>
            </svg>
            <span>Mis tareas</span>
        </a>

        <a href="index.php?page=guardados" class="<?= ($currentPage ?? '') === 'guardados' ? 'active' : '' ?>" aria-label="Guardados" title="Guardados">
            <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17 3a2 2 0 0 1 2 2v15a1 1 0 0 1-1.496.868l-4.512-2.578a2 2 0 0 0-1.984 0l-4.512 2.578A1 1 0 0 1 5 20V5a2 2 0 0 1 2-2z"/>
            </svg>
            <span>Guardados</span>
        </a>
    </nav>

    <article class="sidebar-info">
        <h3>Lo que sabes hacer puede ayudar a alguien.</h3>
        <p>Así nos cuidamos.</p>
    </article>
</aside>