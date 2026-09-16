import { ApiContractError } from '../api/contract.js';
import { mapCarModelsResponse } from '../api/models.js';
import { mapVoteResponse, mapVotingCycleResponse, mapVotingPairResponse } from '../api/voting.js';
import text from '../lang/ru/voting.js';
import { selectedModel, storeSelectedModel } from '../shared/selectedModelStorage.js';

const api = {
    cycle: '/api/voting/cycle',
    models: '/api/voting/models',
    pair: '/api/voting/pair',
    votes: '/api/voting/votes',
};

export default class VotingApp
{
    constructor($, zoomAvailable) {
        this.$ = $;
        this.zoomAvailable = zoomAvailable;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        this.currentPair = null;
        this.isCycleExhausted = false;
        this.isBusy = false;
        this.pairRequest = null;
        this.pairRequestSequence = 0;
        this.models = [];
        this.elements = {
            cars: {
                left: {
                    auction: this.$('#vote-left-auction'),
                    card: this.$('#vote-left-card'),
                    image: this.$('#vote-left-image'),
                    title: this.$('#vote-left-title'),
                },
                right: {
                    auction: this.$('#vote-right-auction'),
                    card: this.$('#vote-right-card'),
                    image: this.$('#vote-right-image'),
                    title: this.$('#vote-right-title'),
                },
            },
            divider: this.$('#vote-divider'),
            emptyCopy: this.$('#vote-empty-copy'),
            emptyState: this.$('#vote-empty-state'),
            emptyTitle: this.$('#vote-empty-title'),
            hint: this.$('#vote-model-hint'),
            modal: this.$('#vote-cycle-modal'),
            modalCancel: this.$('#vote-cancel-cycle'),
            modalConfirm: this.$('#vote-confirm-cycle'),
            model: this.$('#vote-model'),
            notice: this.$('#vote-notice'),
            pair: this.$('#vote-pair'),
            restart: this.$('#vote-restart-cycle'),
            voteButtons: this.$('.vote-button'),
        };
    }

    boot() {
        this.showEmpty(text.empty.noModel);
        this.bindEvents();
        this.loadModels();
    }

    bindEvents() {
        this.elements.model.on('change', () => {
            storeSelectedModel(this.elements.model.val());
            this.loadPair();
        });
        this.elements.voteButtons.on('click', (event) => this.submitVote(this.$(event.currentTarget).data('winner-side')));
        this.elements.restart.on('click', () => this.openRestartModal());
        this.elements.modalCancel.on('click', () => this.closeRestartModal());
        this.elements.modalConfirm.on('click', (event) => {
            event.preventDefault();
            this.closeRestartModal();
            this.restartCycle();
        });
        this.elements.modal.on('click', (event) => {
            if (event.target === event.currentTarget) {
                this.closeRestartModal();
            }
        });
        this.$(document).on('keydown', (event) => {
            if (event.key === 'Escape' && !this.elements.modal.is('[hidden]')) {
                this.closeRestartModal();
            }
        });
    }

    messageFrom(response) {
        if (response instanceof ApiContractError) {
            return text.errors.fallback;
        }

        const errors = response.responseJSON?.errors ?? {};
        const firstField = Object.values(errors)[0];

        return firstField?.[0] ?? response.responseJSON?.message ?? text.errors.fallback;
    }

    setBusy(value) {
        this.isBusy = value;
        this.elements.model.prop('disabled', value);
        this.elements.voteButtons.prop('disabled', value);
        this.elements.restart.prop('disabled', value);
    }

    showNotice(message = '', type = '') {
        this.elements.notice.text(message).removeClass('is-error is-success is-loading');

        if (type) {
            this.elements.notice.addClass(`is-${type}`);
        }
    }

    destroyZoom() {
        if (!this.zoomAvailable) {
            return;
        }

        this.elements.pair.find('.zoomable-photo').each((_, image) => {
            this.$(image).data('ezPlus')?.destroy();
        });
    }

