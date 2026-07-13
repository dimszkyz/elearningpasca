# local_pascasync

Local Moodle plugin that imports and updates Moodle accounts from the protected Pasca Laravel API.

## Endpoint

`GET /api/integrations/moodle/users`

The API must use a Sanctum bearer token with the `moodle-users:read` ability.

## Docker URLs

- Laravel on the host at port 8001: `http://host.docker.internal:8001/api/integrations/moodle/users`
- Laravel and Moodle on the same Docker network: `http://pasca:8000/api/integrations/moodle/users`

Enable **Allow private/internal API host** only for a trusted internal network.

## Behaviour

- One-way sync: Pasca to Moodle.
- Matches users by plugin mapping, `idnumber=pasca:{source_id}`, then unique email.
- Generates a Moodle username from the Pasca name.
- Enables Moodle email login when configured.
- Imports Laravel bcrypt hashes without hashing them again.
- Stores only a SHA-256 fingerprint of the source hash to avoid resetting Moodle passwords on every full sync.
- Does not delete users, assign roles, or enrol users into courses.
