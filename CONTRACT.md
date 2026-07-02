# Orbit Serialize → React JSON Contract

This document is the single source of truth for the data shape that the PHP
serialization layer (`src/Screen/*`) emits and that the React renderer
(`resources/js/*`) consumes. Both sides MUST match this contract. The
TypeScript mirror lives in `resources/js/contract.ts`.

## Screen page props (`Inertia::render('orbit/screen', …)`)

```ts
interface ScreenProps {
  name: string | null;            // header title
  description: string | null;     // header subtitle
  permission: string[] | null;    // permissions required to view
  breadcrumbs: Breadcrumb[];       // [{ label, url }]
  commandBar: FieldNode[];         // serialized Action nodes (Button/Link/…)
  layout: LayoutNode[];            // root layout tree (manyForms collapsed)
  data: Record<string, unknown>;   // repository (query() result) values
  state: string | null;            // encrypted screen-state token (opaque)
  screenComponent: string | null;  // custom full-screen React component name
}

interface Breadcrumb {
  label: string;
  url: string | null;
}
```

## LayoutNode (recursive tree)

Produced by `Layout::toArray(Repository): ?array`. A `null` node (not visible)
is filtered out by the parent.

```ts
interface LayoutNode {
  type: string;                    // kebab layout type: 'rows','table','columns','tabs','modal','legend','selection',… or a custom component name
  key: string;                     // stable slug (Layout::getSlug)
  canSee: boolean;                 // always true when present
  data: Record<string, unknown>;  // layout-specific payload (see below)
  children: LayoutNode[];          // nested layouts
}
```

### Layout `data` payloads (Phase 1 serialized; others fall back to `variables`)

- `rows`:      `{ title: string|null, fields: FieldNode[] }`
- `table`:     `{ title, target, columns: ColumnNode[], striped, compact, bordered, hoverable, textNotFound, subNotFound, iconNotFound }`
- `legend`:    `{ title, target, columns: ColumnNode[] }`
- `selection`: `{ title, fields: FieldNode[] }`
- `tabs`:      `{ titles: string[] }` (children are the tab panes)
- `locale-tabs`: `{ titles: string[], locales: { code, label }[], activeTab }` (one `tab-pane` child per content locale; inner fields are name-scoped per locale, e.g. `ko[title]`)
- `modal`:     `{ key, title, size, async, applyButton, closeButton, … }`
- `columns` / `split` / `accordion` / `blank` / `block` / `wrapper`: `{}` (content lives in `children`)
- custom (`Layout::component`/`react`): `{ component, props }`

## FieldNode (fields & actions)

Produced by `Field::toArray(): ?array` (Actions inherit it).

```ts
interface FieldNode {
  component: string;               // kebab class name: 'input','select','text-area','check-box','button','link',… or custom component name
  name: string | null;            // resolved field name (model.{field} prefix applied)
  value: unknown;                  // bound value (old input → closure → repository → default)
  old: unknown;                    // raw old() input, if any
  attributes: Record<string, unknown>; // everything else (title, placeholder, help, options, required, …) minus `value`
  errors: string[];                // validation errors for this field name
  fields?: FieldNode[];            // present for Group nodes
  props?: Record<string, unknown>; // present for custom ReactField nodes
}
```

Custom field props (`props`) in use:

- `permission-matrix`: `{ groups: { group: string, permissions: { slug, label }[] }[] }`. Value is a `{slug:true}` map or a slug array; the component submits the checked slugs as an array (grouped checkboxes with per-group "select all").

## ColumnNode (TD / Sight)

Produced by `TD::toArray()` / `Sight::toArray()`.

```ts
interface ColumnNode {
  name: string;       // data key
  column: string;     // sort/filter key
  title: string;      // header label
  slug: string;       // stable slug
  align: 'start' | 'center' | 'end';
  width: string | null;
  sort: boolean;
  filter: FieldNode | null;     // filter input descriptor (TD only)
  filterString: string | null; // active filter display (TD only)
  popover: string | null;
  defaultHidden: boolean;
  allowUserHidden: boolean;
  // For non-render columns the value is read client-side from the row by `name`.
  // For render (closure) columns the server pre-renders an HTML string in `rendered`.
  rendered?: string | null;
}
```

## Async / state model (replaces Turbo streams + encrypted `_state`)

Orchid used Turbo Streams + an encrypted `_state` field. The Inertia mapping:

- **Screen state**: the opaque encrypted `state` prop is echoed back on POST
  submissions (hidden `_state` field) so server-side public properties survive
  round-trips. React keeps it in the form payload, never inspects it.
- **POST actions**: `Route::screen()` keeps GET/POST dual purpose. POST method
  results return an **Inertia redirect** (`back()`/`redirect()`), so React just
  uses `router.post()` / `useForm().post()`.
- **Listener layouts**: on watched-field change, React issues an Inertia
  **partial reload** (`router.reload({ only: [layoutKey], data })`) to the same
  screen; the server re-runs the listener method and returns a fresh layout
  subtree in props.
- **Modal async**: the `orbit.async` endpoint returns
  `{ layout: LayoutNode|null, data: Record<string, unknown>, state: string }`
  JSON for a single layout slug; React fetches it via `fetch` when a modal
  opens and merges `data` into the field scope. `orbit.async.listener` returns
  the same shape for a single listener subtree.

