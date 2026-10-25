# Korelasi Spearman antara urutan kriteria menurut bobot ROC dan bobot Likert (data contoh langkah 2).
def avg_ranks(v):
    order = sorted(range(len(v)), key=lambda i: -v[i])
    ranks = [0]*len(v); i = 0
    while i < len(v):
        j = i
        while j+1 < len(v) and abs(v[order[j+1]] - v[order[i]]) < 1e-12: j += 1
        for k in range(i, j+1): ranks[order[k]] = (i+j)/2 + 1
        i = j+1
    return ranks
def pearson(a, b):
    n = len(a); ma = sum(a)/n; mb = sum(b)/n
    num = sum((x-ma)*(y-mb) for x, y in zip(a, b))
    return num / (sum((x-ma)**2 for x in a) * sum((y-mb)**2 for y in b))**0.5
roc    = [0.331667, 0.331667, 0.206667, 0.065, 0.065]
likert = [0.225, 0.2375, 0.225, 0.175, 0.1375]
print("rank roc   ", avg_ranks(roc))
print("rank likert", avg_ranks(likert))
print("spearman", round(pearson(avg_ranks(roc), avg_ranks(likert)), 6))
