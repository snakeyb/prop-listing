import type { Express } from "express";
import { createServer, type Server } from "http";

const ESPOCRM_URL = process.env.ESPOCRM_URL || "";
const ESPOCRM_API_KEY = process.env.ESPOCRM_API_KEY || "";

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

  app.get("/api/property/:id", async (req, res) => {
    try {
      const { id } = req.params;
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

      return res.json(property);
    } catch (error: any) {
      console.error("Error fetching property:", error);
      return res.status(500).json({ error: "Failed to fetch property data" });
    }
  });

  app.get("/api/attachment/:id", async (req, res) => {
    try {
      const { id } = req.params;
      const response = await fetchFromEspo(`Attachment/file/${id}`);

      if (!response.ok) {
        return res.status(response.status).json({
          error: `Failed to fetch attachment: ${response.statusText}`,
        });
      }

      const contentType = response.headers.get("content-type") || "image/jpeg";
      const buffer = Buffer.from(await response.arrayBuffer());

      res.setHeader("Content-Type", contentType);
      res.setHeader("Cache-Control", "public, max-age=86400");
      return res.send(buffer);
    } catch (error: any) {
      console.error("Error fetching attachment:", error);
      return res.status(500).json({ error: "Failed to fetch attachment" });
    }
  });

  return httpServer;
}
