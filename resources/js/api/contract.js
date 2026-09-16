export class ApiContractError extends Error {}

export function array(value) {
    if (!Array.isArray(value)) {
        throw new ApiContractError();
    }

    return value;
}

export function boolean(value) {
    if (typeof value !== 'boolean') {
        throw new ApiContractError();
    }

    return value;
}

export function integer(value) {
    if (!Number.isInteger(value)) {
        throw new ApiContractError();
    }

    return value;
}

export function nullableRecord(value) {
    return value === null ? null : record(value);
}

export function nullableString(value) {
    return value === null ? null : string(value);
}

export function record(value) {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) {
        throw new ApiContractError();
    }

    return value;
}

export function string(value) {
    if (typeof value !== 'string') {
        throw new ApiContractError();
    }

    return value;
}
