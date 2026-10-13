"""Regression cases for publishing existing immutable releases without rebuilding."""
import hashlib
import importlib.util
import io
import json
from pathlib import Path
import stat
import tempfile
import unittest
import zipfile

spec = importlib.util.spec_from_file_location("stager", Path(__file__).parents[1]/"tools/stage-wordpress-release.py")
stager = importlib.util.module_from_spec(spec)
spec.loader.exec_module(stager)


def package(extra=None, version="1.2.4"):
    stream = io.BytesIO()
    with zipfile.ZipFile(stream, "w") as archive:
        archive.writestr(stager.SLUG + "/" + stager.SLUG + ".php", "<?php\n/**\n * Version: " + version + "\n */")
        archive.writestr(stager.SLUG + "/readme.txt", "Stable tag: " + version + "\nhttps://kieransimkin.co.uk/\n")
        archive.writestr(stager.SLUG + "/LICENSE.txt", "Synthetic contract fixture")
        if extra:
            name, mode = extra
            info = zipfile.ZipInfo(name)
            info.external_attr = mode << 16
            archive.writestr(info, "fixture")
    return stream.getvalue()


def fetcher(data, checksum=None):
    digest = hashlib.sha256(data).hexdigest()
    prefix = "https://github.com/" + stager.REPO + "/releases/download/v1.2.4/"
    name = stager.SLUG + "-1.2.4.zip"
    def fetch(url):
        if "/releases/tags/" in url:
            return json.dumps({"tag_name":"v1.2.4", "draft":False, "prerelease":False,
                               "html_url":"https://github.com/" + stager.REPO + "/releases/tag/v1.2.4",
                               "assets":[{"name":name,"browser_download_url":prefix + name,
                                          "size":len(data),"digest":"sha256:" + digest},
                                         {"name":"SHA256SUMS.txt","browser_download_url":prefix + "SHA256SUMS.txt"}]}).encode()
        if "/git/ref/" in url:
            return json.dumps({"object":{"type":"tag", "sha":"b"*40}}).encode()
        if "/git/tags/" in url:
            return json.dumps({"object":{"type":"commit", "sha":"a"*40}}).encode()
        if url.endswith("SHA256SUMS.txt"):
            return ((checksum or digest) + "  " + name + "\n").encode()
        return data
    return fetch


class Contracts(unittest.TestCase):
    def test_existing_release_retains_exact_payload_and_identified_source(self):
        data = package()
        with tempfile.TemporaryDirectory() as folder:
            output = Path(folder)/"stage"
            result = stager.stage("v1.2.4", output, fetcher(data))
            self.assertEqual(result["source"], "a"*40)
            with zipfile.ZipFile(io.BytesIO(data)) as archive:
                for name in archive.namelist():
                    self.assertEqual((output/name).read_bytes(), archive.read(name))
            with self.assertRaisesRegex(ValueError, "existing staging"):
                stager.stage("v1.2.4", output, fetcher(data))

    def test_changed_release_bytes_do_not_create_staging(self):
        with tempfile.TemporaryDirectory() as folder:
            output = Path(folder)/"stage"
            with self.assertRaisesRegex(ValueError, "checksum"):
                stager.stage("v1.2.4", output, fetcher(package(), "0"*64))
            self.assertFalse(output.exists())

    def test_traversal_symlinks_and_development_files_are_rejected(self):
        for name, mode in [("../outside.php", 0o100644),
                           (stager.SLUG + "/link", stat.S_IFLNK | 0o777),
                           (stager.SLUG + "/tests/private.php", 0o100644)]:
            with self.subTest(name=name), self.assertRaises(ValueError):
                stager.validate_archive(package((name, mode)), "1.2.4")

    def test_version_and_tag_must_agree(self):
        with self.assertRaisesRegex(ValueError, "Plugin version"):
            stager.validate_archive(package(version="1.2.3"), "1.2.4")
        with self.assertRaisesRegex(ValueError, "stable version"):
            stager.stage("main", Path("unused"), fetcher(package()))


if __name__ == "__main__":
    unittest.main()
