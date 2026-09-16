import $ from 'jquery';

import 'ez-plus/css/jquery.ez-plus.css';
import VotingApp from './voting/VotingApp.js';

window.$ = $;
window.jQuery = $;

function bootVotePage(zoomAvailable) {
    new VotingApp($, zoomAvailable).boot();
}

import('ez-plus')
    .then(() => bootVotePage(true))
    .catch(() => bootVotePage(false));
