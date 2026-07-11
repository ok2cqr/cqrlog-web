export type CallsignNote = {
  id: number;
  remarks: string | null;
};

export type ClubMembership = {
  slot: 1 | 2 | 3 | 4 | 5;
  name: string;
  number: string;
  fromDate: string | null;
  toDate: string | null;
};

export type RecentQso = {
  id: number;
  qsoDate: string;
  timeOn: string;
  timeOff: string | null;
  callsign: string;
  band: string | null;
  mode: string;
};

export type CallsignAutofill = {
  name: string | null;
  qth: string | null;
  award: string | null;
  qslVia: string | null;
  state: string | null;
  county: string | null;
  waz: number | null;
  itu: number | null;
  grid: string | null;
  iota: string | null;
};

export type CallsignContext = {
  callsign: string;
  idCall: string;
  note: CallsignNote | null;
  clubs: ClubMembership[];
  recentQsoCount: number;
  recentQsos: RecentQso[];
  autofill: CallsignAutofill;
};

export type DxccData = {
  callsign: string;
  name: string | null;
  details: string | null;
  continent: string | null;
  utc: string | null;
  waz: number | null;
  itu: number | null;
  lat: string | null;
  lng: string | null;
  adif: number | null;
  dxccRef: string | null;
};

export type Profile = {
  id: number;
  number: number;
  locator: string | null;
  qth: string | null;
  rig: string | null;
  remarks: string | null;
  visible: boolean;
};

export type LogEntryPayload = {
  qsoDate: string;
  timeOn: string;
  timeOff?: string | null;
  callsign: string;
  frequency: number;
  mode: string;
  rstSent?: string | null;
  rstReceived?: string | null;
  name?: string | null;
  qth?: string | null;
  grid?: string | null;
  state?: string | null;
  county?: string | null;
  award?: string | null;
  adif?: number | null;
  band?: string | null;
  remarks?: string | null;
  qslSent?: string | null;
  qslReceived?: string | null;
  qslVia?: string | null;
  iota?: string | null;
  power?: string | null;
  itu?: number | null;
  waz?: number | null;
  continent?: string | null;
  clubNumber1?: string | null;
  clubNumber2?: string | null;
  clubNumber3?: string | null;
  clubNumber4?: string | null;
  clubNumber5?: string | null;
  stx?: string | null;
  srx?: string | null;
  stxString?: string | null;
  srxString?: string | null;
  contestName?: string | null;
  profileId?: number | null;
};

export type LogEntryResponse = LogEntryPayload & {
  id: number;
  dxccRef?: string | null;
};

export type LogEntryListItem = LogEntryResponse & {
  qsoDate: string;
  timeOn: string;
  timeOff: string | null;
  frequency: number;
  callsign: string;
  mode: string;
  rstSent?: string | null;
  rstReceived?: string | null;
  name?: string | null;
  qth?: string | null;
  award?: string | null;
  band?: string | null;
};

export type LogEntryListResponse = {
  items: LogEntryListItem[];
  totalCount: number;
  page: number;
  perPage: number;
  totalPages: number;
  sortBy: string;
  sortDirection: string;
};

export type ProfileListResponse = {
  items: Profile[];
  totalCount: number;
};
