import { ApiContractError, integer, nullableRecord, nullableString, record, string } from './contract.js';

const pairStatuses = new Set(['ready', 'unavailable', 'exhausted']);

/**
 * @param {unknown} payload
 * @returns {{ csrfToken: string }}
 */
export function mapVotingCycleResponse(payload) {
    const response = record(payload);
    const data = record(response.data);

    return { csrfToken: string(data.csrf_token) };
}

/**
 * @param {unknown} payload
 * @returns {{ pair: VotingPair }}
 */
export function mapVotingPairResponse(payload) {
    return { pair: mapPair(record(payload).data) };
}

/**
 * @param {unknown} payload
 * @returns {{ nextPair: VotingPair, vote: { loserCarId: number, winnerCarId: number } }}
 */
export function mapVoteResponse(payload) {
    const response = record(payload);
    const vote = record(response.data);

    return {
        nextPair: mapPair(response.next_pair),
        vote: {
            loserCarId: integer(vote.loser_car_id),
            winnerCarId: integer(vote.winner_car_id),
        },
    };
}

function mapCar(value) {
    const car = record(value);

    return {
        auctionItemId: string(car.auction_item_id),
        id: integer(car.id),
        imageUrl: string(car.image_url),
        make: string(car.make),
        model: string(car.model),
        year: integer(car.year),
    };
}

function mapPair(value) {
    const pair = record(value);
    const status = string(pair.status);

    if (!pairStatuses.has(status)) {
        throw new ApiContractError();
    }

    return {
        leftCar: mapNullableCar(pair.left_car),
        make: string(pair.make),
        model: string(pair.model),
        pairToken: nullableString(pair.pair_token),
        rightCar: mapNullableCar(pair.right_car),
        status,
    };
}

function mapNullableCar(value) {
    const car = nullableRecord(value);

    return car === null ? null : mapCar(car);
}
