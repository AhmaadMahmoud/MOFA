/*
 * Public site JavaScript for pages with Livewire components (checkout, order page).
 * Alpine and its plugins come from Livewire's bundle; the layout prints
 * @livewireScriptConfig instead of letting Livewire inject its own script.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerMofa } from './setup';

registerMofa(Alpine);
Livewire.start();
