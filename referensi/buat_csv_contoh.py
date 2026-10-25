import csv
prio = {1: "Prioritas 1 (Paling Penting)", 2: "Prioritas 2", 3: "Prioritas 3", 4: "Prioritas 4", 5: "Prioritas 5 (Paling Kurang Penting)"}
nama = ["Harga Laptop", "Processor", "RAM", "Kapasitas Penyimpanan", "Tipe Penyimpanan"]
likert_h = [
 "Seberapa penting harga dalam memilih laptop untuk perkuliahan Pendidikan Komputer?",
 "Seberapa penting spesifikasi processor dalam memilih laptop untuk perkuliahan Pendidikan Komputer?",
 "Seberapa penting kapasitas RAM dalam memilih laptop untuk perkuliahan Pendidikan Komputer?",
 "Seberapa penting kapasitas penyimpanan dalam memilih laptop untuk perkuliahan Pendidikan Komputer?",
 "Seberapa penting tipe penyimpanan (SSD atau HDD) dalam memilih laptop untuk perkuliahan Pendidikan Komputer?",
]
rank_q = "Dari kelima kriteria berikut, urutkan berdasarkan tingkat kepentingannya dalam memilih laptop terbaik untuk kebutuhan mahasiswa Pendidikan Komputer. (Setiap prioritas hanya boleh dipakai satu kali)"
headers = ["Timestamp", "Nama/Inisial", "Jenis Kelamin", "Angkatan",
           "Merk Beserta Seri/Model laptop yang Anda gunakan\nContoh:\nASUS VivoBook 14\nAcer Aspire 5"] \
          + likert_h + [f"{rank_q} [{n}]" for n in nama]
rows = [
 ["9/11/2026 21:35:08", "Budi",  "Laki-laki", "2024", "ASUS VivoBook 14", 5,5,4,3,3, 1,2,3,4,5],
 ["9/11/2026 21:40:19", "Ani",   "Perempuan", "2024", "Acer Aspire 5",    4,5,5,4,2, 2,1,3,5,4],
 ["9/11/2026 21:41:37", "Citra", "Perempuan", "2025", "HP 14s",           5,4,4,4,3, 1,3,2,4,5],
 ["9/11/2026 21:50:02", "Dodi (contoh urutan salah)", "Laki-laki", "2025", "Lenovo LOQ 15IAX9E", 5,5,5,5,5, 1,2,2,4,5],
]
with open("contoh-survei.csv", "w", newline="", encoding="utf-8") as f:
    w = csv.writer(f); w.writerow(headers)
    for r in rows: w.writerow(r[:5] + r[5:10] + [prio[x] for x in r[10:]])
print("ok")
