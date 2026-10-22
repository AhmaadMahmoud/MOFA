/*
 * Public site JavaScript for plain pages: Alpine alone (small, fast on phones).
 * Pages with Livewire components load livewire.js instead (see the layout).
 */
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { registerMofa } from './setup';

Alpine.plugin(focus);
registerMofa(Alpine);
window.Alpine = Alpine;
Alpine.start();
