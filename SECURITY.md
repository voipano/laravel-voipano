# Security

Never include real bearer tokens, webhook secrets, phone numbers, or customer recordings in Git issues or logs.

The default inbound webhook verifier rejects all traffic because the signing contract is not documented in the supplied collection. Do not enable a custom verifier without first validating algorithm, signed payload bytes, constant-time comparison, timestamp window, and replay handling against the backend.

Use `VOIPANO_API_TOKEN` from server environment or a secrets manager. Do not commit `.env`. When serving multiple tenants, use `withToken()` and `forService()` per request; avoid storing tenants' credentials in singleton config.

For security issues, request a private vulnerability report through the repository maintainer after the official repository is created. Do not post exploit details publicly.
