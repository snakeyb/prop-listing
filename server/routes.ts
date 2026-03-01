import type { Express, Request, Response, NextFunction } from "express";
import { createServer, type Server } from "http";

const ESPOCRM_URL = process.env.ESPOCRM_URL || "";
const ESPOCRM_API_KEY = process.env.ESPOCRM_API_KEY || "";

// --- In-memory cache ---
interface CacheEntry<T> {
  data: T;
  expires: number;
}

const propertyCache = new Map<string, CacheEntry<any>>();
const attachmentCache = new Map<string, CacheEntry<{ buffer: Buffer; contentType: string }>>();

const PROPERTY_CACHE_TTL = 5 * 60 * 1000; // 5 minutes
const ATTACHMENT_CACHE_TTL = 60 * 60 * 1000; // 1 hour (images rarely change)

function getCached<T>(cache: Map<string, CacheEntry<T>>, key: string): T | null {
  const entry = cache.get(key);
  if (entry && Date.now() < entry.expires) {
    return entry.data;
  }
  if (entry) cache.delete(key);
  return null;
}

function setCache<T>(cache: Map<string, CacheEntry<T>>, key: string, data: T, ttl: number) {
  cache.set(key, { data, expires: Date.now() + ttl });
}

// --- Rate limiter ---
interface RateLimitEntry {
  count: number;
  resetAt: number;
}

const rateLimitMap = new Map<string, RateLimitEntry>();
const RATE_LIMIT_WINDOW = 60 * 1000; // 1 minute
const RATE_LIMIT_MAX = 30; // 30 requests per minute per IP

setInterval(() => {
  const now = Date.now();
  rateLimitMap.forEach((entry, key) => {
    if (now > entry.resetAt) rateLimitMap.delete(key);
  });
  propertyCache.forEach((entry, key) => {
    if (now > entry.expires) propertyCache.delete(key);
  });
  attachmentCache.forEach((entry, key) => {
    if (now > entry.expires) attachmentCache.delete(key);
  });
}, 60 * 1000);

function rateLimiter(req: Request, res: Response, next: NextFunction) {
  const ip = req.ip || req.socket.remoteAddress || "unknown";
  const now = Date.now();

  let entry = rateLimitMap.get(ip);
  if (!entry || now > entry.resetAt) {
    entry = { count: 0, resetAt: now + RATE_LIMIT_WINDOW };
    rateLimitMap.set(ip, entry);
  }

  entry.count++;

  res.setHeader("X-RateLimit-Limit", RATE_LIMIT_MAX);
  res.setHeader("X-RateLimit-Remaining", Math.max(0, RATE_LIMIT_MAX - entry.count));
  res.setHeader("X-RateLimit-Reset", Math.ceil(entry.resetAt / 1000));

  if (entry.count > RATE_LIMIT_MAX) {
    const retryAfter = Math.ceil((entry.resetAt - now) / 1000);
    res.setHeader("Retry-After", retryAfter);
    return res.status(429).json({
      error: "Too many requests. Please try again later.",
      retryAfter,
    });
  }

  next();
}

async function fetchFromEspo(path: string) {
  const url = `${ESPOCRM_URL}/api/v1/${path}`;
  const response = await fetch(url, {
    headers: {
      "X-Api-Key": ESPOCRM_API_KEY,
      "Content-Type": "application/json",
    },
  });
  return response;
}

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {

  app.use("/api", rateLimiter);

  app.get("/api/property/:id", async (req, res) => {
    try {
      const { id } = req.params;

      const cached = getCached(propertyCache, id);
      if (cached) {
        res.setHeader("X-Cache", "HIT");
        return res.json(cached);
      }

      const response = await fetchFromEspo(`CUnits/${id}`);

      if (!response.ok) {
        return res.status(response.status).json({
          error: `Failed to fetch property: ${response.statusText}`,
        });
      }

      const data = await response.json();

      const property = {
        id: data.id,
        name: data.name,
        propertyType: data.propertyType,
        bedrooms: data.bedrooms,
        receptionRooms: data.receptionRooms,
        bathrooms: data.bathrooms,
        parking: data.parking,
        addressStreet: data.addressStreet,
        addressCity: data.addressCity,
        addressState: data.addressState,
        addressPostalCode: data.addressPostalCode,
        description: data.description,
        propertyPhotosIds: data.propertyPhotosIds || [],
        propertyPhotosNames: data.propertyPhotosNames || {},
        propertyPhotosTypes: data.propertyPhotosTypes || {},
        propertyPartnerName: data.propertyPartnerName,
      };

      setCache(propertyCache, id, property, PROPERTY_CACHE_TTL);
      res.setHeader("X-Cache", "MISS");
      return res.json(property);
    } catch (error: any) {
      if (process.env.NODE_ENV !== "production") console.error("Error fetching property:", error);
      return res.status(500).json({ error: "Failed to fetch property data" });
    }
  });

  app.get("/api/attachment/:id", async (req, res) => {
    try {
      const { id } = req.params;

      const cached = getCached(attachmentCache, id);
      if (cached) {
        res.setHeader("Content-Type", cached.contentType);
        res.setHeader("Cache-Control", "public, max-age=86400");
        res.setHeader("X-Cache", "HIT");
        return res.send(cached.buffer);
      }

      const response = await fetchFromEspo(`Attachment/file/${id}`);

      if (!response.ok) {
        return res.status(response.status).json({
          error: `Failed to fetch attachment: ${response.statusText}`,
        });
      }

      const contentType = response.headers.get("content-type") || "image/jpeg";
      const buffer = Buffer.from(await response.arrayBuffer());

      setCache(attachmentCache, id, { buffer, contentType }, ATTACHMENT_CACHE_TTL);

      res.setHeader("Content-Type", contentType);
      res.setHeader("Cache-Control", "public, max-age=86400");
      res.setHeader("X-Cache", "MISS");
      return res.send(buffer);
    } catch (error: any) {
      if (process.env.NODE_ENV !== "production") console.error("Error fetching attachment:", error);
      return res.status(500).json({ error: "Failed to fetch attachment" });
    }
  });

  return httpServer;
}
