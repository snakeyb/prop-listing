# Overview

This is a property listing web application that displays real estate property details fetched from an external EspoCRM instance. The app shows property information including photos, specifications (bedrooms, bathrooms, parking), and address details. It follows a monorepo structure with a React frontend and Express backend, connected to a PostgreSQL database via Drizzle ORM.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Monorepo Structure
The project uses a three-folder monorepo layout:
- `client/` — React SPA (Single Page Application)
- `server/` — Express API server
- `shared/` — Shared types and database schema used by both client and server

## Frontend (client/)
- **Framework**: React with TypeScript
- **Routing**: Wouter (lightweight alternative to React Router)
- **State/Data Fetching**: TanStack React Query for server state management
- **UI Components**: shadcn/ui component library (new-york style) built on Radix UI primitives
- **Styling**: Tailwind CSS with CSS variables for theming (supports light/dark mode)
- **Build Tool**: Vite with React plugin
- **Path Aliases**: `@/` maps to `client/src/`, `@shared/` maps to `shared/`

The main page is a property listing view at `/property/:id` that fetches property data from the backend API, displays an image gallery, and shows property details like bedrooms, bathrooms, and address.

## Backend (server/)
- **Framework**: Express 5 running on Node.js
- **Language**: TypeScript, executed via `tsx`
- **API Pattern**: REST endpoints under `/api/` prefix
- **Proxy Pattern**: The server acts as a proxy/adapter to EspoCRM — it fetches property data from EspoCRM's API and reshapes it before sending to the frontend
- **Dev Server**: Vite dev server is integrated as middleware for HMR during development
- **Production**: Static files are served from `dist/public/` after build

## Database
- **ORM**: Drizzle ORM with PostgreSQL dialect
- **Schema Location**: `shared/schema.ts`
- **Current Schema**: A simple `users` table with id (UUID), username, and password
- **Migrations**: Managed via `drizzle-kit push` (schema push approach, not migration files)
- **Storage Layer**: Currently uses an in-memory storage implementation (`MemStorage`) — the PostgreSQL/Drizzle setup is configured but the app primarily uses MemStorage for users
- **Connection**: Requires `DATABASE_URL` environment variable

## Build Process
- **Client Build**: Vite builds the React app to `dist/public/`
- **Server Build**: esbuild bundles the server to `dist/index.cjs`, with select dependencies bundled (allowlist) and others kept external
- **Dev**: `tsx server/index.ts` runs the server with Vite middleware for hot reload
- **Production**: `node dist/index.cjs` serves the built app

## Key API Endpoints
- `GET /api/property/:id` — Fetches a property from EspoCRM's `CUnits` entity, transforms the response, and returns property details including photo IDs and metadata

# External Dependencies

## EspoCRM Integration
- The primary data source is an EspoCRM instance accessed via REST API
- **Authentication**: API key-based (`X-Api-Key` header)
- **Environment Variables Required**:
  - `ESPOCRM_URL` — Base URL of the EspoCRM instance
  - `ESPOCRM_API_KEY` — API key for authentication
- **Entity Used**: `CUnits` (custom entity for property units)
- Property photos are referenced by ID and can include links to Google Drive folders

## PostgreSQL Database
- **Environment Variable**: `DATABASE_URL` — PostgreSQL connection string
- Used via Drizzle ORM; required for the app to start (drizzle config throws if missing)
- Session storage uses `connect-pg-simple` (available in dependencies)

## Other Notable Dependencies
- `lucide-react` — Icon library
- `embla-carousel-react` — Carousel/slider functionality
- `react-day-picker` — Date picker component
- `recharts` — Charting library
- `react-hook-form` with `zod` — Form handling and validation
- `vaul` — Drawer component
- Replit-specific plugins for dev: `@replit/vite-plugin-runtime-error-modal`, `@replit/vite-plugin-cartographer`, `@replit/vite-plugin-dev-banner`