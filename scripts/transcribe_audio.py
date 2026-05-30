#!/usr/bin/env python3

import argparse
import json
import sys
from typing import Any, Dict, List


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Transcribe audio using local Whisper backends.")
    parser.add_argument("--input", required=True, help="Absolute path to the extracted audio file.")
    parser.add_argument("--backend", default="whisper", choices=["auto", "whisper", "faster-whisper"])
    parser.add_argument("--model", default="base")
    parser.add_argument("--language", default=None)
    parser.add_argument("--device", default="auto")
    parser.add_argument("--compute-type", default="auto")
    parser.add_argument("--beam-size", type=int, default=5)

    return parser.parse_args()


def run_whisper(args: argparse.Namespace) -> Dict[str, Any]:
    try:
        import whisper
    except ImportError as exc:
        raise RuntimeError("Python package 'openai-whisper' is not installed.") from exc

    device = "cpu" if args.device == "auto" else args.device
    model = whisper.load_model(args.model, device=device)

    options: Dict[str, Any] = {
        "audio": args.input,
        "verbose": False,
        "fp16": device not in (None, "cpu"),
    }

    if args.language:
        options["language"] = args.language

    result = model.transcribe(**options)
    segments = []

    for segment in result.get("segments", []):
        text = str(segment.get("text", "")).strip()

        if not text:
            continue

        segments.append(
            {
                "start": float(segment.get("start", 0.0)),
                "end": float(segment.get("end", 0.0)),
                "text": text,
            }
        )

    duration = max((segment["end"] for segment in segments), default=0.0)

    return {
        "backend": "whisper",
        "model": args.model,
        "language": result.get("language"),
        "text": str(result.get("text", "")).strip(),
        "duration": duration,
        "segments": segments,
    }


def run_faster_whisper(args: argparse.Namespace) -> Dict[str, Any]:
    try:
        from faster_whisper import WhisperModel
    except ImportError as exc:
        raise RuntimeError("Python package 'faster-whisper' is not installed.") from exc

    device = "cpu" if args.device == "auto" else args.device
    compute_type = "int8" if args.compute_type == "auto" else args.compute_type
    model = WhisperModel(args.model, device=device, compute_type=compute_type)
    segments_iterator, info = model.transcribe(
        args.input,
        language=args.language,
        beam_size=args.beam_size,
        vad_filter=True,
    )

    segments: List[Dict[str, Any]] = []
    text_parts: List[str] = []

    for segment in segments_iterator:
        text = str(segment.text).strip()

        if not text:
            continue

        text_parts.append(text)
        segments.append(
            {
                "start": float(segment.start),
                "end": float(segment.end),
                "text": text,
            }
        )

    duration = max((segment["end"] for segment in segments), default=0.0)

    return {
        "backend": "faster-whisper",
        "model": args.model,
        "language": getattr(info, "language", None),
        "text": " ".join(text_parts).strip(),
        "duration": duration,
        "segments": segments,
    }


def main() -> int:
    args = parse_args()
    backends = ["whisper", "faster-whisper"] if args.backend == "auto" else [args.backend]
    errors: List[str] = []

    for backend in backends:
        try:
            result = run_whisper(args) if backend == "whisper" else run_faster_whisper(args)

            if not result.get("text"):
                raise RuntimeError(f"Local backend '{backend}' returned an empty transcript.")

            print(json.dumps(result, ensure_ascii=False))

            return 0
        except Exception as exc:  # noqa: BLE001
            errors.append(f"{backend}: {exc}")

    print(" | ".join(errors) if errors else "No local Whisper backend could transcribe the audio.", file=sys.stderr)

    return 1


if __name__ == "__main__":
    raise SystemExit(main())
