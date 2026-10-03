#!/usr/bin/env python3
"""
ساخت بسته‌ی مقالات سایت از دو فایل Word («مقالات در سایت ۱ و ۲»).

خروجی: database/data/blog-articles/articles.json + تصاویر بهینه‌شده در database/data/blog-articles/images/
سپس `php artisan blog:import-articles` آن‌ها را در جدول blogs ثبت می‌کند.

مرز هر مقاله دستی و از روی عنوان‌های سند تعیین شده است (ARTICLES پایین). فایل دوم یک سند پیوسته با
پرسش‌های کوتاه است؛ برای جلوگیری از مقاله‌های خیلی کوتاه، پرسش‌های هم‌موضوع در یک مقاله جمع شده‌اند.

اجرا (از ریشه‌ی پروژه، نیاز به Pillow):
    python3 database/data/build-blog-articles.py "سایت/مقالات در سایت 1.docx" "سایت/مقالات در سایت 2.docx"
"""
import html
import io
import json
import os
import re
import sys
import zipfile
from xml.etree import ElementTree as ET

from PIL import Image

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
A = '{http://schemas.openxmlformats.org/drawingml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
REL = '{http://schemas.openxmlformats.org/package/2006/relationships}'

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'blog-articles')
IMG_DIR = os.path.join(OUT, 'images')
MAX_WIDTH = 1000

# (doc, ranges [(start, end_exclusive)], slug, title, skip_first_paragraph_text)
ARTICLES = [
    (0, [(0, 330)], 'what-is-a-computer-history', 'کامپیوتر چیست؟ تاریخچه و نسل‌های کامپیوتر', True),
    (0, [(330, 351)], 'why-learn-computer', 'چرا باید کامپیوتر یاد بگیریم؟', True),
    (0, [(351, 371)], 'computer-role-in-daily-life', 'کامپیوتر چه نقشی در زندگی روزمره ما دارد؟', True),
    (0, [(371, 401)], 'is-computer-literacy-for-everyone', 'آیا یادگیری کامپیوتر برای همه لازم است یا فقط برای متخصصان؟', True),
    (0, [(401, 438)], 'how-computers-save-time', 'کامپیوترها چگونه باعث صرفه‌جویی در زمان می‌شوند؟', True),
    (0, [(438, 444)], 'computer-vs-mobile', 'تفاوت بین کامپیوتر و موبایل در چیست؟', True),
    (0, [(444, 469)], 'will-computers-replace-humans', 'آیا کامپیوترها جای انسان‌ها را در کارها می‌گیرند؟', True),
    (0, [(469, 498)], 'most-important-computer-skills', 'چه مهارت‌هایی در کار با کامپیوتر مهم‌تر هستند؟', True),
    (0, [(498, 529)], 'is-learning-computer-hard', 'آیا یادگیری کامپیوتر سخت است؟', True),
    (0, [(529, 565)], 'safe-computer-use', 'چگونه می‌توانیم از کامپیوترها به‌صورت امن استفاده کنیم؟', True),
    (0, [(565, 575)], 'software-and-hardware-pillars', 'نرم‌افزار و سخت‌افزار: دو ستون اصلی دنیای دیجیتال', True),
    (0, [(575, 612)], 'computer-components-hardware-to-software', 'آشنایی با اجزای فنی رایانه؛ از سخت‌افزار تا نرم‌افزار', True),
    (0, [(612, 10_000)], 'software-hardware-in-it', 'نقش نرم‌افزار و سخت‌افزار در فناوری اطلاعات', True),

    (1, [(2, 24)], 'computer-operating-system-and-dos', 'کامپیوتر، سیستم‌عامل و داس چیست؟', False),
    (1, [(24, 219)], 'windows-history-and-editions', 'ویندوز چیست؟ تاریخچه و تفاوت نسخه‌های ویندوز XP تا ۱۰', False),
    (1, [(219, 254), (339, 342)], 'linux-distributions-vs-windows', 'لینوکس چیست؟ انواع نسخه‌ها و فرق آن با ویندوز', False),
    (1, [(254, 273)], 'windows-server-versions', 'ویندوز سرور چیست و نسخه‌های آن', False),
    (1, [(273, 339)], 'mac-imac-macintosh', 'مک، آی‌مک و مکینتاش چیست؟', False),
    (1, [(342, 406)], 'computer-internal-components', 'اجزای داخلی کامپیوتر و کاربرد آن‌ها', False),
    (1, [(406, 638), (798, 800)], 'intel-amd-cpu-generations', 'نسل‌ها و سری‌های پردازنده‌های اینتل و AMD', False),
    (1, [(638, 678)], 'cpu-sockets-intel-amd', 'انواع سوکت‌های CPU اینتل و AMD', False),
    (1, [(678, 729)], 'nvidia-graphics-cards', 'انواع کارت گرافیک انویدیا از اول تا آخر', True),
    (1, [(729, 798)], 'hard-drive-and-case-types', 'انواع هارد و کیس کامپیوتر', False),
    (1, [(800, 813)], 'ram-bus-ddr1-to-ddr5', 'باس رم چیست؟ از DDR1 تا DDR5', False),
    (1, [(813, 829)], 'driver-boot-and-boot-menu', 'درایور، بوت و بوت منو چیست؟', False),
    (1, [(829, 869)], 'cables-inside-pc-case', 'کابل‌های داخل کیس کامپیوتر', False),
    (1, [(869, 925)], 'bios-vs-uefi', 'BIOS و UEFI چیست و چه تفاوتی دارند؟', False),
    (1, [(925, 10_000)], 'keyboard-symbols', 'نام انواع نمادهای (Symbol) کیبورد کامپیوتر', True),
]


