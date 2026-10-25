# Referensi analisis sensitivitas (data contoh yang sama dengan langkah 2).
w = [0.331667, 0.331667, 0.206667, 0.065, 0.065]   # bobot ROC contoh
r = {'A': [0.830769, 0.6, 0.6, 1.0, 1.0],
     'B': [0.658537, 1.0, 1.0, 1.0, 0.6],
     'C': [1.0, 0.4, 0.6, 0.5, 0.2]}
names = ['C1 Harga', 'C2 Processor', 'C3 RAM', 'C4 Kapasitas', 'C5 Tipe']

def totals(weights):
    return {k: sum(weights[j]*v[j] for j in range(5)) for k, v in r.items()}
def order(t): return sorted(t, key=lambda k: (-round(t[k], 10), k))

base = totals(w)
print("dasar", {k: round(v, 6) for k, v in base.items()}, order(base))
for j in range(5):
    for ch in (-0.2, 0.2):
        m = w[:]; m[j] *= (1+ch); s = sum(m); m = [x/s for x in m]
        t = totals(m)
        print(f"{names[j]:14s} {ch:+.1f}", {k: round(v, 6) for k, v in t.items()}, order(t))
