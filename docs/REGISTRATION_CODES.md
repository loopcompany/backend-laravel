# Registration codes

Source: `1/فرمول کد گزاری.pdf` (one page). The customer confirmed that legacy accounts must keep their existing codes; only new registrations use the formula automatically.

Format: `CC-RR-YY-TT-M-S.SSS.SSS`, for example `21-08-05-60-3-0.000.001`.

- CC: two-digit registration city prefix, resolved through the selected region/city and its stored geographic dialing code; a leading zero in `021` becomes `21`.
- RR: the region's `code`, not its database ID, padded to two digits.
- YY: last two digits of the Jalali year at account creation in Asia/Tehran.
- TT: individual 60/61, company 70/71, organization 80/81 (normal/special). Staff retain the existing explicit administrative issuance path: manager 10, administrative/field staff 30/31, administrative/field technician 40/41.
- M: native app 1, desktop/Electron 2, website (including mobile browsers) 3, in-person 4, telephone 5. Admin creation has an explicit initial-registration-method selector.
- Serial: one transactional sequence shared with explicit staff-code issuance, from 1 through 9,999,999, displayed as seven digits with dots. Issuing the same record twice preserves the code and consumes no extra number.

The new `users.registration_method` field is nullable and is deliberately NOT backfilled. API services record it for new accounts, and admin creation defaults to in-person with an explicit selector. Native platform metadata and existing user-agent signatures distinguish native apps, Electron and browsers. User-supplied public payloads cannot set administrative methods 4/5 through the platform field.

UserObserver issues a new account's code once actual location data are available. If location is incomplete it leaves the code empty, retaining the initial method until location completion. It never invents city/region 00 and never renumbers legacy accounts during profile edits or re-registration. Assigned codes remain stable after subsequent address edits. Special accounts are issued through administrative creation/issuance, as specified in the PDF.

Old ID-offset generators are removed from API registration, organizational registration, profile completion and admin creation. The repository compatibility entry point delegates to the canonical sequence service. Referral codes, legal registration numbers, credentials and existing account codes are separate and are not bulk changed.

Focused verification covers all six customer categories, registration methods, Jalali year, region-code vs ID, idempotence, capacity, incomplete locations, legacy preservation, the real registration HTTP route and Filament creation. Production verification uses a transaction that is rolled back, including its sequence increment.