class Doc:
    def __init__(self, path):
        self.zip = zipfile.ZipFile(path)
        self.body = list(ET.fromstring(self.zip.read('word/document.xml')).find(W + 'body'))
        rels = ET.fromstring(self.zip.read('word/_rels/document.xml.rels'))
        self.rels = {r.get('Id'): r.get('Target') for r in rels.iter(REL + 'Relationship')}


def text_of(p):
    return ''.join(t.text or '' for t in p.iter(W + 't')).strip()


def style_of(p):
    s = p.find('./' + W + 'pPr/' + W + 'pStyle')
    return s.get(W + 'val') if s is not None else ''


def is_list(p):
    return p.find('./' + W + 'pPr/' + W + 'numPr') is not None or style_of(p) == 'ListParagraph'


def all_bold(p):
    runs = [r for r in p.iter(W + 'r') if ''.join(t.text or '' for t in r.iter(W + 't')).strip()]
    if not runs:
        return False
    return all(r.find('./' + W + 'rPr/' + W + 'b') is not None for r in runs)


def runs_html(p):
    out = []
    for r in p.iter(W + 'r'):
        t = ''.join(x.text or '' for x in r.iter(W + 't'))
        if not t:
            continue
        t = html.escape(t)
        out.append(f'<strong>{t}</strong>' if r.find('./' + W + 'rPr/' + W + 'b') is not None else t)
    return re.sub(r'\s+', ' ', ''.join(out)).strip()


class Builder:
    def __init__(self, slug):
        self.slug = slug
        self.images = []

    def image(self, doc, blip):
        target = doc.rels.get(blip.get(R + 'embed'))
        if not target:
            return ''
        data = doc.zip.read('word/' + target)
        n = len(self.images) + 1
        im = Image.open(io.BytesIO(data))
        if getattr(im, 'is_animated', False):
            im.seek(0)
        if im.width > MAX_WIDTH:
            im = im.resize((MAX_WIDTH, round(im.height * MAX_WIDTH / im.width)), Image.LANCZOS)
        # همه به JPEG؛ تصاویر شفاف روی زمینه‌ی سفید متن مقاله قرار می‌گیرند
        if im.mode in ('RGBA', 'LA', 'P'):
            rgba = im.convert('RGBA')
            flat = Image.new('RGB', rgba.size, (255, 255, 255))
            flat.paste(rgba, mask=rgba.split()[3])
            im = flat
        name = f'{self.slug}-{n}.jpg'
        im.convert('RGB').save(os.path.join(IMG_DIR, name), 'JPEG', quality=78, optimize=True, progressive=True)
        self.images.append(name)
        return f'<figure class="blog-figure"><img src="{{{{IMG:{name}}}}}" alt="" loading="lazy"></figure>'

    def table(self, tbl):
        rows = []
        for i, tr in enumerate(tbl.iter(W + 'tr')):
            cells = [html.escape(' '.join(text_of(p) for p in tc.iter(W + 'p')).strip()) for tc in tr.iter(W + 'tc')]
            tag = 'th' if i == 0 else 'td'
            rows.append('<tr>' + ''.join(f'<{tag}>{c}</{tag}>' for c in cells) + '</tr>')
        return '<div class="table-responsive"><table class="table table-bordered">' + ''.join(rows) + '</table></div>'


