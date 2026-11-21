# Baut das installierbare ZIP: python3 scripts/build-package.py
import re
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

root = Path('mgd-ebook')
# Version aus der Hauptdatei lesen
version = re.search(r'^ \* Version: (\S+)', (root / 'mgd-ebook.php').read_text(encoding='utf-8'), re.M).group(1)
target = Path('dist') / f'mgd-ebook-{version}.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(root.rglob('*')):
        if path.is_file() and path.name != '.DS_Store' and not path.name.startswith('.smbdelete') and path.suffix != '.log':
            archive.write(path, path.as_posix())
print(target)