    initializeZoom() {
        if (!this.zoomAvailable) {
            return;
        }

        this.elements.pair.find('.zoomable-photo').each((_, image) => {
            const initialize = () => this.initializeImageZoom(image);

            if (image.complete && image.naturalWidth > 0) {
                initialize();

                return;
            }

            this.$(image).one('load.zoom', initialize);
        });
    }

    initializeImageZoom(image) {
        this.$(image).data('ezPlus')?.destroy();
        this.$(image).ezPlus({
                cursor: 'crosshair',
                easing: true,
                tint: true,
                tintColour: '#f1b24a',
                tintOpacity: 0.32,
                zoomLevel: 1.6,
                zoomType: 'window',
                zoomWindowPosition: 1,
            });
    }

    renderCar(side, car) {
        const elements = this.elements.cars[side];

        elements.image.attr({
            alt: text.car.alt(car),
            src: car.imageUrl,
        });
        elements.title.text(text.car.title(car));
        elements.auction.text(text.car.auction(car));
    }

    renderUnavailableCar(car) {
        this.destroyZoom();
        this.currentPair = null;
        this.renderCar('left', car);
        this.elements.pair.addClass('is-single').removeAttr('hidden');
        this.elements.cars.right.card.attr('hidden', true);
        this.elements.divider.attr('hidden', true);
        this.elements.voteButtons.attr('hidden', true);
        this.elements.emptyState.attr('hidden', true);
        requestAnimationFrame(() => this.initializeZoom());
    }

    showEmpty({ title, copy }, canRestart = false) {
        this.destroyZoom();
        this.currentPair = null;
        this.elements.pair.attr('hidden', true);
        this.elements.emptyTitle.text(title);
        this.elements.emptyCopy.text(copy);
        this.elements.emptyState.removeAttr('hidden');
        this.elements.restart.prop('hidden', !canRestart);
    }

    renderPair(pair) {
        if (pair.status === 'unavailable') {
            this.isCycleExhausted = false;

            if (pair.leftCar) {
                this.renderUnavailableCar(pair.leftCar);
            } else {
                this.showEmpty(text.empty.noCars);
            }

            this.showNotice(text.notices.unavailablePair);

            return;
        }

        if (pair.status === 'exhausted') {
            this.isCycleExhausted = true;
            this.showEmpty(text.empty.exhausted, true);
            this.showNotice();
            this.openRestartModal();

            return;
        }

        this.destroyZoom();
        this.isCycleExhausted = false;
        this.currentPair = pair;
        this.elements.pair.removeClass('is-single');
        this.elements.cars.right.card.removeAttr('hidden');
        this.elements.divider.removeAttr('hidden');
        this.elements.voteButtons.removeAttr('hidden');
        this.renderCar('left', pair.leftCar);
        this.renderCar('right', pair.rightCar);
        this.elements.emptyState.attr('hidden', true);
        this.elements.pair.removeAttr('hidden');
        requestAnimationFrame(() => this.initializeZoom());
        this.showNotice();
    }

    loadPair() {
        const selectedModel = this.models.find((model) => model.key === this.elements.model.val());
        const requestId = ++this.pairRequestSequence;

        this.pairRequest?.abort();

        if (!selectedModel) {
            this.setBusy(false);
            this.showEmpty(text.empty.noModel);

            return;
        }

        this.destroyZoom();
        this.currentPair = null;
        this.isCycleExhausted = false;
        this.elements.pair.attr('hidden', true);
        this.elements.emptyState.attr('hidden', true);
        this.setBusy(true);
        this.showNotice(text.notices.loadingPair, 'loading');

        this.pairRequest = this.$.getJSON(api.pair, {
            make: selectedModel.make,
            model: selectedModel.model,
        })
            .done((payload) => {
                if (requestId === this.pairRequestSequence) {
                    try {
                        this.renderPair(mapVotingPairResponse(payload).pair);
                    } catch (response) {
                        this.showEmpty(text.empty.requestFailed);
                        this.showNotice(this.messageFrom(response), 'error');
                    }
                }
            })
            .fail((response, status) => {
                if (requestId === this.pairRequestSequence && status !== 'abort') {
                    this.showEmpty(text.empty.requestFailed);
                    this.showNotice(this.messageFrom(response), 'error');
                }
            })
            .always(() => {
                if (requestId === this.pairRequestSequence) {
                    this.pairRequest = null;
                    this.setBusy(false);
                }
            });
    }