def build(doc, ranges, slug, title, skip_title):
    b = Builder(slug)
    parts, list_items = [], []

    def flush_list():
        if list_items:
            parts.append('<ul>' + ''.join(f'<li>{li}</li>' for li in list_items) + '</ul>')
            list_items.clear()

    first = True
    for start, end in ranges:
        for el in doc.body[start:end]:
            tag = el.tag.split('}')[1]
            if tag == 'tbl':
                flush_list()
                parts.append(b.table(el))
                continue
            if tag != 'p':
                continue

            imgs = [b.image(doc, blip) for blip in el.iter(A + 'blip')]
            text = text_of(el)
            content = runs_html(el)

            if first and skip_title:
                first = False
                flush_list()
                parts.extend(i for i in imgs if i)
                continue
            first = False

            if not text:
                if imgs:
                    flush_list()
                    parts.extend(i for i in imgs if i)
                continue

            st = style_of(el)
            heading = None
            if st in ('Heading1', 'Heading2') and len(text) < 120:
                heading = 'h2'
            elif st in ('Heading3', 'Heading4') and len(text) < 120:
                heading = 'h3'
            elif all_bold(el) and len(text) < 90:
                heading = 'h2' if doc_index_of(doc) == 1 else 'h3'

            if heading:
                flush_list()
                parts.append(f'<{heading}>{html.escape(text)}</{heading}>')
            elif is_list(el) and len(text) < 600:
                list_items.append(content)
            else:
                flush_list()
                parts.append(f'<p>{content}</p>')
            if imgs:
                flush_list()
                parts.extend(i for i in imgs if i)
    flush_list()

    plain = [re.sub('<[^>]+>', '', p) for p in parts if p.startswith('<p>')]
    summary = ' '.join(plain)[:260].rsplit(' ', 1)[0] + '…' if plain else title

    return {
        'slug': slug,
        'title': title,
        'seo_title': title + ' | لوپ',
        'short_des': summary,
        'meta_description': summary[:155],
        'des': '\n'.join(parts),
        'cover': b.images[0] if b.images else None,
        'images': b.images,
    }


DOCS = []


def doc_index_of(doc):
    return DOCS.index(doc)


def main():
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    os.makedirs(IMG_DIR, exist_ok=True)
    for f in os.listdir(IMG_DIR):
        os.remove(os.path.join(IMG_DIR, f))

    DOCS.extend(Doc(p) for p in sys.argv[1:3])
    articles = [build(DOCS[d], ranges, slug, title, skip) for d, ranges, slug, title, skip in ARTICLES]

    with open(os.path.join(OUT, 'articles.json'), 'w', encoding='utf-8') as fh:
        json.dump(articles, fh, ensure_ascii=False, indent=1)

    total = sum(os.path.getsize(os.path.join(IMG_DIR, f)) for f in os.listdir(IMG_DIR))
    print(f'{len(articles)} articles, {len(os.listdir(IMG_DIR))} images, {total // 1024} KB')
    for a in articles:
        print(f"  {a['slug']:45} {len(a['des']):7} chars  {len(a['images']):3} img")


if __name__ == '__main__':
    main()