---

# Phase 2 backend contract (entities / config / media / shared prop)

## Shared `orbit` prop (every Inertia response)

Populated by host `app/Http/Middleware/HandleInertiaRequests.php`. All
DB-backed parts are wrapped in a `safe()` guard so the app renders before
migration (falling back to the defaults below).

```ts
interface OrbitShared {
  menu: MenuItem[];                 // from Orbit menu registry (entities + system)
  permissions: string[];            // current user's permission slugs
  user: { id: number|string; name: string; email: string } | null;
  flash: { message: string | null; type: string | null };
  brand: OrbitBrand;
  notifications: OrbitNotification[]; // recent Orbit notifications (max 10)
  media: MediaEndpoints | null;      // resolved media library endpoint URLs
}

interface OrbitNotification {
  id: string | number;
  title: string | null;
  message: string;
  url: string | null;     // "View" action target
  time: string | null;    // human-diffed timestamp
  read: boolean;
}

interface MediaEndpoints {
  index: string;   // GET listing
  upload: string;  // POST upload
  remove: string;  // DELETE base; React appends `/{id}`
}

interface MenuItem {
  label: string;            // display label (title ?? name)
  icon: string | null;
  url: string | null;       // resolved route URL, or '#' / null for section headers
  badge: string | number | null; // resolved scalar badge value
  sort: number;
  section: string | null;   // group header (e.g. "Main", "System")
  divider: boolean;
  active: string[] | string | null; // active-match pattern(s)
  children: MenuItem[];     // sub-items (already permission-filtered)
}

interface OrbitBrand {
  name: string;
  logo: string | null;      // url/path
  favicon: string | null;   // url/path
  darkMode: boolean;
  palette: string;          // preset key, default 'orbit'
  colors: { primary: string; secondary: string; accent: string }; // hex
}
```

Menu items are pre-filtered by permission server-side; the React shell may
still hide empty sections. `brand.colors` are intended to be injected as CSS
custom properties (`--orbit-color-primary`, …) by the shell.

## Entity CRUD routes

Each registered Entity (scanned from `/entities` or via
`Orbit::registerEntities()`) exposes server-driven `orbit/screen` pages. Each
route is a `Route::screen()` dual GET/POST endpoint (GET renders the screen;
POST dispatches a screen method — `save`/`remove`/`restore`/etc. — selected by
the `method` field and returns an Inertia redirect):

| Name                          | Verb     | URI                        |
| ----------------------------- | -------- | -------------------------- |
| `orbit.entities.{key}.index`  | GET/POST | `entities/{key}`           |
| `orbit.entities.{key}.create` | GET/POST | `entities/{key}/create`    |
| `orbit.entities.{key}.edit`   | GET/POST | `entities/{key}/{id}/edit` |
| `orbit.entities.{key}.view`   | GET/POST | `entities/{key}/{id}`      |

`{key}` is the Entity `uriKey()` (kebab plural). Form fields are emitted under
the `model.` prefix (see `Crud\Layouts\ResourceFields`), so the React form
binds values at `data.model.*` and submits `{ model: {...}, _state, method }`.

## Config (Settings) routes

| Name                  | URI                            | Notes                                   |
| --------------------- | ------------------------------ | --------------------------------------- |
| `orbit.configs`       | `configs/{method?}`            | Settings hub (`settings-hub` component) |
| `orbit.configs.group` | `configs/{group}/{method?}`    | Auto-rendered group edit form           |

Group-edit fields are emitted under the `config.` prefix; dotted item keys are
encoded for HTML form names and decoded on save. Permissions: `orbit.configs`
(hub) and `orbit.configs.{uriKey}` (per group).

## Media library endpoints (JSON, under orbit prefix)

For the frontend media picker. All require the admin guard.

```
GET    orbit.media.index    media/library             ?kind=&q=&per_page=  -> { data: MediaItem[], meta }
POST   orbit.media.search   media/library/search      { q, kind, per_page } -> { data: MediaItem[], meta }
POST   orbit.media.upload   media/library/upload       multipart: files[][, group] -> 201 { data: MediaItem[] }
DELETE orbit.media.destroy  media/library/{id}                              -> { deleted: true }
GET    orbit.media.status   media/library/{id}/status                       -> { id, kind, encoding_status, meta }
```

```ts
interface MediaListResponse {
  data: MediaItem[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

interface MediaItem {
  id: string;                 // uuid
  url: string;                // public url
  name: string;               // original_name
  kind: 'image' | 'video' | 'audio' | 'file' | null;
  mime: string;
  extension: string | null;
  size: number;               // bytes
  width: number | null;
  height: number | null;
  duration: number | null;    // seconds (video/audio)
  alt: string | null;
  encoding_status: 'pending' | 'processing' | 'done' | 'skipped' | 'failed' | null;
  created_at: string | null;  // ISO-8601
}
```

Image uploads are resized to the configured max width (default 1200) at the
configured quality (default 100). Video uploads are queued for ffmpeg encoding
when `ffmpeg` is available (`encoding_status` transitions
`pending → processing → done`); otherwise the original is kept and flagged
`skipped`. Poll `orbit.media.status` for progress.
