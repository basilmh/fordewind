@extends('layouts.app')

@section('title', 'Статистика')

@push('scripts')
    @vite(['resources/js/statistics.js'])
@endpush

@section('content')
    <section id="statistics-app" class="statistics-page" aria-labelledby="statistics-title">
        <div class="page-intro">
            <p class="eyebrow">Живой рейтинг</p>
            <h1 id="statistics-title">Какие автомобили получают больше голосов?</h1>
            <p>Фильтруйте результаты по модели и году выпуска. Данные обновляются без перезагрузки страницы.</p>
        </div>
        <noscript><p class="status-message is-error">Для просмотра статистики включите JavaScript.</p></noscript>
    </section>
@endsection
