"""Build an allowlisted, deterministic production plugin, without tests or CI files."""
from pathlib import Path
import hashlib, re, zipfile

ROOT=Path(__file__).resolve().parents[1]
def build(output=None):
    version=re.search(r'\* Version: ([\d.]+)',(ROOT/'kieran-epk-social-metadata.php').read_text('utf-8')).group(1)
    readme=(ROOT/'readme.txt').read_text('utf-8')
    assert f'Stable tag: {version}\n' in readme
    files=[ROOT/'kieran-epk-social-metadata.php',ROOT/'README.md',ROOT/'readme.txt',ROOT/'LICENSE.txt']
    for folder in ['includes','data','docs']:
        files.extend(p for p in (ROOT/folder).rglob('*') if p.is_file())
    for path in files:
        assert not path.is_symlink()
        assert path.suffix in {'.php','.json','.md','.txt','.svg','.png'},path
    output=Path(output) if output else ROOT/'dist'/f'kieran-epk-social-metadata-{version}.zip'
    output.parent.mkdir(parents=True,exist_ok=True)
    assert not output.exists(),'Preserve existing package bytes; use a fresh output path'
    with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as z:
        for p in sorted(files):
            entry=zipfile.ZipInfo('kieran-epk-social-metadata/'+p.relative_to(ROOT).as_posix(),(2020,1,1,0,0,0))
            entry.compress_type=zipfile.ZIP_DEFLATED;entry.external_attr=0o100644<<16
            z.writestr(entry,p.read_bytes())
    digest=hashlib.sha256(output.read_bytes()).hexdigest()
    (output.parent/'SHA256SUMS.txt').write_text(f'{digest}  {output.name}\n','ascii',newline='\n')
    return output
if __name__=='__main__': print(build())
