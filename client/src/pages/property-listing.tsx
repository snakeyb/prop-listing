import { useQuery } from "@tanstack/react-query";
import { useParams } from "wouter";
import { useState, useCallback } from "react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Bed,
  Bath,
  Sofa,
  Car,
  MapPin,
  Home,
  ChevronLeft,
  ChevronRight,
  X,
  Building2,
  Image as ImageIcon,
  ExternalLink,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { apiUrl } from "@/lib/queryClient";

interface Property {
  id: string;
  name: string;
  propertyType: string;
  bedrooms: number;
  receptionRooms: number;
  bathrooms: number;
  parking: string;
  addressStreet: string;
  addressCity: string;
  addressState: string;
  addressPostalCode: string;
  description: string | null;
  propertyPhotosIds: string[];
  propertyPhotosNames: Record<string, string>;
  propertyPhotosTypes: Record<string, string>;
  propertyPartnerName: string;
}

function ImageGallery({ property }: { property: Property }) {
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const photos = property.propertyPhotosIds;

  const goNext = useCallback(() => {
    setSelectedIndex((prev) => (prev + 1) % photos.length);
  }, [photos.length]);

  const goPrev = useCallback(() => {
    setSelectedIndex((prev) => (prev - 1 + photos.length) % photos.length);
  }, [photos.length]);

  if (photos.length === 0) {
    return (
      <div className="aspect-[16/9] bg-muted rounded-md flex items-center justify-center">
        <div className="text-center text-muted-foreground">
          <ImageIcon className="w-12 h-12 mx-auto mb-2 opacity-40" />
          <p>No photos available</p>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="space-y-3">
        <div
          className="relative aspect-[16/9] rounded-md overflow-hidden cursor-pointer group"
          onClick={() => setLightboxOpen(true)}
          data-testid="main-image-container"
        >
          <img
            src={apiUrl(`/api/attachment/${photos[selectedIndex]}`)}
            alt={property.propertyPhotosNames[photos[selectedIndex]] || property.name}
            className="w-full h-full object-cover transition-transform duration-500"
            data-testid="main-image"
          />
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300" />
          {photos.length > 1 && (
            <>
              <Button
                size="icon"
                variant="outline"
                className="absolute left-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 dark:bg-black/60 backdrop-blur-sm border-0"
                onClick={(e) => {
                  e.stopPropagation();
                  goPrev();
                }}
                data-testid="button-prev-image"
              >
                <ChevronLeft className="w-5 h-5" />
              </Button>
              <Button
                size="icon"
                variant="outline"
                className="absolute right-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 dark:bg-black/60 backdrop-blur-sm border-0"
                onClick={(e) => {
                  e.stopPropagation();
                  goNext();
                }}
                data-testid="button-next-image"
              >
                <ChevronRight className="w-5 h-5" />
              </Button>
            </>
          )}
          <div className="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
            <Badge variant="secondary" className="bg-black/60 text-white border-0 backdrop-blur-sm">
              {selectedIndex + 1} / {photos.length}
            </Badge>
          </div>
        </div>

        {photos.length > 1 && (
          <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-thin" data-testid="thumbnail-strip">
            {photos.map((photoId, index) => (
              <button
                key={photoId}
                onClick={() => setSelectedIndex(index)}
                className={`relative flex-shrink-0 w-20 h-14 rounded-md overflow-hidden transition-all duration-200 ${
                  index === selectedIndex
                    ? "ring-2 ring-primary ring-offset-2 ring-offset-background"
                    : "opacity-60 hover:opacity-100"
                }`}
                data-testid={`thumbnail-${index}`}
              >
                <img
                  src={apiUrl(`/api/attachment/${photoId}`)}
                  alt={property.propertyPhotosNames[photoId] || `Photo ${index + 1}`}
                  className="w-full h-full object-cover"
                  loading="lazy"
                />
              </button>
            ))}
          </div>
        )}
      </div>

      {lightboxOpen && (
        <div
          className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
          onClick={() => setLightboxOpen(false)}
          data-testid="lightbox-overlay"
        >
          <Button
            size="icon"
            variant="ghost"
            className="absolute top-4 right-4 text-white hover:bg-white/10 z-50"
            onClick={() => setLightboxOpen(false)}
            data-testid="button-close-lightbox"
          >
            <X className="w-6 h-6" />
          </Button>

          {photos.length > 1 && (
            <>
              <Button
                size="icon"
                variant="ghost"
                className="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 z-50"
                onClick={(e) => {
                  e.stopPropagation();
                  goPrev();
                }}
                data-testid="button-lightbox-prev"
              >
                <ChevronLeft className="w-8 h-8" />
              </Button>
              <Button
                size="icon"
                variant="ghost"
                className="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 z-50"
                onClick={(e) => {
                  e.stopPropagation();
                  goNext();
                }}
                data-testid="button-lightbox-next"
              >
                <ChevronRight className="w-8 h-8" />
              </Button>
            </>
          )}

          <div className="max-w-[90vw] max-h-[85vh] flex items-center justify-center" onClick={(e) => e.stopPropagation()}>
            <img
              src={apiUrl(`/api/attachment/${photos[selectedIndex]}`)}
              alt={property.propertyPhotosNames[photos[selectedIndex]] || property.name}
              className="max-w-full max-h-[85vh] object-contain rounded-md"
              data-testid="lightbox-image"
            />
          </div>

          <div className="absolute bottom-6 left-1/2 -translate-x-1/2">
            <Badge variant="secondary" className="bg-black/60 text-white border-0 backdrop-blur-sm text-sm">
              {selectedIndex + 1} / {photos.length}
            </Badge>
          </div>
        </div>
      )}
    </>
  );
}

function PropertyDetailItem({
  icon: Icon,
  label,
  value,
}: {
  icon: typeof Bed;
  label: string;
  value: string | number;
}) {
  return (
    <div className="flex items-center gap-3 py-3" data-testid={`detail-${label.toLowerCase().replace(/\s/g, "-")}`}>
      <div className="flex items-center justify-center w-10 h-10 rounded-md" style={{ backgroundColor: 'rgba(74, 100, 146, 0.1)', color: '#4A6492' }}>
        <Icon className="w-5 h-5" />
      </div>
      <div>
        <p className="text-sm text-muted-foreground">{label}</p>
        <p className="font-medium">{value}</p>
      </div>
    </div>
  );
}

function PropertySkeleton() {
  return (
    <div className="min-h-screen bg-background">
      <div className="max-w-5xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <Skeleton className="h-8 w-64 mb-2" />
        <Skeleton className="h-5 w-48 mb-6" />
        <Skeleton className="aspect-[16/9] w-full rounded-md mb-4" />
        <div className="flex gap-2 mb-8">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="w-20 h-14 rounded-md" />
          ))}
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-20 rounded-md" />
          ))}
        </div>
      </div>
    </div>
  );
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <Card className="max-w-md mx-4 w-full">
        <CardContent className="pt-6 text-center">
          <div className="w-16 h-16 rounded-full bg-destructive/10 flex items-center justify-center mx-auto mb-4">
            <Building2 className="w-8 h-8 text-destructive" />
          </div>
          <h2 className="text-xl font-semibold mb-2">Property Not Found</h2>
          <p className="text-muted-foreground text-sm">{message}</p>
        </CardContent>
      </Card>
    </div>
  );
}

