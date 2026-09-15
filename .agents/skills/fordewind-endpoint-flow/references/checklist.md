# Fordewind Endpoint Checklist

## Files to consider

- Form Request: `app/Http/Requests/*Request.php`
- Data class: `app/Data/*Data.php`
- Action: `app/Actions/*Action.php`
- Controller: `app/Http/Controllers/*Controller.php`
- Route: `routes/web.php` or intentionally configured `routes/api.php`
- Tests: `tests/Feature/*Test.php` and, when valuable, `tests/Unit/*Test.php`

## Voting endpoint checks

1. The request uses session and CSRF middleware.
2. Model exists and both car IDs are distinct.
3. Both cars belong to the requested model and have usable photos.
4. The chosen car is one of the submitted pair.
5. The Action persists exactly one vote and does not accept forged winner/loser pairs.
6. The response has an explicit next-pair, exhausted-cycle, or unavailable-pair state.

## Statistics endpoint checks

1. Model filter is optional and constrained to known/valid values as appropriate.
2. `year_from` and `year_to` are integers and the range is valid.
3. Response exposes car data, received-vote count, pagination metadata when used, and total votes for the filtered set.
4. Vote aggregation happens in SQL and does not trigger N+1 queries.

## HTTP test coverage

- `200`/`201` success shape;
- `422` invalid payload and invalid year range;
- `404` only where a resource is intentionally addressed by URL;
- database effect for a vote;
- session-cycle or empty-source behaviour;
- no external network dependency.
