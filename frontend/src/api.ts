import type {
  CallsignContext,
  DxccData,
  LogEntryListResponse,
  LogEntryPayload,
  LogEntryResponse,
  ProfileListResponse,
} from './types';

type ApiErrorPayload = {
  error?: {
    code?: string;
    message?: string;
    details?: {
      fields?: Record<string, string[]>;
    };
  };
};

type RadioStateResponse = {
  freq?: number | string | null;
  mode?: string | null;
  lastseen?: number | string | null;
  last_seen?: number | string | null;
};

export type FrontendConfigResponse = {
  radioSyncDefaultUrl?: string | null;
  radioSyncDefaultPollIntervalSeconds?: number | null;
};

async function requestJson<T>(input: RequestInfo, init?: RequestInit): Promise<T> {
  const response = await fetch(input, {
    headers: {
      'Content-Type': 'application/json',
      ...(init?.headers ?? {}),
    },
    ...init,
  });

  if (!response.ok) {
    let payload: ApiErrorPayload | null = null;

    try {
      payload = (await response.json()) as ApiErrorPayload;
    } catch {
      payload = null;
    }

    const fieldMessages = payload?.error?.details?.fields
      ? Object.entries(payload.error.details.fields)
          .flatMap(([field, messages]) => messages.map((message) => `${field}: ${message}`))
          .join('\n')
      : '';

    throw new Error(
      fieldMessages || payload?.error?.message || `Request failed with status ${response.status}.`,
    );
  }

  if (response.status === 204) {
    return undefined as T;
  }

  const responseText = await response.text();

  if (responseText.trim() === '') {
    return undefined as T;
  }

  return JSON.parse(responseText) as T;
}

export function getCallsignContext(callsign: string, qsoDate?: string): Promise<CallsignContext> {
  const params = new URLSearchParams({ callsign });

  if (qsoDate) {
    params.set('qsoDate', qsoDate);
  }

  return requestJson<CallsignContext>(`/api/callsignContext?${params.toString()}`);
}

export function getDxcc(callsign: string): Promise<DxccData> {
  const params = new URLSearchParams({ callsign });

  return requestJson<DxccData>(`/api/dxcc?${params.toString()}`);
}

export function createLogEntry(payload: LogEntryPayload): Promise<LogEntryResponse> {
  return requestJson<LogEntryResponse>('/api/logEntries', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export function getLogEntry(id: number): Promise<LogEntryResponse> {
  return requestJson<LogEntryResponse>(`/api/logEntries/${id}`);
}

export function updateLogEntry(id: number, payload: Partial<LogEntryPayload>): Promise<LogEntryResponse> {
  return requestJson<LogEntryResponse>(`/api/logEntries/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  });
}

export function getLogEntries(page: number, perPage = 50): Promise<LogEntryListResponse> {
  const params = new URLSearchParams({
    page: page.toString(),
    perPage: perPage.toString(),
  });

  return requestJson<LogEntryListResponse>(`/api/logEntries?${params.toString()}`);
}

export function getProfiles(): Promise<ProfileListResponse> {
  return requestJson<ProfileListResponse>('/api/profiles');
}

export function getFrontendConfig(): Promise<FrontendConfigResponse> {
  return requestJson<FrontendConfigResponse>('/api/frontendConfig');
}

export function createProfile(payload: {
  number: number;
  locator?: string | null;
  qth?: string | null;
  rig?: string | null;
  remarks?: string | null;
  visible?: boolean;
}): Promise<{
  id: number;
  number: number;
  locator: string | null;
  qth: string | null;
  rig: string | null;
  remarks: string | null;
  visible: boolean;
}> {
  return requestJson('/api/profiles', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export function updateProfile(
  id: number,
  payload: Partial<{
    number: number;
    locator: string | null;
    qth: string | null;
    rig: string | null;
    remarks: string | null;
    visible: boolean;
  }>,
): Promise<{
  id: number;
  number: number;
  locator: string | null;
  qth: string | null;
  rig: string | null;
  remarks: string | null;
  visible: boolean;
}> {
  return requestJson(`/api/profiles/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  });
}

export function deleteProfile(id: number): Promise<void> {
  return requestJson(`/api/profiles/${id}`, {
    method: 'DELETE',
  });
}

export function createNote(
  callsign: string,
  remarks: string,
): Promise<{ id: number; callsign: string; remarks: string | null }> {
  return requestJson('/api/notes', {
    method: 'POST',
    body: JSON.stringify({
      callsign,
      remarks,
    }),
  });
}

export function updateNote(
  noteId: number,
  remarks: string | null,
): Promise<{ id: number; callsign: string | null; remarks: string | null }> {
  return requestJson(`/api/notes/${noteId}`, {
    method: 'PATCH',
    body: JSON.stringify({
      remarks,
    }),
  });
}

export async function getRadioState(url: string): Promise<RadioStateResponse> {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`Radio request failed with status ${response.status}.`);
  }

  const responseText = await response.text();

  if (responseText.trim() === '') {
    return {};
  }

  return JSON.parse(responseText) as RadioStateResponse;
}

export async function getDxClusterFeed(url: string): Promise<string> {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`DX Cluster request failed with status ${response.status}.`);
  }

  return response.text();
}

export async function getSolarDataFeed(url: string): Promise<string> {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`Solar data request failed with status ${response.status}.`);
  }

  return response.text();
}

export function getSolarData(): Promise<string> {
  return getSolarDataFeed('/api/solarData');
}
