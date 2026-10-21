"""
Script to package Moodle plugin into a compliant ZIP file for Moodle Marketplace / Plugins Directory.
"""
import os
import re
import zipfile
import time

def get_plugin_version():
    version_file = 'version.php'
    release = 'v1.0.7'
    build = '2024050107'
    if os.path.exists(version_file):
        with open(version_file, 'r', encoding='utf-8') as f:
            content = f.read()
            m_rel = re.search(r"\$plugin->release\s*=\s*['\"]([^'\"]+)['\"];", content)
            if m_rel:
                release = m_rel.group(1)
            m_bld = re.search(r"\$plugin->version\s*=\s*([0-9]+);", content)
            if m_bld:
                build = m_bld.group(1)
    return release, build

def build_package():
    release, build = get_plugin_version()
    zip_filename = f'tool_bulkcopycourse_{release}_{build}.zip'
    root_folder = 'bulkcopycourse'

    files_to_pack = []
    dirs_to_pack = set([root_folder])

    for root, dirs, files in os.walk('.'):
        # Exclude git, github, scratch, vendor, pycache, etc.
        dirs[:] = [d for d in dirs if not d.startswith('.') and d not in ['scratch', 'vendor', '__pycache__']]
        rel_root = os.path.relpath(root, '.')
        if rel_root != '.':
            dir_arc = root_folder + '/' + rel_root.replace('\\', '/')
            dirs_to_pack.add(dir_arc)
        for f in files:
            if f.startswith('.') or f.endswith('.zip') or f == 'package.py':
                continue
            rel_path = os.path.relpath(os.path.join(root, f), '.')
            arcname = root_folder + '/' + rel_path.replace('\\', '/')
            files_to_pack.append((os.path.join(root, f), arcname))

    sorted_dirs = sorted(dirs_to_pack, key=lambda x: (x.count('/'), x))

    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as z:
        now = time.localtime(time.time())[:6]
        # Add explicit directory entries with Unix drwxr-xr-x mode (0755)
        for d in sorted_dirs:
            d_name = d if d.endswith('/') else d + '/'
            zi = zipfile.ZipInfo(d_name, now)
            zi.external_attr = 0o40755 << 16
            zi.external_attr |= 0x10  # DOS directory attribute
            z.writestr(zi, '')

        # Add files with Unix -rw-r--r-- mode (0644)
        for filepath, arcname in sorted(files_to_pack, key=lambda x: x[1]):
            with open(filepath, 'rb') as fp:
                data = fp.read()
            zi = zipfile.ZipInfo(arcname, now)
            zi.external_attr = 0o100644 << 16
            z.writestr(zi, data, compress_type=zipfile.ZIP_DEFLATED)

    print(f'Successfully created Moodle-compliant ZIP: {zip_filename}')
    return zip_filename

if __name__ == '__main__':
    build_package()
