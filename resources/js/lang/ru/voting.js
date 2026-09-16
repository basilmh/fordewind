export default {
    car: {
        alt: ({ make, model, year }) => `${make} ${model}, ${year}. Нажмите, чтобы увеличить фотографию.`,
        auction: ({ auctionItemId, year }) => `${year} год · Лот ${auctionItemId}`,
        title: ({ make, model }) => `${make} ${model}`,
    },
    empty: {
        exhausted: {
            copy: 'Все фотографии этой модели уже были показаны в текущей session.',
            title: 'Цикл завершён',
        },
        noCars: {
            copy: 'Для этой модели пока доступно меньше двух автомобилей.',
            title: 'Недостаточно фотографий',
        },
        noModel: {
            copy: 'После выбора модели появится первая пара фотографий.',
            title: 'Выберите модель',
        },
        requestFailed: {
            copy: 'Не удалось загрузить фотографии.',
            title: 'Пара недоступна',
        },
    },
    errors: {
        fallback: 'Не удалось выполнить запрос. Повторите попытку.',
    },
    models: {
        emptyHint: 'Автомобили ещё не импортированы.',
        hint: 'Модели отсортированы по алфавиту.',
        option: (model, count) => `${model} · ${count} авто`,
        placeholder: 'Выберите модель',
        unavailableOption: (model, count) => `${model} · ${count} авто · без голосования`,
    },
    notices: {
        cycleStarted: 'Новый цикл начат.',
        loadingModels: 'Загружаем модели...',
        loadingPair: 'Подбираем следующую пару...',
        restartingCycle: 'Создаём новую session...',
        savingVote: 'Сохраняем выбор...',
        unavailablePair: 'Для голосования нужны как минимум две фотографии этой модели.',
        voteSaved: 'Голос учтён. Следующая пара готова.',
    },
};
