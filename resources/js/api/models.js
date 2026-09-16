import { array, boolean, integer, record, string } from './contract.js';

/**
 * @param {unknown} payload
 * @returns {{ models: Array<{ name: string, carsCount: number, isVotable: boolean }> }}
 */
export function mapCarModelsResponse(payload) {
    const response = record(payload);

    return {
        models: array(response.data).map((model) => {
            const value = record(model);

            return {
                carsCount: integer(value.cars_count),
                isVotable: boolean(value.is_votable),
                name: string(value.model),
            };
        }),
    };
}
