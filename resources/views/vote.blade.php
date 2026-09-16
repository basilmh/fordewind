@extends('layouts.app')

@section('title', 'Голосование')

@push('scripts')
    @vite(['resources/js/vote.js'])
@endpush

@section('content')
    <section id="vote-app" class="vote-page" aria-labelledby="vote-title">
        <div class="page-intro vote-intro">
            <p class="eyebrow">Аукционный радар</p>
            <h1 id="vote-title">Выберите кадр, который стоит увидеть первым.</h1>
            <p>Сравнивайте фотографии автомобилей одной модели. Каждое решение сразу формирует рейтинг.</p>
        </div>

        <div class="vote-controls panel">
            <label for="vote-model">Модель автомобиля</label>
            <select id="vote-model" disabled>
                <option value="">Загружаем модели...</option>
            </select>
            <p id="vote-model-hint" class="field-hint">Выберите модель, чтобы начать сравнение.</p>
        </div>

        <p id="vote-notice" class="status-message" role="status" aria-live="polite"></p>

        <div id="vote-pair" class="vote-pair" hidden>
            <article id="vote-left-card" class="vote-card" data-side="left">
                <div class="photo-frame"><img id="vote-left-image" class="zoomable-photo" src="" alt=""></div>
                <div class="vote-card-meta">
                    <p class="card-kicker">Левая фотография</p>
                    <h2 id="vote-left-title"></h2>
                    <p id="vote-left-auction"></p>
                </div>
                <button class="vote-button" type="button" data-winner-side="left">Левая нравится больше</button>
            </article>

            <div id="vote-divider" class="vote-divider" aria-hidden="true"><span>или</span></div>

            <article id="vote-right-card" class="vote-card" data-side="right">
                <div class="photo-frame"><img id="vote-right-image" class="zoomable-photo" src="" alt=""></div>
                <div class="vote-card-meta">
                    <p class="card-kicker">Правая фотография</p>
                    <h2 id="vote-right-title"></h2>
                    <p id="vote-right-auction"></p>
                </div>
                <button class="vote-button" type="button" data-winner-side="right">Правая нравится больше</button>
            </article>
        </div>

        <div id="vote-empty-state" class="empty-state" hidden>
            <p id="vote-empty-title" class="empty-state-title"></p>
            <p id="vote-empty-copy"></p>
            <button id="vote-restart-cycle" class="secondary-button" type="button" hidden>Начать новый цикл</button>
        </div>

        <div id="vote-cycle-modal" class="modal-backdrop" hidden role="presentation">
            <section class="cycle-modal" role="dialog" aria-labelledby="cycle-modal-title" aria-modal="true">
                <p class="eyebrow">Новая session</p>
                <h2 id="cycle-modal-title">Начать новый цикл?</h2>
                <p>Текущий список просмотренных фотографий будет сброшен. Голоса, уже сохранённые в рейтинге, останутся.</p>
                <div class="modal-actions">
                    <button id="vote-cancel-cycle" class="text-button" type="button">Остаться в этом цикле</button>
                    <button id="vote-confirm-cycle" class="secondary-button" type="button">Начать новый цикл</button>
                </div>
            </section>
        </div>
    </section>
@endsection