    loadModels() {
        this.setBusy(true);
        this.showNotice(text.notices.loadingModels, 'loading');

        this.$.getJSON(api.models)
            .done((payload) => {
                let response;

                try {
                    response = mapCarModelsResponse(payload);
                } catch (error) {
                    this.showNotice(this.messageFrom(error), 'error');

                    return;
                }

                this.elements.model.empty().append(this.$('<option>', {
                    text: text.models.placeholder,
                    value: '',
                }));

                this.models = response.models;

                response.models.forEach((model) => {
                    this.elements.model.append(this.$('<option>', {
                        text: model.isVotable
                            ? text.models.option(model.name, model.carsCount)
                            : text.models.unavailableOption(model.name, model.carsCount),
                        value: model.key,
                    }));
                });

                const savedModel = selectedModel();

                if (savedModel && response.models.some((model) => model.key === savedModel)) {
                    this.elements.model.val(savedModel);
                }

                this.elements.hint.text(response.models.length ? text.models.hint : text.models.emptyHint);
                this.showNotice();
            })
            .fail((response) => this.showNotice(this.messageFrom(response), 'error'))
            .always(() => {
                this.setBusy(false);

                if (this.elements.model.val()) {
                    this.loadPair();
                }
            });
    }

    submitVote(winnerSide) {
        if (!this.currentPair || this.isBusy) {
            if (this.isCycleExhausted && !this.isBusy) {
                this.openRestartModal();
            }

            return;
        }

        this.setBusy(true);
        this.showNotice(text.notices.savingVote, 'loading');

        this.$.ajax({
            contentType: 'application/json',
            data: JSON.stringify({
                left_car_id: this.currentPair.leftCar.id,
                make: this.currentPair.make,
                model: this.currentPair.model,
                pair_token: this.currentPair.pairToken,
                right_car_id: this.currentPair.rightCar.id,
                winner_side: winnerSide,
            }),
            headers: { 'X-CSRF-TOKEN': this.csrfToken },
            method: 'POST',
            url: api.votes,
        })
            .done((payload) => {
                try {
                    this.renderPair(mapVoteResponse(payload).nextPair);
                    this.showNotice(text.notices.voteSaved, 'success');
                } catch (response) {
                    this.showNotice(this.messageFrom(response), 'error');
                }
            })
            .fail((response) => this.showNotice(this.messageFrom(response), 'error'))
            .always(() => this.setBusy(false));
    }

    restartCycle() {
        this.setBusy(true);
        this.showNotice(text.notices.restartingCycle, 'loading');

        this.$.ajax({
            headers: { 'X-CSRF-TOKEN': this.csrfToken },
            method: 'POST',
            url: api.cycle,
        })
            .done((payload) => {
                try {
                    this.csrfToken = mapVotingCycleResponse(payload).csrfToken;
                } catch (response) {
                    this.showNotice(this.messageFrom(response), 'error');
                    this.setBusy(false);

                    return;
                }

                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', this.csrfToken);
                this.showNotice(text.notices.cycleStarted, 'success');
                this.loadPair();
            })
            .fail((response) => {
                this.showNotice(this.messageFrom(response), 'error');
                this.setBusy(false);
            });
    }

    openRestartModal() {
        this.elements.modal.removeAttr('hidden');
        this.elements.modalConfirm.trigger('focus');
    }

    closeRestartModal() {
        this.elements.modal.attr('hidden', true);
        (this.elements.restart.prop('hidden') ? this.elements.model : this.elements.restart).trigger('focus');
    }
}
