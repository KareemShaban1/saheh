"""Generate solid-color PNG icons for PWA (no extra deps)."""
import struct
import zlib
from pathlib import Path


def chunk(tag: bytes, data: bytes) -> bytes:
    crc = zlib.crc32(tag + data) & 0xFFFFFFFF
    return struct.pack(">I", len(data)) + tag + data + struct.pack(">I", crc)


def write_png(path: Path, width: int, height: int, r: int, g: int, b: int, a: int = 255) -> None:
    pixel = bytes([r, g, b, a])
    row = b"\x00" + pixel * width
    raw = row * height
    compressed = zlib.compress(raw, 9)
    ihdr = struct.pack(">IIBBBBB", width, height, 8, 6, 0, 0, 0)
    data = b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", compressed) + chunk(b"IEND", b"")
    path.write_bytes(data)


def main() -> None:
    root = Path(__file__).resolve().parent.parent
    public = root / "public"
    public.mkdir(parents=True, exist_ok=True)
    # Teal #0d9488 — matches PWA branding
    write_png(public / "pwa-192.png", 192, 192, 13, 148, 136)
    write_png(public / "pwa-512.png", 512, 512, 13, 148, 136)
    print("Wrote", public / "pwa-192.png", public / "pwa-512.png")


if __name__ == "__main__":
    main()
