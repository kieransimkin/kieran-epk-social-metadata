"""Writable, per-run scratch directories for standalone Python contracts."""
from __future__ import annotations

from contextlib import contextmanager
import os
from pathlib import Path
import tempfile
import uuid


@contextmanager
def project_temporary_directory():
    parent = None
    if os.name == "nt":
        local = Path(os.environ.get("LOCALAPPDATA") or Path.home() / "AppData" / "Local")
        parent = local / "kieran-epk-social-metadata" / "test-tmp"
        parent.mkdir(parents=True, exist_ok=True)
        marker = parent / f"probe-{uuid.uuid4().hex}"
        renamed = marker.with_name(marker.name + "-renamed")
        try:
            marker.write_text("temp-probe", encoding="utf-8")
            if marker.read_text(encoding="utf-8") != "temp-probe":
                raise OSError(f"Temporary-directory readback failed: {parent}")
            marker.rename(renamed)
        finally:
            renamed.unlink(missing_ok=True)
            marker.unlink(missing_ok=True)
    with tempfile.TemporaryDirectory(prefix="ksem-test-", dir=parent) as directory:
        yield directory
