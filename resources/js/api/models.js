import { array, boolean, integer, record, string } from './contract.js';

/**
 * @param {unknown} payload
 * @returns {{ models: Array<{ key: string, make: string, model: string, name: string, carsCount: number, isVotable: boolean }> }}
 */
export function mapCarModelsResponse(payload) {
    const response = record(payload);

    return {
        models: array(response.data).map((model) => {
            const value = record(model);

            const make = string(value.make);
            const modelName = string(value.model);

            return {
                carsCount: integer(value.cars_count),
                isVotable: boolean(value.is_votable),
                key: JSON.stringify([make, modelName]),
                make,
                model: modelName,
                name: `${make} ${modelName}`,
            };
        }),
    };
}
