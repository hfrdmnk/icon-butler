import './bootstrap';

import Autosize from '@marcreichel/alpine-autosize';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.plugin(Autosize);

Livewire.start();
