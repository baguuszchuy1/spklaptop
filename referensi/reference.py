# Implementasi referensi (independen dari PHP) untuk memverifikasi ROC dan SAW.
n = 5
roc = {k: sum(1/i for i in range(k, n+1)) / n for k in range(1, n+1)}
print("ROC per prioritas:", {k: round(v, 6) for k, v in roc.items()})

# Responden: (ranks C1..C5, likert C1..C5)
resp = [
    ([1,2,3,4,5], [5,5,5,4,3]),
    ([2,1,3,5,4], [4,5,4,3,3]),
    ([1,3,2,4,5], [5,4,5,4,2]),
    ([3,1,2,5,4], [4,5,4,3,3]),
]
w_roc = [sum(roc[r[0][j]] for r in resp)/len(resp) for j in range(n)]
mean_l = [sum(r[1][j] for r in resp)/len(resp) for j in range(n)]
w_lik = [m/sum(mean_l) for m in mean_l]
mean_rank = [sum(r[0][j] for r in resp)/len(resp) for j in range(n)]
print("w_roc  ", [round(x, 6) for x in w_roc], round(sum(w_roc), 6))
print("w_lik  ", [round(x, 6) for x in w_lik], round(sum(w_lik), 6))
print("mean_rank", mean_rank)
print("mean_likert", mean_l)

# Alternatif. C1 Harga = nilai asli (cost). C2..C5 = skor rubrik (benefit).
# Rubrik (sama dengan CriterionSeeder):
def proc(s):  return 1 if s<5000 else 2 if s<10000 else 3 if s<15000 else 4 if s<20000 else 5
def ram(g):   return 1 if g<=4 else 3 if g<=8 else 4 if g<=12 else 5
def cap(g):   return 1 if g<=255 else 2 if g<=511 else 4 if g<=999 else 5
typ = {'HDD':1,'SSD SATA':3,'SSD + HDD':4,'SSD NVMe':5}
lap = {
  'A': dict(price=6500000, cpu=12000, ram=8,  gb=512, t='SSD NVMe'),
  'B': dict(price=8200000, cpu=21000, ram=16, gb=512, t='SSD SATA'),
  'C': dict(price=5400000, cpu=7000,  ram=8,  gb=256, t='HDD'),
}
X = {k: [v['price'], proc(v['cpu']), ram(v['ram']), cap(v['gb']), typ[v['t']]] for k, v in lap.items()}
print("X:", X)
def saw(w):
    cols = list(zip(*X.values()))
    out = {}
    for k, row in X.items():
        r = [min(cols[0])/row[0]] + [row[j]/max(cols[j]) for j in range(1, n)]
        out[k] = (r, sum(w[j]*r[j] for j in range(n)))
    return out
for name, w in (("ROC", w_roc), ("Likert", w_lik), ("Sama rata", [0.2]*5)):
    res = saw(w)
    order = sorted(res, key=lambda k: -res[k][1])
    print(name, {k: round(res[k][1], 6) for k in res}, "urutan:", order)
r = saw(w_roc)
for k in r: print("r", k, [round(x, 6) for x in r[k][0]])
