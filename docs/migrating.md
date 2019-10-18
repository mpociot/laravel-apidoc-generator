# Migrating
- Version requirement: PHP 7.2 and Laravel 5.7

- Rename your old config file. Publish the new config file and copy over any changes you made to the old one.

- Rename your old vendor view (if published). Publish the new vendor view and copy over any changes you made to the old one.

- Migrate `bindings` to `@urlParam`

- Remove `response_calls.bindings`

- Rename `query` and `body` in `response_calls` config to `queryParams` and `bodyParams`

- Move any `apply.response_calls.headers` to `apply.headers`

- Move any prepend/append files to new location (resources/docs/source)

- Verify that any custom strategies match the new signatures

- Modify any custom language views to match new parameters (`metadata`)

- See changelog for details
