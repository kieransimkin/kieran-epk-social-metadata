from pathlib import Path
import importlib.util, tempfile, zipfile, hashlib
ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location('package',ROOT/'tools/package.py');module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
with tempfile.TemporaryDirectory() as folder:
    one=module.build(Path(folder)/'one.zip');two=module.build(Path(folder)/'two.zip')
    assert one.read_bytes()==two.read_bytes()
    with zipfile.ZipFile(one) as z:
        names=z.namelist()
        assert len(names)==len(set(names))
        assert all(n.startswith('kieran-epk-social-metadata/') for n in names)
        assert not any('/tests/' in n or '/tools/' in n or '/.github/' in n for n in names)
        assert 'kieran-epk-social-metadata/readme.txt' in names
        assert 'kieran-epk-social-metadata/LICENSE.txt' in names
        for n in names: assert z.read(n)==(ROOT/n.removeprefix('kieran-epk-social-metadata/')).read_bytes()
print('Production package boundaries, exact source bytes and reproducibility passed')
