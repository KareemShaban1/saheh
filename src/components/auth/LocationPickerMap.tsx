const EGYPT_CENTER = { lat: 26.8206, lng: 30.8025 };

type Props = {
  latitude: string;
  longitude: string;
  onChange: (latitude: string, longitude: string) => void;
};

export default function LocationPickerMap({ latitude, longitude, onChange }: Props) {
  const lat = Number(latitude);
  const lng = Number(longitude);
  const hasCoordinates = Number.isFinite(lat) && Number.isFinite(lng);
  const center = hasCoordinates ? { lat, lng } : EGYPT_CENTER;
  const delta = hasCoordinates ? 0.05 : 8;
  const left = center.lng - delta;
  const right = center.lng + delta;
  const bottom = center.lat - delta;
  const top = center.lat + delta;
  const mapUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(
    `${left},${bottom},${right},${top}`,
  )}&layer=mapnik&marker=${encodeURIComponent(`${center.lat},${center.lng}`)}`;

  return (
    <div className="space-y-2">
      <div className="flex justify-end">
        <button
          type="button"
          className="text-xs text-primary hover:underline"
          onClick={() => {
            if (!navigator.geolocation) {
              return;
            }
            navigator.geolocation.getCurrentPosition((pos) => {
              onChange(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6));
            });
          }}
        >
          Use current location
        </button>
      </div>
      <div className="h-64 w-full overflow-hidden rounded-md border border-input">
        <iframe
          title="location-map"
          src={mapUrl}
          className="h-full w-full"
          loading="lazy"
          referrerPolicy="no-referrer-when-downgrade"
        />
      </div>
      <p className="text-xs text-muted-foreground">Use current location button or type latitude/longitude manually.</p>
    </div>
  );
}
