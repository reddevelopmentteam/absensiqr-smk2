#!/usr/bin/env python3
"""Local face processor adapter for the biometric enrollment pipeline.

Server requirements:
  pip install face_recognition opencv-python-headless

The script only prints JSON to stdout and never stores the input image.
"""
import json
import os
import sys


def fail(message):
    print(json.dumps({"available": False, "error": message, "faces": []}))
    sys.exit(0)


def main():
    if len(sys.argv) != 2:
        fail("Path gambar tidak diberikan.")

    path = sys.argv[1]
    if not os.path.isfile(path):
        fail("File gambar tidak ditemukan.")

    try:
        import cv2  # noqa: F401
        import face_recognition
    except Exception as exc:  # pragma: no cover - environment dependent
        fail("Engine wajah belum terpasang: %s" % exc)

    try:
        image = face_recognition.load_image_file(path)
        locations = face_recognition.face_locations(image, model="hog")
        encodings = face_recognition.face_encodings(image, locations)
    except Exception as exc:
        fail("Gambar tidak dapat dianalisis: %s" % exc)

    faces = []
    for index, location in enumerate(locations):
        top, right, bottom, left = location
        encoding = encodings[index] if index < len(encodings) else []
        faces.append(
            {
                "x": int(left),
                "y": int(top),
                "width": int(right - left),
                "height": int(bottom - top),
                "confidence": 1.0,
                "embedding": [float(value) for value in encoding],
            }
        )

    print(json.dumps({"available": True, "error": None, "faces": faces}))


if __name__ == "__main__":
    main()
