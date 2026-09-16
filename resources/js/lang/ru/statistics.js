export default {
    card: {
        color: 'Цвет',
        engine: 'Двигатель',
        imageAlt: ({ make, model, year }) => `${make} ${model}, ${year}`,
        mileage: 'Пробег',
        noPrice: 'Цена не указана',
        transmission: 'КПП',
        votes: 'голосов',
    },
    empty: {
        copy: 'Измените фильтры или импортируйте исходные данные.',
        title: 'Ничего не найдено',
    },
    errors: {
        fallback: 'Не удалось загрузить статистику.',
        invalidRange: 'Год «от» не может быть больше года «до».',
        yearFrom: (year) => `Год «от» должен быть между 1900 и ${year}.`,
        yearTo: (year) => `Год «до» должен быть между 1900 и ${year}.`,
    },
    filters: {
        allModels: 'Все модели',
        ariaLabel: 'Фильтры статистики',
        model: 'Модель',
        modelOption: (model, count) => `${model} · ${count} авто`,
        reset: 'Сбросить',
        yearFrom: 'Год от',
        yearFromPlaceholder: '1900',
        yearTo: 'Год до',
    },
    loader: 'Обновляем рейтинг...',
    pagination: {
        ariaLabel: 'Страницы статистики',
        next: 'Далее',
        page: (currentPage, lastPage) => `Страница ${currentPage} из ${lastPage}`,
        previous: 'Назад',
    },
    summary: {
        cars: (count) => `${count} автомобилей в выборке`,
        votes: 'Получено голосов',
    },
};
