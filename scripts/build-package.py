# Baut das installierbare ZIP + .sha256: python3 scripts/build-package.py
import re
import time
from hashlib import sha256
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

root = Path('mgd-ebook')
# Version aus der Hauptdatei lesen
version = re.search(r'^ \* Version: (\S+)', (root / 'mgd-ebook.php').read_text(encoding='utf-8'), re.M).group(1)
target = Path('dist') / f'mgd-ebook-{version}.zip'
target.parent.mkdir(exist_ok=True)
files = [p for p in sorted(root.rglob('*'))
         if p.is_file() and p.name != '.DS_Store' and not p.name.startswith('.smbdelete') and p.suffix != '.log']
assert files and not any(p.is_symlink() for p in files)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for path in files:
        # Rechte fest 644, sonst 403 nach unzip per SSH
        info = ZipInfo(path.as_posix(), date_time=time.localtime(path.stat().st_mtime)[:6])
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        archive.writestr(info, path.read_bytes())
with ZipFile(target) as archive:
    assert archive.testzip() is None
    assert all(n.startswith('mgd-ebook/') for n in archive.namelist())
# Prüfsumme nur mit Dateinamen, ohne dist/ davor
digest = sha256(target.read_bytes()).hexdigest()
Path(f'{target}.sha256').write_text(f'{digest}  {target.name}\n', encoding='utf-8')
print(target)
print(f'{digest}  {target.name}')
