# QSO Delete Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Delete a logged QSO from the web UI via a Delete button in the Edit QSO dialog, backed by `DELETE /api/logEntries/{id}`.

**Architecture:** Mirror the existing profiles DELETE (only resource with delete today): gateway method + controller action returning 204/404. Frontend adds an api client call and a confirmed Delete button in the edit dialog; on success the dialog closes, the list reloads and shows a feedback strip. No `log_changes` bookkeeping (web create/update already skip it).

**Tech Stack:** PHP 8.2/Symfony 7.4 + Dibi (backend), React 18 + TS (frontend), PHPUnit in Docker (`make test`).

## Global Constraints

- Tests run ONLY inside Docker: `make test` (never host PHPUnit).
- No legacy column names in API responses; error shape `{ "error": { "code", "message", ... } }`; not-found code is `not_found`.
- Spec: `docs/superpowers/specs/2026-07-12-qso-delete-design.md`.

---

### Task 1: Backend `DELETE /api/logEntries/{id}`

**Files:**
- Modify: `src/Api/LogEntry/LogEntryGateway.php` (add `delete()` after `update()`)
- Modify: `src/Controller/Api/LogEntryController.php` (add `delete` action after `update`, ~line 87)
- Test: `tests/Api/LogEntryControllerTest.php`
- Modify: `docs/api.md`, `docs/openapi.yaml`

**Interfaces:**
- Consumes: `LogEntryGateway::fetchById(int $id): ?Row` (existing), `ResourceNotFoundException` (existing).
- Produces: `DELETE /api/logEntries/{id}` → `204` empty body, or `404` `not_found` — Task 2's `deleteLogEntry()` calls this.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Api/LogEntryControllerTest.php`, after `listRejectsTooLongContestName`:

```php
    #[Test]
    public function deleteRemovesLogEntry(): void
    {
        $id = $this->insertLogEntry([
            'qsodate' => '2026-03-16',
            'time_on' => '10:00',
            'callsign' => 'OK1DEL',
            'freq' => 7.0740,
            'mode' => 'CW',
        ]);

        $this->client->request('DELETE', sprintf('/api/logEntries/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertSame('', $this->client->getResponse()->getContent());

        $row = $this->connection->fetch(
            'SELECT id_cqrlog_main FROM cqrlog_main WHERE id_cqrlog_main = %i',
            $id,
        );

        self::assertNull($row);
    }

    #[Test]
    public function deleteReturnsNotFoundForUnknownLogEntry(): void
    {
        $this->client->request('DELETE', '/api/logEntries/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $payload['error']['code']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `make test-db-reset && docker compose -f docker-compose.yml up -d app && docker compose -f docker-compose.yml exec -T app vendor/bin/phpunit --filter 'deleteRemovesLogEntry|deleteReturnsNotFoundForUnknownLogEntry'`
Expected: FAIL — DELETE route does not exist yet, so the response is 405/404 where 204 is asserted (`deleteRemovesLogEntry` fails; the unknown-id test may already pass via routing 404 — that is fine).

- [ ] **Step 3: Implement gateway delete**

Add to `src/Api/LogEntry/LogEntryGateway.php`, directly after the `update()` method:

```php
    public function delete(int $id): void
    {
        $this->connection
            ->delete('cqrlog_main')
            ->where('id_cqrlog_main = %i', $id)
            ->execute();
    }
```

- [ ] **Step 4: Implement controller action**

Add to `src/Controller/Api/LogEntryController.php`, directly after the `update()` method (mirrors `ProfileController::delete`):

```php
    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if ($this->gateway->fetchById($id) === null) {
            throw new ResourceNotFoundException('Log entry', $id);
        }

        $this->gateway->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
```

(`ResourceNotFoundException` and `Response` are already imported in this file.)

- [ ] **Step 5: Run the two tests, then the full suite**

Run: `docker compose -f docker-compose.yml exec -T app vendor/bin/phpunit --filter 'deleteRemovesLogEntry|deleteReturnsNotFoundForUnknownLogEntry'`
Expected: `OK (2 tests, …)`
Run: `make test`
Expected: full suite green (57 tests).

- [ ] **Step 6: Document the endpoint**

In `docs/api.md`, in the log entries section (after the `PATCH /api/logEntries/{id}` section, ~line 597), add:

```markdown
### `DELETE /api/logEntries/{id}`

