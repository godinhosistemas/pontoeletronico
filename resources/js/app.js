import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// Registra o plugin focus do Alpine
Alpine.plugin(focus);

// Disponibiliza o Alpine globalmente
window.Alpine = Alpine;

// Inicia o Alpine
Alpine.start();
