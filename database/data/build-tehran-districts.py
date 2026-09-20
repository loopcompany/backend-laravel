"""Build a simplified GeoJSON of Tehran's 22 municipal districts from raw Overpass output.

Simplification is done once per OSM *way*, not per polygon: neighbouring districts
share the same border ways, so this keeps shared borders bit-identical and the 22
polygons stay gap- and overlap-free.
"""
import json, re, math

SRC, OUT = 'districts_raw.json', 'tehran-districts.geojson'
EPS = 0.00025                      # ~25 m at Tehran's latitude
FA = '۰۱۲۳۴۵۶۷۸۹'

fa2en = lambda s: ''.join(str(FA.index(c)) if c in FA else c for c in s)


def perp_dist(p, a, b):
    if a == b:
        return math.hypot(p[0] - a[0], p[1] - a[1])
    dx, dy = b[0] - a[0], b[1] - a[1]
    t = max(0.0, min(1.0, ((p[0] - a[0]) * dx + (p[1] - a[1]) * dy) / (dx * dx + dy * dy)))
    return math.hypot(p[0] - (a[0] + t * dx), p[1] - (a[1] + t * dy))


def rdp(pts, eps):
    """Douglas-Peucker. Iterative, so a 1400-point way can't blow the stack."""
    if len(pts) < 3:
        return pts
    keep = [False] * len(pts)
    keep[0] = keep[-1] = True
    stack = [(0, len(pts) - 1)]
    while stack:
        lo, hi = stack.pop()
        dmax, idx = 0.0, lo
        for i in range(lo + 1, hi):
            d = perp_dist(pts[i], pts[lo], pts[hi])
            if d > dmax:
                dmax, idx = d, i
        if dmax > eps:
            keep[idx] = True
            stack += [(lo, idx), (idx, hi)]
    return [p for p, k in zip(pts, keep) if k]


def stitch(ways):
    """Join way geometries into closed rings by matching endpoints."""
    segs, rings = [list(w) for w in ways if len(w) >= 2], []
    while segs:
        cur, changed = segs.pop(0), True
        while cur[0] != cur[-1] and changed:
            changed = False
            for i, s in enumerate(segs):
                if s[0] == cur[-1]:    cur = cur + s[1:]
                elif s[-1] == cur[-1]: cur = cur + s[-2::-1]
                elif s[-1] == cur[0]:  cur = s[:-1] + cur
                elif s[0] == cur[0]:   cur = s[::-1][:-1] + cur
                else: continue
                segs.pop(i); changed = True; break
        if cur[0] != cur[-1]:
            cur.append(cur[0])
        rings.append(cur)
    return rings


def ring_area(r):
    return abs(sum(r[i][0] * r[i + 1][1] - r[i + 1][0] * r[i][1]
                   for i in range(len(r) - 1)) / 2.0)


data = json.load(open(SRC))

# Pass 1 — simplify every border way exactly once, keyed by OSM way id.
ways, raw_total, simp_total = {}, 0, 0
for el in data['elements']:
    for mem in el.get('members', []):
        if mem['type'] != 'way' or 'geometry' not in mem or mem.get('role') not in ('outer', 'inner', ''):
            continue
        if mem['ref'] in ways:
            continue
        pts = [(round(p['lon'], 6), round(p['lat'], 6)) for p in mem['geometry']]
        s = rdp(pts, EPS)
        ways[mem['ref']] = s
        raw_total += len(pts); simp_total += len(s)

# Pass 2 — stitch the simplified ways into each district's rings.
feats, stats = [], []
for el in data['elements']:
    tags = el.get('tags', {})
    name = tags.get('name', '')
    m = re.search(r'منطقه\s+([۰-۹0-9]+)', name)
    num = int(fa2en(m.group(1))) if m else None

    outer, inner = [], []
    for mem in el.get('members', []):
        role = mem.get('role')
        if mem['type'] != 'way' or mem['ref'] not in ways or role not in ('outer', 'inner', ''):
            continue
        (inner if role == 'inner' else outer).append(ways[mem['ref']])

    orings = sorted(stitch(outer), key=ring_area, reverse=True)
    irings = stitch(inner)
    orings = [r for r in orings if len(r) >= 4]
    if not orings:
        print('!! no usable outer ring for', name); continue

    def holes_for(o):
        xs, ys = [p[0] for p in o], [p[1] for p in o]
        return [h for h in irings
                if min(xs) <= h[0][0] <= max(xs) and min(ys) <= h[0][1] <= max(ys)]

    polys = [[[list(p) for p in o]] + [[list(p) for p in h] for h in holes_for(o)] for o in orings]
    geom = ({'type': 'Polygon', 'coordinates': polys[0]} if len(polys) == 1
            else {'type': 'MultiPolygon', 'coordinates': polys})

    pts = [p for o in orings for p in o]
    feats.append({'type': 'Feature', 'properties': {
        'osm_id': el['id'], 'district': num, 'name': name,
        'name_en': tags.get('name:en') or (f'District {num}' if num else None),
        'center_lat': round(sum(p[1] for p in pts) / len(pts), 6),
        'center_lng': round(sum(p[0] for p in pts) / len(pts), 6),
    }, 'geometry': geom})
    stats.append((num, len(orings), len(irings), sum(len(r) for r in orings)))

feats.sort(key=lambda f: f['properties']['district'] or 999)
json.dump({'type': 'FeatureCollection', 'features': feats},
          open(OUT, 'w'), ensure_ascii=False, separators=(',', ':'))

print(f'{len(feats)} districts, {len(ways)} border ways')
print(f'way points: {raw_total} -> {simp_total} ({100 * simp_total / raw_total:.0f}%)')
bad = [s for s in stats if s[1] != 1]
print('districts with != 1 outer ring:', bad or 'none')
print('total ring points:', sum(s[3] for s in stats))
