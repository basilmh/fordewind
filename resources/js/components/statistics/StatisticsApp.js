import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

import { ApiContractError } from '../../api/contract.js';
import { mapCarModelsResponse } from '../../api/models.js';
import { mapStatisticsResponse } from '../../api/statistics.js';
import text from '../../lang/ru/statistics.js';
import { selectedModel } from '../../shared/selectedModelStorage.js';

const currentYear = new Date().getFullYear();

export default {
    name: 'StatisticsApp',

    setup() {
        const models = ref([]);
        const result = ref({ cars: [], meta: null });
        const loading = ref(false);
        const loadingModels = ref(false);
        const error = ref('');
        const filters = reactive({ modelKey: '', yearFrom: '', yearTo: '' });
        let yearTimer;
        let initialized = false;
        let statisticsAbortController;

        const yearError = computed(() => {
            const from = Number(filters.yearFrom);
            const to = Number(filters.yearTo);

            if (filters.yearFrom && (from < 1900 || from > currentYear)) {
                return text.errors.yearFrom(currentYear);
            }

            if (filters.yearTo && (to < 1900 || to > currentYear)) {
                return text.errors.yearTo(currentYear);
            }

            if (filters.yearFrom && filters.yearTo && from > to) {
                return text.errors.invalidRange;
            }

            return '';
        });

        const hasResults = computed(() => result.value.cars.length > 0);
        const totalVotes = computed(() => result.value.meta?.totalVotes ?? 0);

        function errorMessage(response) {
            if (response instanceof ApiContractError) {
                return text.errors.fallback;
            }

            const firstField = Object.values(response?.errors ?? {})[0];

            return firstField?.[0] ?? response?.message ?? text.errors.fallback;
        }

        function query(page = 1) {
            const params = new URLSearchParams({ page: String(page), per_page: '12' });

            const selected = models.value.find((model) => model.key === filters.modelKey);

            if (selected) {
                params.set('make', selected.make);
                params.set('model', selected.model);
            }
            if (filters.yearFrom) params.set('year_from', filters.yearFrom);
            if (filters.yearTo) params.set('year_to', filters.yearTo);

            return params;
        }

        async function loadStatistics(page = 1) {
            statisticsAbortController?.abort();

            if (yearError.value) {
                result.value = { cars: [], meta: null };
                loading.value = false;

                return;
            }

            const controller = new AbortController();
            statisticsAbortController = controller;
            loading.value = true;
            error.value = '';

            try {
                const response = await fetch(`/api/statistics?${query(page)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw payload;
                }

                if (statisticsAbortController === controller) {
                    result.value = mapStatisticsResponse(payload);
                }
            } catch (response) {
                if (response?.name !== 'AbortError' && statisticsAbortController === controller) {
                    error.value = errorMessage(response);
                }
            } finally {
                if (statisticsAbortController === controller) {
                    loading.value = false;
                }
            }
        }

        async function loadModels() {
            loadingModels.value = true;

            try {
                const response = await fetch('/api/voting/models', { headers: { Accept: 'application/json' } });
                const payload = await response.json();

                if (!response.ok) {
                    throw payload;
                }

                models.value = mapCarModelsResponse(payload).models;
                const savedModel = selectedModel();

                if (savedModel && models.value.some((model) => model.key === savedModel)) {
                    filters.modelKey = savedModel;
                }
            } catch (response) {
                error.value = errorMessage(response);
            } finally {
                loadingModels.value = false;
            }
        }

        function resetFilters() {
            filters.modelKey = '';
            filters.yearFrom = '';
            filters.yearTo = '';
        }

        function formatMoney(value) {
            if (value === null) return text.card.noPrice;

            return new Intl.NumberFormat('en-US', { currency: 'USD', style: 'currency' }).format(Number(value));
        }

        watch(() => [filters.modelKey, filters.yearFrom, filters.yearTo], ([modelKey], [previousModelKey]) => {
            if (!initialized) {
                return;
            }

            clearTimeout(yearTimer);

            if (modelKey !== previousModelKey) {
                void loadStatistics();

                return;
            }

            yearTimer = window.setTimeout(() => void loadStatistics(), 350);
        });

        onMounted(async () => {
            await loadModels();
            await nextTick();
            initialized = true;
            await loadStatistics();
        });

        onBeforeUnmount(() => {
            clearTimeout(yearTimer);
            statisticsAbortController?.abort();
        });

        return {
            currentYear,
            filters,
            formatMoney,
            hasResults,
            loadStatistics,
            loading,
            loadingModels,
            models,
            resetFilters,
            result,
            text,
            totalVotes,
            yearError,
            error,
        };
    },
};