function NoIdState() {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <Card className="max-w-md mx-4 w-full">
        <CardContent className="pt-6 text-center">
          <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
            <Home className="w-8 h-8 text-muted-foreground" />
          </div>
          <h2 className="text-xl font-semibold mb-2">No Property Selected</h2>
          <p className="text-muted-foreground text-sm">
            Please provide a property ID in the URL to view a listing.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

export default function PropertyListing() {
  const params = useParams<{ id: string }>();
  const propertyId = params.id;

  const {
    data: property,
    isLoading,
    error,
  } = useQuery<Property>({
    queryKey: ["/api/property", propertyId],
    enabled: !!propertyId,
  });

  if (!propertyId) {
    return <NoIdState />;
  }

  if (isLoading) {
    return <PropertySkeleton />;
  }

  if (error || !property) {
    return (
      <ErrorState
        message={
          error?.message || "The property you're looking for could not be found."
        }
      />
    );
  }

  const fullAddress = [
    property.addressStreet,
    property.addressCity,
    property.addressState,
    property.addressPostalCode,
  ]
    .filter(Boolean)
    .join(", ");

  return (
    <div className="min-h-screen bg-background" data-testid="property-listing-page">
      <header className="border-b bg-card/50 backdrop-blur-sm sticky top-0 z-40">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center gap-2">
            <Building2 className="w-6 h-6" style={{ color: '#4A6492' }} />
            <span className="font-semibold text-lg" data-testid="text-header-title">Placement Findr Property Listing</span>
          </div>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-6">
          <div className="flex flex-wrap items-center gap-2 mb-1">
            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight" data-testid="text-property-name">
              {property.name}
            </h1>
            {property.propertyType && (
              <Badge variant="secondary" data-testid="badge-property-type">
                {property.propertyType}
              </Badge>
            )}
          </div>
          <div className="flex items-center gap-1.5 text-muted-foreground">
            <MapPin className="w-4 h-4" />
            <p className="text-sm" data-testid="text-address">{fullAddress}</p>
          </div>
        </div>

        <div className="mb-8">
          <ImageGallery property={property} />
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8" data-testid="property-details-grid">
          <Card>
            <CardContent className="p-4">
              <PropertyDetailItem icon={Bed} label="Bedrooms" value={property.bedrooms} />
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <PropertyDetailItem icon={Bath} label="Bathrooms" value={property.bathrooms} />
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <PropertyDetailItem icon={Sofa} label="Receptions" value={property.receptionRooms} />
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <PropertyDetailItem icon={Car} label="Parking" value={property.parking || "N/A"} />
            </CardContent>
          </Card>
        </div>

        {property.description && (
          <div className="mb-8" data-testid="property-description-section">
            <h2 className="text-lg font-semibold mb-3">About this property</h2>
            <div className="prose prose-sm max-w-none text-muted-foreground dark:prose-invert">
              <p>{property.description}</p>
            </div>
          </div>
        )}

        <div className="mb-8" data-testid="property-address-section">
          <h2 className="text-lg font-semibold mb-3">Location</h2>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-start gap-3">
                <div className="flex items-center justify-center w-10 h-10 rounded-md flex-shrink-0" style={{ backgroundColor: 'rgba(74, 100, 146, 0.1)', color: '#4A6492' }}>
                  <MapPin className="w-5 h-5" />
                </div>
                <div>
                  {property.addressStreet && (
                    <p className="font-medium" data-testid="text-street">{property.addressStreet}</p>
                  )}
                  <p className="text-sm text-muted-foreground" data-testid="text-city-state">
                    {[property.addressCity, property.addressState].filter(Boolean).join(", ")}
                  </p>
                  {property.addressPostalCode && (
                    <p className="text-sm text-muted-foreground" data-testid="text-postal-code">
                      {property.addressPostalCode}
                    </p>
                  )}
                  <a
                    href={`https://www.google.co.uk/maps/search/${encodeURIComponent(fullAddress)}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 text-sm mt-2 underline hover:opacity-80 transition-opacity"
                    style={{ color: '#4A6492' }}
                    data-testid="link-google-maps"
                  >
                    View on Google Maps
                    <ExternalLink className="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

      </main>

      <footer className="border-t py-6 mt-8">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-muted-foreground">
          Property listing provided by{" "}
          <a
            href="https://propertypipeline.co.uk/"
            target="_blank"
            rel="noopener noreferrer"
            className="underline hover:opacity-80 transition-opacity"
            style={{ color: '#4A6492' }}
            data-testid="link-property-pipeline"
          >
            PropertyPipeline
          </a>
        </div>
      </footer>
    </div>
  );
}
