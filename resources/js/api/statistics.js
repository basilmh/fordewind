import { array, integer, nullableString, record, string } from './contract.js';

/**
 * @param {unknown} payload
 * @returns {{ cars: Array<{
 *     auctionItemId: string,
 *     color: string,
 *     engine: string,
 *     id: number,
 *     imageUrl: string,
 *     make: string,
 *     model: string,
 *     odometer: number,
 *     transmission: string,
 *     units: string,
 *     votesReceived: number,
 *     winningBidAmount: string|null,
 *     year: number,
 * }>, meta: { currentPage: number, lastPage: number, perPage: number, totalCars: number, totalVotes: number } }}
 */
export function mapStatisticsResponse(payload) {
    const response = record(payload);
    const meta = record(response.meta);

    return {
        cars: array(response.data).map((car) => mapCar(record(car))),
        meta: {
            currentPage: integer(meta.current_page),
            lastPage: integer(meta.last_page),
            perPage: integer(meta.per_page),
            totalCars: integer(meta.total_cars),
            totalVotes: integer(meta.total_votes),
        },
    };
}

function mapCar(car) {
    return {
        auctionItemId: string(car.auction_item_id),
        color: string(car.color),
        engine: string(car.engine),
        id: integer(car.id),
        imageUrl: string(car.image_url),
        make: string(car.make),
        model: string(car.model),
        odometer: integer(car.odometer),
        transmission: string(car.transmission),
        units: string(car.units),
        votesReceived: integer(car.votes_received),
        winningBidAmount: nullableString(car.winning_bid_amount),
        year: integer(car.year),
    };
}
