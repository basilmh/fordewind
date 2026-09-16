const selectedModelStorageKey = 'fordewind.selected-model';

export function selectedModel() {
    try {
        return window.localStorage.getItem(selectedModelStorageKey);
    } catch {
        return null;
    }
}

export function storeSelectedModel(model) {
    try {
        if (model) {
            window.localStorage.setItem(selectedModelStorageKey, model);

            return;
        }

        window.localStorage.removeItem(selectedModelStorageKey);
    } catch {
        // The application remains usable when browser storage is unavailable.
    }
}
