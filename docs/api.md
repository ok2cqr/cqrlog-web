# API Documentation

This document describes the API that is currently implemented in this project.

Base URL for local development:

```text
http://localhost:4000
```

Implemented endpoints:

- `GET /api/health`
- `GET /api/callsignContext`
- `GET /api/dxcc`
- `GET /api/profiles`
- `GET /api/profiles/{id}`
- `POST /api/profiles`
- `PATCH /api/profiles/{id}`
- `GET /api/notes`
- `GET /api/notes/{id}`
- `POST /api/notes`
- `PATCH /api/notes/{id}`
- `GET /api/longNotes`
- `GET /api/longNotes/{id}`
- `POST /api/longNotes`
- `PATCH /api/longNotes/{id}`
- `GET /api/logEntries`
- `GET /api/logEntries/{id}`
- `POST /api/logEntries`
- `PATCH /api/logEntries/{id}`

## Common Behavior

- All responses are JSON.
- Public field names use `camelCase`.
- Every resource exposes a unified `id`.
- Unknown input fields are rejected with `422 Unprocessable Entity`.
- `PATCH` requires at least one field in the JSON body.
- Invalid JSON returns `400 Bad Request`.

Common error payload:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "Request validation failed.",
    "details": {
      "fields": {
        "callsign": [
          "This field must not be empty."
        ]
      }
    }
  }
}
```

Common error codes:

- `invalid_json`
- `validation_failed`
- `not_found`
- `database_error`
- `internal_error`

## Health

### `GET /api/health`

Response example:

```json
{
  "status": "ok",
  "environment": "dev"
}
```

## Callsign Context

### `GET /api/callsignContext`

Returns read-only helper data for a single callsign. This endpoint is intended for frontend autofill and lookup behavior around the QSO form.

Supported query parameters:

- `callsign`: required, exact callsign lookup, case-insensitive
- `qsoDate`: optional `Y-m-d` date used to filter club memberships by `fromdate` and `todate`

Response example:

```json
{
  "callsign": "OK1ABC",
  "idCall": "OK1ABC",
  "note": {
    "id": 12,
    "remarks": "Known portable station"
  },
  "clubs": [
    {
      "slot": 1,
      "name": "Czech DX Club",
      "number": "CDX-123",
      "fromDate": "2020-01-01",
      "toDate": null
    },
    {
      "slot": 2,
      "name": "European CW Club",
      "number": "ECWC-456",
      "fromDate": "2024-01-01",
      "toDate": "2026-12-31"
    }
  ],
  "recentQsoCount": 2,
  "recentQsos": [
    {
      "id": 321,
      "qsoDate": "2026-03-20",
      "timeOn": "12:30",
      "timeOff": "12:33",
      "callsign": "OK1ABC",
      "band": "40M",
      "mode": "CW"
    }
  ],
  "autofill": {
    "name": "Petr",
    "qth": "Brno",
    "award": "WAC",
    "qslVia": "Bureau",
    "state": "JM",
    "county": "Brno",
    "waz": 15,
    "itu": 28,
    "grid": "JN89"
  ]
}
```

Notes:

- `note` is the first exact-match record from `notes`, ordered by `id`
- `clubs` are resolved from `club1` through `club5`
- each club item contains the `slot` number, resolved club `name`, membership `number`, and optional validity dates
- club names are parsed from the INI content stored in `cqrlog_config.config_file`
- if a club name cannot be resolved from configuration, the API falls back to `Club 1` through `Club 5`
- `idCall` is the normalized base callsign used for club and recent-QSO matching
- `recentQsos` contains the most recent matches for the normalized `idCall`
- `autofill` contains the first non-empty values collected from recent QSOs, intended for frontend prefill

## DXCC

### `GET /api/dxcc`

Resolves DXCC metadata for a callsign through the HamQTH JSON endpoint.

Supported query parameters:

- `callsign`: required, exact callsign lookup

Response example:

```json
{
  "callsign": "XX9W",
  "name": "Macao",
  "details": "Macao",
  "continent": "AS",
  "utc": "-8",
  "waz": 24,
  "itu": 44,
  "lat": "22.2",
  "lng": "113.55",
  "adif": 152
}
```

Notes:

- the endpoint proxies [HamQTH DXCC JSON](https://www.hamqth.com/dxcc_json.php?callsign=XX9W)
- if the upstream service is unavailable, the API returns `502 Bad Gateway`

## Profiles

Public fields:

- `id`
- `number`
- `locator`
- `qth`
- `rig`
- `remarks`
- `visible`

Notes:

- `number` maps to legacy column `nr`.
- Empty strings are normalized to `null` in API responses.

### `GET /api/profiles`

Response example:

```json
{
  "items": [
    {
      "id": 1,
      "number": 2,
      "locator": "JN79",
      "qth": "Brno",
      "rig": "IC-7300",
      "remarks": "Club station",
      "visible": true
    }
  ],
  "totalCount": 1
}
```

### `POST /api/profiles`

Request example:

```json
{
  "number": 12,
  "locator": "JN89",
  "qth": "Ostrava",
  "rig": "TS-590",
  "remarks": "Contest setup",
  "visible": true
}
```

Validation:

- `number` is required and must be an integer greater than `0`
- `locator` max length `6`
- `qth`, `rig`, `remarks` max length `250`
- `visible` must be boolean

### `PATCH /api/profiles/{id}`

All fields are optional. Sending `null` for `locator`, `qth`, `rig` or `remarks` clears the stored value.

## Notes

Public fields:

- `id`
- `callsign`
- `remarks`

Notes:

- `remarks` maps to legacy column `longremarks`.
- `callsign` is uppercased before storing.
- Empty `remarks` are normalized to `null` in API responses.

### `GET /api/notes`

Response example:

```json
{
  "items": [
    {
      "id": 1,
      "callsign": "OK1ABC",
      "remarks": "Worked on 20m"
    }
  ],
  "totalCount": 1
}
```

### `POST /api/notes`

Request example:

```json
{
  "callsign": "ok1abc",
  "remarks": "Worked on 20m"
}
```

Validation:

- `callsign` is required, must be non-empty, max length `20`
- `remarks` is optional, max length `256`

### `PATCH /api/notes/{id}`

All fields are optional. Sending `null` for `remarks` clears the stored value.

## Long Notes

Public fields:

- `id`
- `note`

### `GET /api/longNotes`

Response example:

```json
{
  "items": [
    {
      "id": 1,
      "note": "Long station-specific note"
    }
  ],
  "totalCount": 1
}
```

### `POST /api/longNotes`

Request example:

```json
{
  "note": "Long station-specific note"
}
```

Validation:

- `note` is required on `POST`
- on `POST`, `note` must not be empty after trimming
- on `PATCH`, `note` may be `null`

## Log Entries

The `logEntries` API now exposes nearly all fields from `cqrlog_main`, but under a public camelCase contract instead of raw legacy column names.

Public fields:

- `id`
- `qsoDate`
- `timeOn`
- `timeOff`
- `callsign`
- `frequency`
- `mode`
- `rstSent`
- `rstReceived`
- `name`
- `qth`
- `grid`
- `state`
- `county`
- `award`
- `adif`
- `band`
- `remarks`
- `qslSent`
- `qslReceived`
- `qslVia`
- `iota`
- `power`
- `itu`
- `waz`
- `idCall`
- `lotwSentDate`
- `lotwReceivedDate`
- `lotwSent`
- `lotwReceived`
- `continent`
- `qslSentDate`
- `qslReceivedDate`
- `clubNumber1`
- `clubNumber2`
- `clubNumber3`
- `clubNumber4`
- `clubNumber5`
- `eqslSent`
- `eqslSentDate`
- `eqslReceived`
- `eqslReceivedDate`
- `receiveFrequency`
- `satellite`
- `propagationMode`
- `stx`
- `srx`
- `stxString`
- `srxString`
- `contestName`
- `dok`
- `operator`
- `myLocator`
- `qsoDxcc`
- `profileId`

Notes:

- `frequency` maps to legacy `freq`
- `grid` maps to legacy `loc`
- `power` maps to legacy `pwr`
- `myLocator` maps to `my_loc`
- `qsoDxcc` maps to `qso_dxcc`
- `profileId` maps to `profile`
- `idCall` maps to `idcall`
- `continent` maps to `cont`
- `receiveFrequency` maps to `rxfreq`
- `propagationMode` maps to `prop_mode`
- `contestName` maps to `contestname`
- stored zero values for `qsoDxcc` and `profileId` are returned as `null`
- stored zero values for `adif`, `itu` and `waz` are returned as `null`
- `callsign`, `grid`, `state`, `iota`, `idCall`, `lotwSent`, `lotwReceived`, `continent`, `eqslSent`, `eqslReceived`, `dok`, `operator` and `myLocator` are uppercased before storing

### `GET /api/logEntries`

Supported query parameters:

- `page`: default `1`
- `perPage`: default `50`, max `100`
- `callsign`: case-insensitive partial match
- `qsoDateFrom`: inclusive lower bound in `Y-m-d`
- `qsoDateTo`: inclusive upper bound in `Y-m-d`
- `sortBy`: one of `qsoDate`, `callsign`, `frequency`, `mode`, `id`, default `qsoDate`
- `sortDirection`: `asc` or `desc`, default `desc`

Response example:

```json
{
  "items": [
    {
      "id": 10,
      "qsoDate": "2026-03-16",
      "timeOn": "11:30",
      "timeOff": null,
      "callsign": "OK1NEW",
      "frequency": 14.074,
      "mode": "FT8",
      "rstSent": null,
      "rstReceived": null,
      "name": null,
      "qth": null,
      "grid": "JN79",
      "state": "CA",
      "county": "Santa Clara",
      "award": null,
      "adif": null,
      "band": null,
      "remarks": "Created via API",
      "qslSent": null,
      "qslReceived": null,
      "qslVia": null,
      "iota": "EU-001",
      "power": "100W",
      "itu": 28,
      "waz": 15,
      "idCall": "OK1NEW/P",
      "lotwSentDate": "2026-03-17",
      "lotwReceivedDate": "2026-03-18",
      "lotwSent": "Y",
      "lotwReceived": "R",
      "continent": "EU",
      "qslSentDate": "2026-03-19",
      "qslReceivedDate": "2026-03-20",
      "clubNumber1": "100",
      "clubNumber2": "200",
      "clubNumber3": "300",
      "clubNumber4": "400",
      "clubNumber5": "500",
      "eqslSent": "Y",
      "eqslSentDate": "2026-03-21",
      "eqslReceived": "N",
      "eqslReceivedDate": "2026-03-22",
      "receiveFrequency": 14.075,
      "satellite": "QO-100",
      "propagationMode": "SAT",
      "stx": "001",
      "srx": "002",
      "stxString": "STX-001",
      "srxString": "SRX-002",
      "contestName": "CQ WW",
      "dok": "D01",
      "operator": "OK1OP",
      "myLocator": null,
      "qsoDxcc": null,
      "profileId": 3
    }
  ],
  "totalCount": 1,
  "page": 1,
  "perPage": 50,
  "totalPages": 1,
  "sortBy": "qsoDate",
  "sortDirection": "desc"
}
```

### `POST /api/logEntries`

Request example:

```json
{
  "qsoDate": "2026-03-16",
  "timeOn": "11:30",
  "callsign": "ok1new",
  "frequency": 14.074,
  "mode": "FT8",
  "grid": "jn79",
  "state": "ca",
  "county": "Santa Clara",
  "award": "WAS",
  "adif": 291,
  "remarks": "Created via API",
  "iota": "eu-001",
  "power": "100W",
  "itu": 28,
  "waz": 15,
  "idCall": "ok1new/p",
  "lotwSentDate": "2026-03-17",
  "lotwReceivedDate": "2026-03-18",
  "lotwSent": "y",
  "lotwReceived": "r",
  "continent": "eu",
  "qslSentDate": "2026-03-19",
  "qslReceivedDate": "2026-03-20",
  "clubNumber1": "100",
  "clubNumber2": "200",
  "clubNumber3": "300",
  "clubNumber4": "400",
  "clubNumber5": "500",
  "eqslSent": "y",
  "eqslSentDate": "2026-03-21",
  "eqslReceived": "n",
  "eqslReceivedDate": "2026-03-22",
  "receiveFrequency": 14.075,
  "satellite": "QO-100",
  "propagationMode": "SAT",
  "stx": "001",
  "srx": "002",
  "stxString": "STX-001",
  "srxString": "SRX-002",
  "contestName": "CQ WW",
  "dok": "d01",
  "operator": "ok1op",
  "profileId": 3
}
```

Validation:

- required fields: `qsoDate`, `timeOn`, `callsign`, `frequency`, `mode`
- `qsoDate` must use `Y-m-d`
- `timeOn` and `timeOff` must use `HH:MM`
- `frequency` must be a positive number
- `callsign` max length `20`
- `mode` max length `12`
- `rstSent`, `rstReceived` max length `20`
- `name` max length `40`
- `qth` max length `60`
- `grid` max length `10`
- `state` max length `4`
- `county` max length `30`
- `award` max length `50`
- `adif` must be an integer greater than or equal to `0`
- `band` max length `6`
- `remarks` max length `200`
- `qslSent` max length `4`
- `qslReceived` max length `3`
- `qslVia` max length `30`
- `iota` max length `6`
- `power` max length `10`
- `itu` and `waz` must be integers greater than or equal to `0`
- `idCall` max length `20`
- `lotwSentDate`, `lotwReceivedDate`, `eqslSentDate` and `eqslReceivedDate` must use `Y-m-d`
- `lotwSent` and `lotwReceived` max length `3`
- `continent` max length `3`
- `qslSentDate` and `qslReceivedDate` max length `10`
- `clubNumber1` to `clubNumber5` max length `100`
- `eqslSent` and `eqslReceived` max length `1`
- `receiveFrequency` must be a positive number
- `satellite` and `propagationMode` max length `30`
- `stx` and `srx` max length `6`
- `stxString` and `srxString` max length `50`
- `contestName` max length `40`
- `dok` max length `12`
- `operator` max length `20`
- `myLocator` max length `10`
- `qsoDxcc` and `profileId` must be integers greater than or equal to `0`

### `PATCH /api/logEntries/{id}`

All fields are optional. Sending `null` clears nullable string fields such as `timeOff`, `remarks`, `qslVia` or `myLocator`.

## OpenAPI

Machine-readable schema:

- [openapi.yaml](/Users/petr/Projects/private/con/docs/openapi.yaml)
