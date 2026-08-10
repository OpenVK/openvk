import htm from '../../node_modules/htm/dist/htm.mjs';
import { h, render as preactRender } from '../../node_modules/preact/dist/preact.mjs';

export const html = htm.bind(h);
export const render = preactRender;