Deletes the log entry. Returns `204 No Content` on success, `404` with the
standard error shape when the entry does not exist.
```

Also add `DELETE /api/logEntries/{id}` to the endpoint list at the top (~line 31).

In `docs/openapi.yaml`, add to the `/api/logEntries/{id}` path item (alongside its `get`/`patch`, mirror the `delete` under `/api/profiles/{id}`):

```yaml
    delete:
      tags: [LogEntries]
      summary: Delete log entry
      operationId: deleteLogEntry
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '204':
          description: Log entry deleted
        '404':
          description: Log entry not found
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Error'
```

(Check the profiles delete entry first and copy its exact parameter/response style, including the error schema `$ref` name used there.)

- [ ] **Step 7: Commit**

```bash
git add src/Api/LogEntry/LogEntryGateway.php src/Controller/Api/LogEntryController.php tests/Api/LogEntryControllerTest.php docs/api.md docs/openapi.yaml
git commit -m "feat: DELETE /api/logEntries/{id}"
```

---

### Task 2: Delete button in the Edit QSO dialog

**Files:**
- Modify: `frontend/src/api.ts` (after `updateLogEntry`, ~line 139)
- Modify: `frontend/src/App.tsx` (handler after `handleEditSubmit`; button in the edit dialog `dialog__actions`, ~line 4016)
- Modify: `frontend/src/styles.css` (danger button + actions group)

**Interfaces:**
- Consumes: `DELETE /api/logEntries/{id}` from Task 1; existing `requestJson`, `closeEditDialog()`, `setQsoListFeedback`, `setQsoListReloadKey`, `editDialog` state.
- Produces: `deleteLogEntry(id: number): Promise<void>` in `api.ts`.

- [ ] **Step 1: Add api client function**

In `frontend/src/api.ts`, after `updateLogEntry` (mirrors `deleteProfile`):

```ts
export function deleteLogEntry(id: number): Promise<void> {
  return requestJson(`/api/logEntries/${id}`, {
    method: 'DELETE',
  });
}
```

Add `deleteLogEntry` to the import list from `./api` in `frontend/src/App.tsx` (alphabetical, after `createProfile`/`createLogEntry`/`createNote`, next to `deleteProfile`).

- [ ] **Step 2: Add the delete handler**

In `frontend/src/App.tsx`, directly after `handleEditSubmit`:

```ts
  async function handleDeleteQso(): Promise<void> {
    if (editDialog.entryId === null) {
      return;
    }

    const entryId = editDialog.entryId;
    const shouldDelete = window.confirm(`Delete QSO #${entryId} (${editDialog.originalCallsign})?`);

    if (!shouldDelete) {
      return;
    }

    setEditDialog((current) => ({
      ...current,
      status: 'saving',
      message: 'Deleting QSO…',
    }));

    try {
      await deleteLogEntry(entryId);
      closeEditDialog();
      setQsoListFeedback({
        status: 'saved',
        message: `Deleted QSO #${entryId}.`,
      });
      setQsoListReloadKey((current) => current + 1);
    } catch (error) {
      setEditDialog((current) => ({
        ...current,
        status: 'ready',
        message: error instanceof Error ? error.message : 'Unable to delete QSO.',
      }));
    }
  }
```

(While `status === 'ready'` the dialog renders `editDialog.message` with the error styling — failure stays visible in the dialog, matching save failures.)

- [ ] **Step 3: Add the Delete button to the dialog actions**

In `frontend/src/App.tsx`, replace the edit dialog's actions block (~line 4016):

```tsx
                      <div className="dialog__actions">
                        <button
                          className="button button--secondary"
                          type="button"
                          onClick={closeEditDialog}
                          disabled={editDialog.status === 'saving'}
                        >
                          Cancel
                        </button>
                        <button className="button button--primary" type="submit" disabled={editDialog.status === 'saving'}>
                          {editDialog.status === 'saving' ? 'Saving…' : 'Save changes'}
                        </button>
                      </div>
```

with:

```tsx
                      <div className="dialog__actions">
                        <button
                          className="button button--danger"
                          type="button"
                          onClick={() => void handleDeleteQso()}
                          disabled={editDialog.status === 'saving'}
                        >
                          Delete
                        </button>
                        <div className="dialog__actions-group">
                          <button
                            className="button button--secondary"
                            type="button"
                            onClick={closeEditDialog}
                            disabled={editDialog.status === 'saving'}
                          >
                            Cancel
                          </button>
                          <button className="button button--primary" type="submit" disabled={editDialog.status === 'saving'}>
                            {editDialog.status === 'saving' ? 'Saving…' : 'Save changes'}
                          </button>
                        </div>
                      </div>
```

(`.dialog__actions` is already `justify-content: space-between`, so Delete sits left, Cancel/Save right.)

- [ ] **Step 4: Add CSS**

In `frontend/src/styles.css`, after the `.dialog__actions` block (~line 944):

```css
.dialog__actions-group {
  display: flex;
  gap: 0.75rem;
}

.button--danger {
  background: transparent;
  border: 1px solid rgba(192, 57, 43, 0.5);
  color: #c0392b;
}
```

And a dark-mode override in the dark-theme section (near the other `:root[data-theme='dark']` button rules):

```css
:root[data-theme='dark'] .button--danger {
  border-color: rgba(231, 112, 99, 0.55);
  color: #e77063;
}
```

Before writing, check how `.button--secondary` is defined (base `.button` class provides padding/radius) and keep `.button--danger` consistent with that structure; check the dark section for where secondary buttons are overridden and place the danger override next to them.

- [ ] **Step 5: Build**

Run: `cd frontend && npm run build`
Expected: `tsc -b && vite build` completes without errors.

- [ ] **Step 6: Manual verification (dev servers)**

Backend runs via `make dev` (port 4000); frontend `cd frontend && npm run dev` (port 5174 — 5173 is taken by an unrelated container). Login from `.env.local`: `ok2cqr` / `ok2cqr`.

1. QSO list → Edit on any test entry (create one first via the entry form if needed) → Delete → confirm → dialog closes, strip shows "Deleted QSO #…", entry gone from the list.
2. Edit → Delete → Cancel in the confirm → nothing happens, dialog stays.
3. Verify layout at iPad Mini portrait 744×1133 (Chrome device emulation) — buttons must not overflow the dialog.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/api.ts frontend/src/App.tsx frontend/src/styles.css
git commit -m "feat: delete QSO from the edit dialog"
```
