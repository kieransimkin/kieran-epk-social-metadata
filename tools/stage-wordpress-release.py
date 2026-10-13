"""Stage an immutable, checksum-verified GitHub release for WordPress.org SVN."""
from __future__ import annotations
import argparse
import hashlib
import io
import json
import os
from pathlib import Path, PurePosixPath
import re
import stat
from urllib.request import Request, urlopen
import zipfile

REPO = "kieransimkin/kieran-epk-social-metadata"
SLUG = "kieran-epk-social-metadata"


def fetch(url):
    headers = {"User-Agent": "EPK-Social-Metadata-release-stager"}
    if url.startswith("https://api.github.com/") and os.environ.get("GH_TOKEN"):
        headers["Authorization"] = "Bearer " + os.environ["GH_TOKEN"]
    with urlopen(Request(url, headers=headers), timeout=60) as response:
        data = response.read(10_000_001)
    if len(data) > 10_000_000:
        raise ValueError("Release response exceeds the directory package bound")
    return data


def validate_archive(data, version):
    with zipfile.ZipFile(io.BytesIO(data)) as archive:
        names = archive.namelist()
        if len(names) != len(set(names)):
            raise ValueError("Duplicate archive paths")
        files = {}
        total = 0
        for entry in archive.infolist():
            path = PurePosixPath(entry.filename)
            mode = entry.external_attr >> 16
            if ("\\" in entry.filename or ":" in entry.filename or path.is_absolute()
                    or ".." in path.parts or path.parts[0] != SLUG or stat.S_ISLNK(mode)):
                raise ValueError("Unsafe archive path or symlink")
            if entry.is_dir():
                continue
            total += entry.file_size
            if total > 20_000_000:
                raise ValueError("Expanded package exceeds the production bound")
            files[entry.filename] = archive.read(entry)
        main = files[f"{SLUG}/{SLUG}.php"].decode("utf-8")
        readme = files[f"{SLUG}/readme.txt"].decode("utf-8")
        if not re.search(r"(?m)^\s*\* Version: " + re.escape(version) + r"\s*$", main):
            raise ValueError("Plugin version differs from the release tag")
        if not re.search(r"(?m)^Stable tag: " + re.escape(version) + r"\s*$", readme):
            raise ValueError("Directory stable tag differs from the release tag")
        if f"{SLUG}/LICENSE.txt" not in files or "https://kieransimkin.co.uk/" not in readme:
            raise ValueError("Missing licence or website link")
        if any(part in {".git", ".github", "tests", "tools", "node_modules"}
               for name in files for part in PurePosixPath(name).parts):
            raise ValueError("Development files must not reach the directory package")
    return files


def stage(tag, output, fetcher=fetch):
    if not re.fullmatch(r"v\d+\.\d+\.\d+", tag):
        raise ValueError("Only stable version tags may be published")
    version = tag[1:]
    base = f"https://api.github.com/repos/{REPO}"
    release = json.loads(fetcher(f"{base}/releases/tags/{tag}"))
    if release["tag_name"] != tag or release["draft"] or release["prerelease"]:
        raise ValueError("The matching stable GitHub release must already be public")
    reference = json.loads(fetcher(f"{base}/git/ref/tags/{tag}"))["object"]
    for _ in range(4):
        if reference["type"] == "commit":
            break
        if reference["type"] != "tag" or not re.fullmatch(r"[a-f0-9]{40}", reference["sha"]):
            raise ValueError("Invalid source tag reference")
        reference = json.loads(fetcher(f"{base}/git/tags/{reference['sha']}"))["object"]
    if reference["type"] != "commit" or not re.fullmatch(r"[a-f0-9]{40}", reference["sha"]):
        raise ValueError("Could not identify an immutable source commit")
    assets = {asset["name"]: asset for asset in release["assets"]}
    filename = f"{SLUG}-{version}.zip"
    package = assets[filename]
    checksums = assets["SHA256SUMS.txt"]
    prefix = f"https://github.com/{REPO}/releases/download/{tag}/"
    for name, asset in [(filename, package), ("SHA256SUMS.txt", checksums)]:
        if asset["browser_download_url"] != prefix + name:
            raise ValueError("Unexpected release asset location")
    data = fetcher(package["browser_download_url"])
    checksum_text = fetcher(checksums["browser_download_url"]).decode("ascii")
    matches = re.findall(r"(?m)^([a-f0-9]{64})\s+\*?" + re.escape(filename) + r"$", checksum_text)
    digest = hashlib.sha256(data).hexdigest()
    if len(matches) != 1 or matches[0] != digest or len(data) != package["size"]:
        raise ValueError("Release checksum or byte-size mismatch")
    if package.get("digest") and package["digest"] != "sha256:" + digest:
        raise ValueError("GitHub asset digest differs")
    files = validate_archive(data, version)
    output = Path(output).resolve()
    if output.exists():
        raise ValueError("Preserve existing staging; use a fresh output directory")
    output.mkdir(parents=True)
    for name, content in files.items():
        target = output.joinpath(*PurePosixPath(name).parts)
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(content)
    evidence = {"tag": tag, "version": version, "source": reference["sha"],
                "release": release["html_url"], "zip_sha256": digest,
                "files": {name: hashlib.sha256(content).hexdigest() for name, content in files.items()},
                "website": "https://kieransimkin.co.uk/danceflow/"}
    (output.parent / "wordpress-publication.json").write_text(json.dumps(evidence, indent=2) + "\n", "utf-8")
    return evidence


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--tag", required=True)
    parser.add_argument("--output", default="wordpress-build")
    args = parser.parse_args()
    result = stage(args.tag, args.output)
    if os.environ.get("GITHUB_OUTPUT"):
        with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as handle:
            handle.write("version=" + result["version"] + "\n")
    print(json.dumps({key: result[key] for key in ["tag", "source", "zip_sha256", "website"]}))
