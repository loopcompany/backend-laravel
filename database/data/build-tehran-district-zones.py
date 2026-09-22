"""Split districts 21 and 22 along the service-area line in west Tehran.

The service area's western edge does not follow a district border; operations
defined it by road:

    Fath -> Kerman Khodro (north) -> Lashkari (west) -> Darou Pakhsh (north)
    -> Ardestani (north) -> Hamedani (west) -> Kermanshah blvd (north)
    -> Shohadaye Alborz / Kharrazi (east)

(The brief called the Hamedani -> freeway link "Kaj"; OSM has no Kaj there, and
Kermanshah blvd is the only street joining the two west of Ardestani.)

Each district is cut along that line into an east and a west zone. The cut is
made with the district polygon itself, so the two zones tile the district
exactly and the outer borders stay identical to tehran-districts.geojson.

Input: roads_raw.json from Overpass:
    [out:json];way["highway"](35.69,51.08,35.78,51.23);out geom;
Needs: pip install shapely
"""
import json, math
from shapely import set_precision
from shapely.geometry import LineString, Point, shape, mapping
from shapely.ops import linemerge, nearest_points, split, substring, unary_union

SRC, DISTRICTS, OUT = 'roads_raw.json', 'tehran-districts.geojson', 'tehran-district-zones.geojson'

roads = json.load(open(SRC))['elements']
D = {f['properties']['district']: shape(f['geometry']) for f in json.load(open(DISTRICTS))['features']}


def ways(pred):
    return [LineString([(p['lon'], p['lat']) for p in e['geometry']])
            for e in roads if len(e.get('geometry', [])) > 1 and pred(e['tags'])]


named = lambda *names: (lambda t: t.get('name') in names)
KK = ways(lambda t: 'کرمان خودرو' in t.get('name', '') and t.get('highway') in ('primary', 'trunk'))
LASH = ways(lambda t: t.get('name') == 'بزرگراه لشگری' and t.get('highway') == 'trunk')
DP = ways(named('داروپخش'))
ARD = ways(lambda t: t.get('name') in ('بلوار مصطفی اردستانی', 'بلوار اردستانی') and t.get('highway') == 'secondary')
HAM = ways(lambda t: t.get('name') == 'بزرگراه همدانی' and t.get('highway') == 'trunk')
KSH = ways(named('بلوار کرمانشاه'))
KHR = ways(lambda t: any(k in t.get('name', '') for k in ('خرازی', 'شهدای البرز'))
           and t.get('highway') in ('trunk', 'motorway'))


def leg(ls, p, q):
    """The stretch of a road between the points nearest p and q, along one carriageway."""
    merged = linemerge(unary_union(ls))
    best = min(getattr(merged, 'geoms', [merged]), key=lambda l: l.distance(p) + l.distance(q))
    a, b = best.project(p), best.project(q)
    coords = list(substring(best, min(a, b), max(a, b)).coords)
    return coords if a <= b else coords[::-1]


def junction(a, b):
    a, b = unary_union(a), unary_union(b)
    hit = a.intersection(b)
    if not hit.is_empty:
        return hit if hit.geom_type == 'Point' else hit.centroid
    p, q = nearest_points(a, b)
    return Point((p.x + q.x) / 2, (p.y + q.y) / 2)


def extend(coords, d=0.0006):
    """Push both ends outward so the cut fully crosses the district border."""
    def push(p, q):
        dx, dy = p[0] - q[0], p[1] - q[1]
        n = math.hypot(dx, dy) or 1
        return (p[0] + dx / n * d, p[1] + dy / n * d)
    coords = list(coords)
    return [push(coords[0], coords[1])] + coords + [push(coords[-1], coords[-2])]


# Kerman Khodro meets district 21's south-west edge (the Fath highway).
hits = unary_union(KK).intersection(D[21].boundary)
A = min(getattr(hits, 'geoms', [hits]), key=lambda p: p.y)
B, C = junction(KK, LASH), junction(DP, LASH)
# Darou Pakhsh / Ardestani cross the Tehran-Karaj freeway, the 21/22 border.
Dj = nearest_points(D[21].intersection(D[22]), Point(51.1537, 35.7424))[0]
E, K1 = junction(ARD, HAM), junction(KSH, HAM)
K2 = nearest_points(unary_union(KHR), Point(51.1435, 35.7559))[0]
X = Point(51.1566, 35.7611)  # Kharrazi reaches district 22's northern border

cut21 = leg(KK, A, B) + leg(LASH, B, C) + leg(DP, C, Point(51.1535, 35.7418)) + [Dj.coords[0]]
cut22 = ([Dj.coords[0]] + leg(ARD, Point(51.1539, 35.7431), E) + leg(HAM, E, K1)
         + leg(KSH, K1, Point(51.1437, 35.7559)) + [K2.coords[0]] + leg(KHR, K2, X))

TITLES = {
    (21, 'east'): 'منطقه ۲۱ - شرق (از کرمان‌خودرو و داروپخش به شرق)',
    (21, 'west'): 'منطقه ۲۱ - غرب (غرب کرمان‌خودرو و داروپخش)',
    (22, 'east'): 'منطقه ۲۲ - شرق (از اردستانی و بلوار کرمانشاه به شرق)',
    (22, 'west'): 'منطقه ۲۲ - غرب (غرب اردستانی و بلوار کرمانشاه)',
}

features = []
for district, cut in ((21, cut21), (22, cut22)):
    line = LineString(extend(LineString(cut).simplify(0.00008).coords))
    parts = sorted(split(D[district], line).geoms, key=lambda p: p.area, reverse=True)
    main = parts[:2]
    # Where the cut runs back along itself it can leave a sliver; give it to the
    # zone it shares the most border with.
    for sliver in parts[2:]:
        i = max(range(2), key=lambda k: main[k].boundary.intersection(sliver.boundary).length)
        main[i] = main[i].union(sliver)
    east, west = sorted(main, key=lambda p: p.centroid.x, reverse=True)
    for side, geom in (('east', east), ('west', west)):
        features.append({
            'type': 'Feature',
            'properties': {'district': district, 'code': f'{district}-{side}', 'side': side,
                           'title': TITLES[(district, side)]},
            'geometry': mapping(set_precision(geom, 1e-6)),
        })
        print(f'{district}-{side}: {geom.area / D[district].area:.1%} of the district')

json.dump({'type': 'FeatureCollection', 'features': features}, open(OUT, 'w'),
          ensure_ascii=False, separators=(',', ':'))
